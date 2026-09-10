<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Services\RichText\GambarTempel;
use App\Services\RichText\PembersihHtml;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Orkestrasi wizard pengisian dokumen — dipindahkan UTUH dari DocumentController
 * agar controller kembali tipis dan aturan pengisian punya rumah sendiri.
 *
 * Cakupannya: menyimpan tiap langkah, membersihkan nilai kosong, menerima
 * unggahan lampiran, memetakan peserta alur (peninjau/penyetuju/pembuat
 * tambahan), dan langkah virtual "Log Revisi" di luar schema.
 */
class DocumentWizard
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly GambarTempel $gambarTempel,
    ) {}

    /** Save every section of a step (content, uploads, and flow participants). */
    public function persistStep(Request $request, SchemaService $schema, int $step, Document $document): void
    {
        $sections = $request->input('sections', []);
        $this->applyUploads($request, $schema, $step, $document, $sections);
        $this->persistSakelarBab($request, $schema, $step, $document);

        foreach ($schema->sectionsForStep($step) as $section) {
            $key = $section['key'];
            $type = $section['type'] ?? 'text';

            if ($type === 'user_picker') {
                $this->saveParticipants($document, $key, $sections[$key] ?? null);

                continue;
            }

            $this->documents->saveSection($document, $key, $this->cleanValue(
                $type,
                $sections[$key] ?? null,
                $section,
                $document,
            ));
        }

        $document->save();
    }

    /**
     * Simpan sakelar bab OPSIONAL milik langkah ini (mis. "Gunakan Flowchart").
     *
     * Nilainya ('1'/'0') disimpan sebagai isi bersection_key `toggle_key` —
     * kunci DI LUAR schema, pola yang sudah dipakai `catatan_revisi`. Nol
     * migrasi, nol kolom baru.
     *
     * Kiriman yang TAK memuat sakelarnya dibaca sebagai MENYALA: itulah bawaan
     * untuk dokumen baru, dan itu pula yang terjadi bila JS mati — kegagalan
     * yang aman, karena hasilnya persis perilaku sebelum fitur ini ada.
     */
    public function persistSakelarBab(Request $request, SchemaService $schema, int $step, Document $document): void
    {
        foreach ($schema->sectionsForStep($step) as $section) {
            $kunci = $section['toggle_key'] ?? null;
            if ($kunci === null) {
                continue;
            }

            $this->documents->saveSection($document, $kunci,
                (string) $request->input("sections.{$kunci}", '1') === '0' ? '0' : '1');
        }
    }

    /**
     * Persist langkah virtual "Log Revisi" (hanya draft revisi Tipe B):
     * baris catatan perubahan per halaman (lembar CATATAN REVISI) + override
     * manual Edisi/Revisi. Baris lama (salinan revisi sebelumnya) membawa
     * no_rev-nya sendiri; baris baru diberi no_revisi dokumen ini.
     *
     * Sejak keputusan pemilik 2026-09-07 bentuknya BERSARANG: satu baris = satu
     * SESI revisi (No. Rev naik satu kali per klik tombol Revisi), dan tiap
     * temuan di dalamnya jadi sub-poin a./b./c. pada kolom CATATAN. Baris tanpa
     * `sub` tetap sah dan tercetak persis seperti dulu — itulah yang membuat
     * seluruh dokumen yang sudah terbit tak berubah satu byte pun.
     */
    public function persistRevisionLog(Request $request, Document $document): void
    {
        // Edisi & Revisi: otomatis terisi (roll-over), boleh diedit manual (owner rev).
        $document->edisi = (string) max(1, (int) $request->input('edisi', $document->edisi ?: 1));
        $document->no_revisi = max(0, (int) $request->input('no_revisi', $document->no_revisi));

        $this->persistTanggalCetak($request, $document);

        // Nomor yang dipikul BARIS BARU adalah nomor yang akan tercetak, yaitu
        // nomor SESUDAH dokumen dikirim (butir 7a) — bukan nomor versi terbit
        // yang masih tersimpan di kolomnya selama draft disusun.
        [, $noRevisiCetak] = $document->revisiSaatKirim();

        // Sub-poin a./b./c. di dalam satu baris. Hanya `catatan`-nya yang
        // tersimpan: `bab` cuma petunjuk layar, dan halaman tiap temuan sudah
        // digabung ke kolom HAL. baris induknya.
        //
        // Sub-poin KOSONG sengaja ikut tersimpan. Tombol Revisi menyusun
        // kerangka lebih dulu (satu butir per bab yang berubah, catatannya
        // masih kosong) dan autosave 1200 ms membekukannya sebelum jari sampai
        // ke kotak pertama — membuangnya di sini berarti kerangka itu lenyap
        // begitu halaman dimuat ulang. Yang tak boleh tercetak disaring di
        // lembar cetaknya, bukan di sini; aturannya sama dengan baris induk,
        // yang juga tersimpan meski catatannya masih kosong.
        $subPoin = fn ($v) => collect(is_array($v) ? $v : [])
            ->map(fn ($p) => ['catatan' => trim((string) (is_array($p) ? ($p['catatan'] ?? '') : $p))])
            ->values()
            ->all();

        $rows = collect($request->input('sections.catatan_revisi', []))
            ->map(fn ($r) => is_array($r) ? array_merge($r, ['sub' => $subPoin($r['sub'] ?? [])]) : $r)
            // Baris berisi = ada tanggal/halaman/catatan ATAU minimal satu
            // sub-poin. Tanpa syarat kedua, baris yang isinya SELURUHNYA di
            // sub-poin (bentuk barunya: induk cuma kalimat pembuka, dan itu
            // pun boleh kosong) akan dibuang diam-diam saat disimpan.
            ->filter(fn ($r) => is_array($r) && (collect($r)->only(['tanggal', 'halaman', 'catatan'])->filter(fn ($v) => filled($v))->isNotEmpty() || $r['sub'] !== []))
            ->map(fn ($r) => [
                'no_rev' => filled($r['no_rev'] ?? null) ? (int) $r['no_rev'] : $noRevisiCetak,
                'tanggal' => trim((string) ($r['tanggal'] ?? '')),
                'halaman' => trim((string) ($r['halaman'] ?? '')),
                'catatan' => trim((string) ($r['catatan'] ?? '')),
                'sub' => $r['sub'],
            ])
            ->values()
            ->all();

        $this->documents->saveSection($document, 'catatan_revisi', $rows);
        $document->save();
    }

    /**
     * Tgl. Terbit & Tgl. Revisi kop cetak — HANYA untuk draft salinan arsip
     * (TEMUAN-F8).
     *
     * Dibatasi `salin_arsip_at` dengan sengaja. Dokumen yang benar-benar
     * ditinjau memperoleh tanggal terbitnya dari pengesahan, dan membiarkan
     * kedua tanggal itu ditulis dari kiriman wizard berarti nomor POST yang
     * dirakit tangan bisa memundurkan tanggal berlaku dokumen mana pun — batas
     * yang tak terlihat di layar, jadi ia harus dijaga di sini.
     *
     * Kosong = jangan sentuh: bawaannya sudah dikirim server (published_at versi
     * lama untuk terbit, hari ini untuk revisi), jadi field yang dikosongkan
     * pengguna tak boleh diam-diam menghapus tanggal yang sudah tersimpan.
     */
    private function persistTanggalCetak(Request $request, Document $document): void
    {
        if (! $document->salin_arsip_at) {
            return;
        }

        foreach (['tanggal_terbit' => 'published_at', 'tanggal_revisi' => 'tanggal_revisi'] as $input => $kolom) {
            $nilai = trim((string) $request->input($input, ''));

            if ($nilai !== '' && ($tanggal = strtotime($nilai)) !== false) {
                $document->{$kolom} = date('Y-m-d', $tanggal);
            }
        }
    }

    /** Map user_picker sections to reviewer/approver columns and document_authors. */
    private function saveParticipants(Document $document, string $key, mixed $value): void
    {
        if ($key === 'peninjau') {
            $document->reviewer_id = $value ?: null;
        } elseif ($key === 'penyetuju') {
            $document->approver_id = $value ?: null;
        } elseif ($key === 'peninjau_penyetuju') {
            // SP/IK: satu SH/DH meninjau SEKALIGUS menyetujui → isi kedua kolom sama.
            $document->reviewer_id = $value ?: null;
            $document->approver_id = $value ?: null;
        } elseif ($key === 'pembuat_tambahan') {
            // Hanya id yang BENAR-BENAR kandidat sah yang disimpan. Menyaring di
            // dropdown saja tidak cukup — kiriman POST bisa memuat id mana pun
            // (mis. SH dept lain), dan aturannya wajib sama persis dgn daftar
            // yang ditampilkan.
            $sah = $this->coAuthorCandidates($document)->pluck('id');
            $ids = collect(is_array($value) ? $value : [])
                ->filter()->map(fn ($v) => (int) $v)->unique()
                ->filter(fn ($id) => $sah->contains($id));

            $document->authors()->where('is_primary', false)->delete();
            foreach ($ids as $id) {
                $document->authors()->create(['user_id' => $id, 'is_primary' => false]);
            }
        }
    }

    /**
     * Daftar dokumen BERLAKU sedepartemen — isi pemilih lampiran (bab VII SOP).
     *
     * Dipecah jadi `jenis`/`nomor`/`judul` supaya pemilihnya bisa menampilkan
     * lencana jenis & nomor bergaya monospace, dan bisa menyaring atas nomor
     * MAUPUN judul. Yang TERSIMPAN tetap satu baris teks gabungan
     * "PPA-ADRO-IK-ICTMD-02 — Instruksi Kerja Perawatan", bukan id: lampiran
     * MENYEBUT dokumen sebagai keterangan cetak, bukan merelasikannya, jadi
     * kalau dokumen rujukannya kelak dihapus barisnya tetap terbaca.
     *
     * Dokumen lama yang nanti diunggah ke "Dokumen Berlaku" akan ikut muncul
     * di sini dengan sendirinya — tak ada kode yang perlu disentuh lagi.
     *
     * @return array<int, array{jenis:string, nomor:string, judul:string}>
     */
    public function dokumenBerlaku(Document $document): array
    {
        return Document::berlaku()
            ->with('type')
            ->where('department_id', $document->department_id)
            ->where('id', '!=', $document->id)
            ->orderBy('doc_number')
            ->get()
            ->map(fn (Document $d) => [
                'jenis' => (string) $d->type?->code,
                'nomor' => $d->displayNumber(),
                'judul' => (string) $d->title,
            ])
            ->all();
    }

    /**
     * Trim empty items from list/group values so blank rows aren't persisted.
     *
     * $section = definisi section-nya dari schema; $document = dokumen pemiliknya.
     * Keduanya dibutuhkan HANYA oleh repeatable_group berkolom `rich_text`:
     * kolom itu berisi HTML, dan HTML dari peramban harus lewat DUA tahap
     * sebelum menyentuh basis data —
     *
     *   1. {@see GambarTempel}  foto tempelan (data URI) jadi berkas lampiran
     *   2. {@see PembersihHtml} sisanya disaring menurut daftar putih
     *
     * Urutannya tak boleh terbalik: bersihkan() membuang data URI, jadi
     * menjalankannya lebih dulu berarti foto tempelan hilang sebelum sempat
     * disimpan.
     *
     * Keduanya berpenanda bawaan supaya pemanggil lama (dan test) tetap sah.
     * Tanpa $document, tahap (1) dilewati dan perilakunya persis seperti dulu.
     */
    public function cleanValue(string $type, mixed $value, array $section = [], ?Document $document = null): mixed
    {
        $fields = $section['group_fields'] ?? $section['fields'] ?? [];
        if (in_array($type, ['rich_list', 'reference_picker'], true) && is_array($value)) {
            return array_values(array_filter($value, fn ($v) => filled(trim((string) $v))));
        }

        if ($type === 'repeatable_group' && is_array($value)) {
            /*
             | Sanitasi terjadi SAAT SIMPAN, bukan saat cetak: dgn begitu apa pun
             | yang tersimpan di document_contents sudah bersih, dan jalur cetak,
             | layar tinjau, serta prompt AI tak perlu mempercayai apa pun.
             |
             | Urutannya penting — dibersihkan DULU, baru baris kosong dibuang.
             | Editor yang dikosongkan mengirim '<p><br></p>', yang `filled()`
             | anggap terisi; tanpa urutan ini, baris yang sesungguhnya kosong
             | akan ikut tersimpan dan memakan satu baris tabel di PDF.
             */
            $rich = array_column(
                array_filter($fields, fn ($f) => ($f['type'] ?? '') === 'rich_text'),
                'key'
            );

            if ($rich !== []) {
                $sectionKey = (string) ($section['key'] ?? '');

                $value = array_map(function ($row) use ($rich, $document, $sectionKey) {
                    if (! is_array($row)) {
                        return $row;
                    }
                    foreach ($rich as $k) {
                        if (! array_key_exists($k, $row)) {
                            continue;
                        }

                        $mentah = is_string($row[$k]) ? $row[$k] : null;

                        if ($mentah !== null && $document !== null) {
                            $mentah = $this->gambarTempel->tukar($document, $mentah, $sectionKey);
                        }

                        $row[$k] = PembersihHtml::bersihkan($mentah);
                    }

                    return $row;
                }, $value);
            }

            return array_values(array_filter($value, fn ($row) => is_array($row) && collect($row)->filter(fn ($v) => filled($v))->isNotEmpty()));
        }

        // JSA analisa: nested Langkah Kerja → Bahaya → Pengendalian. Buang yang kosong.
        if ($type === 'jsa_analysis' && is_array($value)) {
            $steps = [];
            foreach ($value as $step) {
                if (! is_array($step)) {
                    continue;
                }
                $langkah = trim((string) ($step['langkah'] ?? ''));
                $bahayaList = [];
                foreach (($step['bahaya'] ?? []) as $bahaya) {
                    if (! is_array($bahaya)) {
                        continue;
                    }
                    $risiko = trim((string) ($bahaya['risiko'] ?? ''));
                    $kendali = array_values(array_filter(
                        array_map(fn ($v) => trim((string) $v), (array) ($bahaya['pengendalian'] ?? [])),
                        fn ($v) => $v !== '',
                    ));
                    if ($risiko !== '' || $kendali) {
                        $bahayaList[] = ['risiko' => $risiko, 'pengendalian' => $kendali];
                    }
                }
                if ($langkah !== '' || $bahayaList) {
                    $steps[] = ['langkah' => $langkah, 'bahaya' => $bahayaList];
                }
            }

            return $steps;
        }

        return $value;
    }

    /**
     * Store uploaded images (image / text_or_image group fields) per department
     * (PRD v2 §4.3: storage/app/public/lampiran/{DEPT}/{JENIS}/) and inject the
     * resulting path into $sections so it persists in value_json.
     */
    private function applyUploads(Request $request, SchemaService $schema, int $step, Document $document, array &$sections): void
    {
        $files = $request->file('files', []);
        $dept = $document->department->code;
        $jenis = $document->type->code;

        foreach ($schema->sectionsForStep($step) as $section) {
            if (($section['type'] ?? null) !== 'repeatable_group') {
                continue;
            }

            $imageKeys = collect($section['group_fields'] ?? $section['fields'] ?? [])
                ->filter(fn ($f) => in_array($f['type'] ?? null, ['image', 'text_or_image'], true))
                ->pluck('key');

            $rowFiles = $files[$section['key']] ?? [];

            foreach ($rowFiles as $rowIndex => $fieldFiles) {
                foreach ($fieldFiles as $fieldKey => $uploaded) {
                    if (! $uploaded || ! $uploaded->isValid() || ! $imageKeys->contains($fieldKey)) {
                        continue;
                    }

                    // Guard: JPG/PNG, max 2MB (PRD §4.3).
                    if (! in_array($uploaded->getMimeType(), ['image/jpeg', 'image/png'], true) || $uploaded->getSize() > 2 * 1024 * 1024) {
                        continue;
                    }

                    $filename = uniqid('img_').'.'.$uploaded->getClientOriginalExtension();
                    $path = $uploaded->storeAs("lampiran/{$dept}/{$jenis}", $filename, 'public');

                    $sections[$section['key']][$rowIndex][$fieldKey] = $path;

                    $document->attachments()->create([
                        'section_key' => $section['key'],
                        'path' => $path,
                        'original_name' => $uploaded->getClientOriginalName(),
                        'mime' => $uploaded->getMimeType(),
                        'size' => $uploaded->getSize(),
                    ]);
                }
            }
        }
    }

    /**
     * Kandidat user_picker. Peninjau & penyetuju memakai matriks aturan-alur-v2
     * (jenis + departemen + SHE/Plant); section lain memakai role_filter schema.
     */
    public function userPickerCandidates(SchemaService $schema, Document $document): array
    {
        $resolver = app(DocumentParticipantResolver::class);
        $out = [];

        foreach ($schema->allSections() as $section) {
            if (($section['type'] ?? null) !== 'user_picker') {
                continue;
            }

            $key = $section['key'];
            if ($key === 'peninjau' || $key === 'peninjau_penyetuju') {
                // peninjau_penyetuju (SP/IK): kandidat = SH/DH dept (peninjau &
                // penyetuju identik untuk alur ini).
                $out[$key] = $resolver->reviewerCandidates($document);

                continue;
            }
            if ($key === 'penyetuju') {
                $out[$key] = $resolver->approverCandidates($document);

                continue;
            }
            if ($key === 'pembuat_tambahan') {
                $out[$key] = $this->coAuthorCandidates($document);

                continue;
            }

            $query = \App\Models\User::with('department')->where('status', 'active');
            if ($filter = ($section['role_filter'] ?? null)) {
                $query->whereHas('roles', fn ($q) => $q->whereIn('name', $filter));
            }
            $out[$key] = $query->orderBy('name')->get();
        }

        return $out;
    }

    /**
     * Kandidat PEMBUAT TAMBAHAN — pembantu penyusun dokumen.
     *
     * Dibatasi **GL & Non-Staff dari DEPARTEMEN YANG SAMA** dengan dokumen:
     *   • sedepartemen — penyusun harus orang yang benar-benar mengerjakan
     *     proses itu; dokumen mutu bersifat per-departemen;
     *   • bukan SH/DH/PJO — mereka peninjau/penyetuju, tak boleh merangkap
     *     sebagai penyusun dokumen yang kelak mereka nilai sendiri;
     *   • bukan pembuat utama — dia sudah tercantum sebagai pembuat.
     *
     * Dipakai BERSAMA oleh dropdown dan penyimpanan, sehingga daftar yang
     * tampil dan yang boleh tersimpan mustahil berbeda.
     *
     * `whereIn` (bukan `whereNotIn`) disengaja: bila kelak ada jabatan baru,
     * ia TIDAK otomatis lolos jadi kandidat.
     *
     * Yang sedang CUTI/OFF/DINAS LUAR hari ini dibuang — aturan yang sama
     * dengan peninjau (CLAUDE.md §6). Pembuat tambahan ada untuk SATU tujuan:
     * melanjutkan pengisian bila pembuat utama berhalangan; cadangan yang
     * sedang cuti bukan cadangan. Batas 2 dokumen TIDAK ikut berlaku — angka
     * itu mengukur beban peninjauan, dan penyusun tak meninjau apa pun.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function coAuthorCandidates(Document $document): \Illuminate\Database\Eloquent\Collection
    {
        // Yang SUDAH tercatat pada dokumen ini tetap muncul walau sedang cuti.
        // Ini keharusan, bukan kelonggaran: saveParticipants() menghapus lalu
        // menulis ulang SELURUH pembuat tambahan, disaring dengan daftar ini.
        // Tanpa pengecualian ini, GL yang mengajukan cuti SESUDAH ditunjuk akan
        // lenyap namanya dari baris "Dibuat Oleh" pada penyimpanan berikutnya —
        // diam-diam, tanpa ada yang menyentuh kolomnya.
        $tercatat = $document->authors()->where('is_primary', false)->pluck('user_id');

        $sedangOff = \App\Models\UserOffDay::aktifPada(now()->toDateString())->pluck('user_id');

        return User::with('department')->where('status', 'active')
            ->where('id', '!=', $document->created_by)
            ->where('department_id', $document->department_id)
            ->whereIn('jabatan', [User::JABATAN_GROUP_LEADER, User::JABATAN_STAFF])
            ->where(fn ($q) => $q->whereNotIn('id', $sedangOff)->orWhereIn('id', $tercatat))
            ->orderBy('name')->get();
    }

    /** Current values for user_picker sections (reviewer/approver/co-authors). */
    public function userPickerValues(Document $document): array
    {
        return [
            'peninjau' => $document->reviewer_id,
            'penyetuju' => $document->approver_id,
            'peninjau_penyetuju' => $document->reviewer_id,   // SP/IK: sama dgn approver
            'pembuat_tambahan' => $document->authors()->where('is_primary', false)->pluck('user_id')->all(),
        ];
    }

    /**
     * Kandidat user_picker → larik polos siap kirim ke props Inertia.
     *
     * WAJIB lewat sini, tak boleh model User apa adanya: props Inertia tertulis
     * seluruhnya di atribut `data-page` dan terbaca lewat "view source", jadi
     * `password` + `remember_token` ikut terbawa (pelajaran Fase 6) dan kunci
     * mentah `jabatan` ikut tercetak (CLAUDE.md §6c). Yang dikirim persis empat
     * hal yang memang dibaca papan & dropdown — tak lebih.
     *
     * @param  array<string, \Illuminate\Support\Collection<int, User>>  $candidates
     * @return array<string, list<array{id:int, nama:string, nrp:string, dept:string, jabatan:string}>>
     */
    public function propsKandidat(array $candidates): array
    {
        return collect($candidates)->map(
            fn ($daftar) => collect($daftar)->map(fn (User $u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'nrp' => $u->nrp,
                'dept' => $u->department->code ?? '-',
                // Label, bukan kunci mentah — dijaga LabelJabatanTest.
                'jabatan' => $u->jabatanLabel(),
            ])->values()->all()
        )->all();
    }

    /**
     * Ketersediaan peninjau → larik polos, TANGGALNYA SUDAH DIFORMAT SERVER.
     *
     * {@see ReviewerAvailability::untuk()} memulangkan objek Carbon di `kembali`
     * dan di tiap sel `pita`. Membiarkannya jadi JSON berarti memformat ulang
     * tanggal di peramban — dengan locale, timezone, dan nama bulan yang
     * ditentukan mesin pengguna, bukan oleh aplikasi. Tanggal WITA berbahasa
     * Indonesia adalah keputusan server; ia diformat di sini, sekali.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $ketersediaan
     */
    public function propsKetersediaan(array $ketersediaan): array
    {
        return collect($ketersediaan)->map(
            fn (array $perOrang) => collect($perOrang)->map(fn (array $a) => [
                'beban' => $a['beban'],
                'batas' => $a['batas'],
                'penuh' => $a['penuh'],
                'tingkat' => $a['tingkat'],
                'off' => $a['off'],
                'tersedia' => $a['tersedia'],
                'jenis' => $a['jenis'],
                'kembali' => $a['kembali']?->translatedFormat('l, j F'),
                // Bentuk yang bisa DIURUTKAN. Papan memakainya untuk menyebut
                // siapa yang paling cepat bebas saat tak ada satu pun kandidat
                // bisa dipilih; 'l, j F' mustahil diurutkan sebagai teks.
                'kembaliIso' => $a['kembali']?->toDateString(),
                'alasan' => $a['alasan'],
                'pita' => collect($a['pita'])->map(fn (array $sel) => [
                    // 'j M' dipakai penanda awal minggu di sumbu waktu papan.
                    'tanggal' => $sel['tanggal']->translatedFormat('j M'),
                    'status' => $sel['status'],
                    'label' => $sel['label'],
                ])->all(),
            ])->all()
        )->all();
    }
}
