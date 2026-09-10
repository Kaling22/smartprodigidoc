<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Models\UserOffDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Ketersediaan peninjau (FITUR-BARU-v4 §6).
 *
 * Menjawab SATU pertanyaan: "siapa yang bisa menangani dokumen sekarang, dan
 * yang tidak — kapan ia kembali?"
 *
 * Dipusatkan seperti {@see DocumentParticipantResolver}: jawaban yang sama
 * dipakai papan pemilihan DI LAYAR dan penjaga DI SERVER, sehingga mustahil
 * layar menampilkan seseorang bisa dipilih sementara server menolaknya.
 *
 * Dua sebab seseorang tak tersedia, dan keduanya sengaja dibedakan:
 *   • PENUH  — sudah memegang `smartpro.peninjau.batas_dokumen` dokumen berjalan.
 *   • OFF    — cuti / off day / dinas luar pada hari ini.
 * Keduanya memblokir penugasan BARU, tapi tak satu pun menarik dokumen yang
 * sudah dipegang (ketetapan pemilik 3 Agustus 2026).
 *
 * Sejak PLAN-REVISI-v6 Fase A, `batas_dokumen` bawaannya 0 — jadi PENUH tak
 * pernah lagi terjadi di lapangan, dan yang menggantikannya adalah `tingkat`:
 * beban ditampilkan sebagai ANGKA, keputusannya dikembalikan ke GL. Jalur
 * "penuh" sengaja tetap utuh sebagai rem darurat satu-suntingan di config.
 */
class ReviewerAvailability
{
    /** Status tiap sel pita. */
    public const SEL_TERSEDIA = 'tersedia';

    public const SEL_OFF = 'off';

    public const SEL_TUTUP = 'tutup';   // akhir pekan ATAU libur nasional

    /**
     * Section user_picker mana yang memakai PAPAN KETERSEDIAAN, dan pada yang
     * mana batas beban BENAR-BENAR memblokir.
     *
     * Dulu dua baris `in_array(...)` di dalam `documents/fields/_user_picker`.
     * Dipindah ke sini karena ia keputusan yang sama dengan yang ditegakkan
     * server di `DocumentController::saringPeninjauTakTersedia()` — bukan urusan
     * lapisan tampilan (pakem P3/P5). Halaman React menerimanya sebagai props;
     * nol aturan peserta yang diketik ulang di TSX.
     *
     * `penyetuju` => false punya DUA akibat sekaligus, keduanya disengaja:
     * batas beban tak berlaku (PJO cuma satu orang — memblokirnya menghentikan
     * seluruh SOP di 7 departemen), dan kolom angka beban tak dirender (angka
     * itu mengukur beban PENINJAUAN, bukan tugas menyetujui).
     */
    public const PAPAN = [
        'peninjau' => true,
        'peninjau_penyetuju' => true,
        'penyetuju' => false,
    ];

    /**
     * Ketersediaan sekumpulan kandidat, siap dipakai Blade.
     *
     * @param  Collection<int, User>  $kandidat
     * @param  Document|null  $kecuali  dokumen yang SEDANG disunting. Dipakai DUA
     *                                  hal, dan keduanya sah karena ia memang
     *                                  dokumen yang sedang dikerjakan:
     *                                  (a) tak ikut dihitung sebagai beban, agar
     *                                      memilih ulang peninjau yang sama tak
     *                                      terlihat menambah bebannya;
     *                                  (b) sumber JENIS dokumen, penentu apakah
     *                                      `tingkat` diwarnai (lihat `ambang`).
     * @return array<int, array{
     *     beban:int, batas:int, penuh:bool, off:bool, tersedia:bool, tingkat:string,
     *     jenis:?string, kembali:?CarbonImmutable, alasan:string, pita:array<int, array{tanggal:CarbonImmutable, status:string}>
     * }>
     */
    public function untuk(Collection $kandidat, ?Document $kecuali = null): array
    {
        if ($kandidat->isEmpty()) {
            return [];
        }

        $ids = $kandidat->pluck('id')->all();
        $hariIni = CarbonImmutable::today();
        $batas = (int) config('smartpro.peninjau.batas_dokumen', 2);
        $panjangPita = (int) config('smartpro.peninjau.hari_pita', 14);

        $beban = $this->bebanPeninjau($ids, $kecuali);
        $off = $this->offPeriods($ids, $hariIni, $panjangPita);
        $tutup = $this->hariTutup($hariIni, $panjangPita);

        // Ambang warna hanya berlaku bagi jenis yang terdaftar. Dibaca SEKALI di
        // luar perulangan; null = jenis ini mencetak angka polos.
        $ambang = config('smartpro.peninjau.ambang', []);
        $berambang = in_array($kecuali?->type?->code, $ambang['jenis'] ?? [], true) ? $ambang : null;

        $hasil = [];
        foreach ($ids as $id) {
            $periode = $off->get($id, collect());
            $sedangOff = $periode->first(fn (UserOffDay $o) => $hariIni->between($o->mulai, $o->sampai));

            $jumlah = $beban[$id] ?? 0;
            $penuh = $batas > 0 && $jumlah >= $batas;

            $hasil[$id] = [
                'beban' => $jumlah,
                'batas' => $batas,
                'penuh' => $penuh,
                'tingkat' => match (true) {
                    ! $berambang => 'normal',
                    $jumlah >= (int) $berambang['sibuk'] => 'sibuk',
                    $jumlah >= (int) $berambang['padat'] => 'padat',
                    default => 'normal',
                },
                'off' => (bool) $sedangOff,
                'tersedia' => ! $penuh && ! $sedangOff,
                'jenis' => $sedangOff?->jenisLabel(),
                'kembali' => $sedangOff ? $this->hariKerjaSesudah($sedangOff->sampai, $tutup) : null,
                'alasan' => $this->alasan($sedangOff, $penuh, $jumlah, $tutup),
                'pita' => $this->pita($periode, $hariIni, $panjangPita, $tutup),
            ];
        }

        return $hasil;
    }

    /** Ketersediaan SATU orang — pembungkus tipis untuk kartu dashboard. */
    public function untukSatu(User $user): array
    {
        return $this->untuk(collect([$user]))[$user->id];
    }

    /**
     * Ketersediaan untuk section user_picker.
     *
     * `pembuat_tambahan` IKUT dihitung, tapi dibaca berbeda: dropdown-nya hanya
     * memakai kunci `off` untuk memberi keterangan "sedang cuti" pada orang yang
     * ditahan di daftar. Batas dokumen tak berlaku di sana — angka itu mengukur
     * beban PENINJAUAN, sedangkan pembuat tambahan tak meninjau apa pun.
     *
     * @param  array<string, Collection<int, User>>  $kandidat  keluaran DocumentWizard::userPickerCandidates()
     * @return array<string, array<int, array>>
     */
    public function untukPicker(array $kandidat, Document $document): array
    {
        $out = [];

        foreach (['peninjau', 'peninjau_penyetuju', 'penyetuju', 'pembuat_tambahan'] as $key) {
            if (isset($kandidat[$key])) {
                $out[$key] = $this->untuk(collect($kandidat[$key]), $document);
            }
        }

        return $out;
    }

    /**
     * Berapa dokumen berjalan yang dipegang tiap peninjau?
     *
     * "Berjalan" = sudah dikirim dan belum lepas dari tangannya:
     * `waiting_for_review` (terkirim, belum dibuka) + `in_review` (sedang
     * dikerjakan). Status sesudahnya sudah berpindah ke MD/PJO, jadi tak lagi
     * membebani peninjau.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    public function bebanPeninjau(array $ids, ?Document $kecuali = null): array
    {
        return Document::whereIn('reviewer_id', $ids)
            ->whereIn('status', ['waiting_for_review', 'in_review'])
            ->when($kecuali?->id, fn ($q, $id) => $q->where('id', '!=', $id))
            ->selectRaw('reviewer_id, COUNT(*) c')
            ->groupBy('reviewer_id')
            ->pluck('c', 'reviewer_id')
            ->map(fn ($c) => (int) $c)
            ->all();
    }

    /**
     * Rentang off yang menyentuh jendela pita, dikelompokkan per user.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Collection<int, UserOffDay>>
     */
    private function offPeriods(array $ids, CarbonImmutable $mulai, int $panjang): Collection
    {
        $akhir = $mulai->addDays($panjang - 1);

        return UserOffDay::whereIn('user_id', $ids)
            ->bertindih($mulai->toDateString(), $akhir->toDateString())
            ->orderBy('mulai')
            ->get()
            ->groupBy('user_id');
    }

    /**
     * Pita `panjang` hari mulai hari ini.
     *
     * Tiap sel membawa `label` siap-pakai untuk atribut `title` — keterangan
     * saat kursor lewat, tanpa satu baris JavaScript pun.
     *
     * @param  Collection<int, UserOffDay>  $periode
     * @param  array<int, string>  $tutup  tanggal 'Y-m-d' yang kantornya tutup
     * @return array<int, array{tanggal:CarbonImmutable, status:string, label:string}>
     */
    private function pita(Collection $periode, CarbonImmutable $mulai, int $panjang, array $tutup): array
    {
        $sel = [];
        for ($i = 0; $i < $panjang; $i++) {
            $hari = $mulai->addDays($i);

            // Urutan periksa disengaja: OFF menang atas TUTUP. Orang yang cuti
            // seminggu tetap terlihat cuti, bukan terpotong-potong akhir pekan.
            $off = $periode->first(fn (UserOffDay $o) => $hari->between($o->mulai, $o->sampai));
            $status = match (true) {
                (bool) $off => self::SEL_OFF,
                in_array($hari->toDateString(), $tutup, true) => self::SEL_TUTUP,
                default => self::SEL_TERSEDIA,
            };

            $keterangan = match ($status) {
                self::SEL_OFF => $off->jenisLabel(),
                self::SEL_TUTUP => $hari->isWeekend() ? 'Akhir pekan' : 'Libur nasional',
                default => 'Tersedia',
            };

            $sel[] = [
                'tanggal' => $hari,
                'status' => $status,
                'label' => $hari->translatedFormat('l, j F').' — '.$keterangan,
            ];
        }

        return $sel;
    }

    /**
     * Tanggal 'Y-m-d' yang kantornya tutup dalam jendela pita: akhir pekan
     * (dihitung sendiri, selalu benar) + libur nasional (dari internet).
     *
     * @return array<int, string>
     */
    private function hariTutup(CarbonImmutable $mulai, int $panjang): array
    {
        $akhir = $mulai->addDays($panjang - 1);

        $libur = array_merge(
            $this->liburNasional((int) $mulai->year),
            $mulai->year === $akhir->year ? [] : $this->liburNasional((int) $akhir->year),
        );

        $tutup = [];
        for ($i = 0; $i < $panjang; $i++) {
            $hari = $mulai->addDays($i);
            if ($hari->isWeekend() || in_array($hari->toDateString(), $libur, true)) {
                $tutup[] = $hari->toDateString();
            }
        }

        return $tutup;
    }

    /**
     * Libur nasional Indonesia, dari API publik, di-cache sehari.
     *
     * Sengaja TANPA tabel & tanpa halaman admin: daftarnya tak pernah diubah
     * oleh PT PPA, hanya dibaca. Gagal = larik kosong — pita kehilangan penanda
     * tanggal merah, dan tak ada satu pun keputusan alur yang terpengaruh
     * (libur tak pernah memblokir pemilihan; akhir pekan tetap dihitung lokal).
     *
     * Tak ada data yang DIKIRIM keluar selain angka tahun.
     *
     * @return array<int, string> tanggal 'Y-m-d'
     */
    private function liburNasional(int $tahun): array
    {
        return Cache::remember("libur_nasional:{$tahun}", now()->addDay(), function () use ($tahun) {
            try {
                $res = Http::timeout(3)->get('https://api-harilibur.vercel.app/api', ['year' => $tahun]);

                return collect($res->json())
                    ->filter(fn ($h) => ($h['is_national_holiday'] ?? false) === true)
                    ->map(fn ($h) => CarbonImmutable::parse($h['holiday_date'])->toDateString())
                    ->values()->all();
            } catch (\Throwable $e) {
                report($e);

                return [];
            }
        });
    }

    /**
     * Hari KERJA pertama sesudah rentang off berakhir.
     *
     * "Kembali Sabtu" tak berguna bagi siapa pun — yang ingin diketahui GL
     * adalah kapan orangnya benar-benar bisa menerima dokumen lagi.
     *
     * @param  array<int, string>  $tutup
     */
    private function hariKerjaSesudah(\DateTimeInterface $sampai, array $tutup): CarbonImmutable
    {
        $hari = CarbonImmutable::parse($sampai)->addDay();

        // Batas 14 lompatan: cukup untuk libur terpanjang, dan mustahil
        // berputar selamanya kalau daftar liburnya kelak salah isi.
        for ($i = 0; $i < 14; $i++) {
            if (! $hari->isWeekend() && ! in_array($hari->toDateString(), $tutup, true)) {
                break;
            }
            $hari = $hari->addDay();
        }

        return $hari;
    }

    /** Kalimat pendek yang dibaca GL di baris kandidat. */
    private function alasan(?UserOffDay $off, bool $penuh, int $beban, array $tutup): string
    {
        if ($off) {
            $kembali = $this->hariKerjaSesudah($off->sampai, $tutup);

            return $off->jenisLabel().' — kembali '.$kembali->translatedFormat('D, j M');
        }

        if ($penuh) {
            return "Penuh — {$beban} dokumen berjalan";
        }

        return 'Tersedia hari ini';
    }
}
