<?php

namespace App\Models;

use App\Services\DocumentService;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doc_number', 'doc_number_final', 'doc_number_manual', 'arsip_path', 'arsip_path_asli', 'document_type_id', 'department_id',
        'title', 'status', 'current_step', 'revision_round', 'no_revisi', 'revises_document_id',
        'edisi', 'is_controlled', 'reviewer_id', 'approver_id', 'created_by',
        'submitted_at', 'published_at', 'salin_arsip_at', 'tanggal_revisi',
        'nonaktif_tahap', 'nonaktif_oleh', 'obsolete_reason',
    ];

    protected $casts = [
        'doc_number_manual' => 'boolean',
        'is_controlled' => 'boolean',
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'salin_arsip_at' => 'datetime',
        // Tanggal murni, bukan waktu: yang tercetak di kop cuma d/m/Y, dan jam
        // yang ikut tersimpan hanya menimbulkan pergeseran hari di zona waktu.
        'tanggal_revisi' => 'date',
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'waiting_for_review' => 'Menunggu Ditinjau',
        'in_review' => 'Dalam Peninjauan',
        'rejected' => 'Ditolak',
        'verifikasi_md' => 'Verifikasi MD',
        'pending_approval' => 'Menunggu Persetujuan',
        'published' => 'Berlaku',
        'sedang_direvisi' => 'Sedang Direvisi',
        'menunggu_nonaktif' => 'Menunggu Nonaktif',
        'obsolete' => 'Tidak Berlaku',
        // legacy
        'submitted' => 'Submitted',
        'needs_revision' => 'Perlu Revisi',
        'archived' => 'Diarsipkan',
    ];

    /**
     * Rupa badge tiap status: [warna1, warna2, ikon].
     *
     * Elemen keempat ('solid' / 'lunak') DIBUANG di spec dashboard v3 R1: tak
     * ada lagi badge bergradasi pekat di mana pun, semuanya lembut. Yang dulu
     * dikerjakan gradasi — membedakan status AKHIR dari status BERJALAN —
     * sekarang dikerjakan RONA dan IKON: hijau/merah/hitam hanya muncul pada
     * hasil akhir, sisanya di ramp hangat. Itu pembedaan yang tetap terbaca
     * oleh mata yang sulit membedakan warna, tanpa satu badge pun berteriak.
     *
     * SATU tempat, sengaja. Peta ini dulu disalin di empat berkas Blade
     * (documents/index, documents/show, documents/staff-status, dashboard) dan
     * sudah saling menyimpang: tiga di antaranya memuat kunci warisan yang tak
     * pernah ditulis ke basis data, dan `verifikasi_md` TIDAK ADA di satu pun —
     * sehingga dokumen di tahap Verifikasi MD selalu tampil abu-abu, tak
     * terbedakan dari Draft.
     *
     * Dipakai lewat partials/_badge-status. Warna diambil dari palet di
     * layouts/app (--su-*, --pp-*); ditulis hex di sini karena nilainya juga
     * dipakai sebagai titik warna di matriks dasbor, bukan hanya sebagai kelas.
     */
    public const STATUS_META = [
        // Status BERJALAN — seluruhnya di ramp hangat (kelabu → kuning → oranye →
        // cokelat bakar), searah identitas oranye-hitam. Yang membedakan antar
        // status bukan cuma rona melainkan juga TERANGNYA + ikonnya masing-masing.
        'draft' => ['#71717a', '#52525b', 'bi-pencil'],
        'waiting_for_review' => ['#f59e0b', '#d97706', 'bi-hourglass-split'],
        'in_review' => ['#ea580c', '#c2410c', 'bi-clipboard-check'],
        'verifikasi_md' => ['#7c2d12', '#431407', 'bi-fonts'],
        'pending_approval' => ['#facc15', '#eab308', 'bi-patch-question'],
        'sedang_direvisi' => ['#a16207', '#854d0e', 'bi-arrow-repeat'],
        'menunggu_nonaktif' => ['#b45309', '#92400e', 'bi-slash-circle'],
        // Status AKHIR — hijau & merah SENGAJA disisakan hanya di sini, sebagai
        // penanda hasil (berhasil / gagal), persis cara referensi memakainya.
        // Kalau dua warna ini juga dipakai untuk hal lain, keduanya berhenti
        // berarti "baik" dan "buruk".
        'published' => ['#22c55e', '#16a34a', 'bi-patch-check-fill'],
        'rejected' => ['#ef4444', '#dc2626', 'bi-x-octagon'],
        'obsolete' => ['#27272a', '#18181b', 'bi-archive'],
        // Kunci warisan: tak pernah ditulis lagi, tapi baris lama di basis data
        // bisa saja masih memakainya. Dipetakan netral supaya tak jadi badge kosong.
        'submitted' => ['#71717a', '#52525b', 'bi-send'],
        'needs_revision' => ['#a16207', '#854d0e', 'bi-arrow-repeat'],
        'archived' => ['#27272a', '#18181b', 'bi-archive'],
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** Dokumen Berlaku yang sedang direvisi oleh draft ini (Tipe B). */
    public function revisesDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revises_document_id');
    }

    /**
     * Kebalikan revisesDocument(): dokumen yang MENGGANTIKAN yang ini.
     *
     * Dipakai layar arsip mobile untuk menjawab "digantikan oleh apa?" —
     * pertanyaan pertama orang saat menemukan dokumen Tidak Berlaku.
     */
    public function direvisiOleh(): HasOne
    {
        return $this->hasOne(self::class, 'revises_document_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(DocumentContent::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(DocumentAuthor::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * SEMUA yang ikut menyusun dokumen: pembuat utama + pembuat tambahan.
     *
     * Dipakai untuk memberitahu — pembuat tambahan sebelumnya hanya tercantum di
     * halaman pengesahan lalu tak pernah menerima kabar apa pun tentang dokumen
     * yang ikut ia susun (FITUR-BARU-v4 §6). Dipusatkan di sini supaya tiap
     * peristiwa alur tak perlu mengulang aturan "siapa saja penyusunnya".
     *
     * @return Collection<int, User>
     */
    public function penyusun(): Collection
    {
        return collect([$this->creator])
            ->merge($this->authors()->where('is_primary', false)->with('user')->get()->pluck('user'))
            ->filter()
            ->unique('id')
            ->values();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    /** Pengaju nonaktif (Fase F) — menentukan apakah tahap MD ikut dilewati. */
    public function nonaktifOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nonaktif_oleh');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** Masukan lapangan Non-Staff atas dokumen ini (FITUR-BARU-v4 §3). */
    public function feedback(): HasMany
    {
        return $this->hasMany(DocumentFeedback::class);
    }

    /** Masukan sejawat dari sesama GL sedepartemen (PLAN-AKSES-v8 Fase 2). */
    public function masukanSejawat(): HasMany
    {
        return $this->hasMany(MasukanSejawat::class);
    }

    /**
     * Bolehkah $user memberi masukan atas dokumen ini?
     *
     * Tiga syarat sekaligus: (a) jabatannya Non-Staff — lihat gate
     * `beri-masukan`; (b) dokumennya milik departemen yang sama, sebab masukan
     * ditujukan ke SH/DH departemen itu; (c) dokumennya sedang BERLAKU, karena
     * masukan atas draft/dokumen mati tak ada yang bisa menindaklanjuti.
     *
     * Dipusatkan di sini agar controller web, controller API, dan tombol di
     * layar memakai aturan yang SAMA persis.
     */
    public function bisaDiberiMasukanOleh(User $user): bool
    {
        return $user->can('beri-masukan')
            && $this->department_id === $user->department_id
            && in_array($this->status, ['published', 'sedang_direvisi'], true);
    }

    /**
     * Boleh memberi MASUKAN SEJAWAT atas dokumen ini? (PLAN-AKSES-v8 Fase 2)
     *
     * Kanal terpisah dari masukan lapangan di atas: yang itu milik Non-Staff
     * atas dokumen BERLAKU; yang ini milik sesama GL atas dokumen yang MASIH
     * BISA DIUBAH. Keduanya sengaja tak saling menyentuh — gate `beri-masukan`
     * tetap utuh milik Non-Staff.
     *
     * Batasnya "belum Berlaku", bukan "draft" saja: dokumen yang sedang
     * ditinjau pun masih bisa diperbaiki pembuatnya begitu dikembalikan, jadi
     * catatan rekan masih ada gunanya. Begitu dokumen terbit, jalurnya berganti
     * jadi Masukan Lapangan / Ajukan Revisi.
     *
     * SH/DH/PJO sengaja tak ikut: mereka peninjau & penyetuju, dan menyalurkan
     * catatan lewat kanal kedua hanya akan menyaingi jalur tinjauan resminya.
     *
     * Dipakai tombol di layar DAN penjaga di controller — tombol yang tampil
     * lalu berakhir 403 adalah gejala aturan yang disalin.
     */
    public function bisaDiberiMasukanSejawatOleh(User $user): bool
    {
        return $user->jabatan === User::JABATAN_GROUP_LEADER
            && $this->department_id === $user->department_id
            && $this->created_by !== $user->id
            && ! in_array($this->status, ['published', 'sedang_direvisi', 'menunggu_nonaktif', 'obsolete'], true);
    }

    /**
     * Boleh MEMBACA masukan sejawat atas dokumen ini?
     *
     * Penyusunnya (pembuat utama maupun pembuat tambahan), pemberi masukannya
     * sendiri, dan pemegang `document.view_all`. Bukan seluruh orang yang boleh
     * MELIHAT dokumennya: catatan antar-GL adalah percakapan kerja rekan
     * sedepartemen, dan wizard pengisian juga dibuka penonton read-only.
     *
     * Dipakai halaman `masukan-sejawat.show` DAN wizard pembuat, yang sejak
     * catatan per-item ikut digambar di bawah tiap isian punya pertanyaan yang
     * sama persis.
     */
    public function bisaLihatMasukanSejawat(User $user): bool
    {
        return $this->created_by === $user->id
            || $this->authors()->where('user_id', $user->id)->exists()
            || $this->masukanSejawat()->where('user_id', $user->id)->exists()
            || $user->can('document.view_all');
    }

    /**
     * Boleh MEMULAI revisi Tipe B atas dokumen ini? (PLAN-REVISI-v6 Fase C, D1)
     *
     * GL adalah PENYUSUNNYA, jadi ia hanya boleh merevisi yang DIBUATNYA —
     * batas yang mustahil dinyatakan sebagai izin spatie, sebab izin tak
     * mengenal "dokumen yang mana". MD & Admin lolos lewat `document.view_all`;
     * MD memang bertugas lintas 7 departemen.
     *
     * Pembuat TAMBAHAN sengaja TIDAK ikut: ia baris "Dibuat Oleh" tambahan di
     * lembar pengesahan, bukan pemilik dokumennya.
     *
     * Dipakai tombol di layar DAN penjaga di controller — tombol yang tampil
     * lalu berakhir 403 adalah gejala aturan yang disalin.
     */
    public function bisaDirevisiOleh(User $user): bool
    {
        return $this->pemilikRevisi($user) && $this->status === 'published';
    }

    /**
     * Bagian WEWENANG dari {@see bisaDirevisiOleh()}, tanpa syarat statusnya.
     *
     * Dipisah karena Fase F menuntut dua jawaban yang berbeda atas pertanyaan
     * yang berbeda: orang yang salah mendapat 403, sedangkan orang yang benar
     * atas dokumen yang statusnya salah mendapat 422. Kalau keduanya diperiksa
     * sekaligus, mengajukan nonaktif atas dokumen yang sedang direvisi akan
     * berbunyi "Anda tidak berhak" — padahal ia berhak, dokumennya yang belum
     * bisa.
     */
    public function pemilikRevisi(User $user): bool
    {
        return $user->can('document.request_revision')
            && ($user->can('document.view_all') || $this->created_by === $user->id);
    }

    /** Sebab dokumen jadi Tidak Berlaku. */
    public const OBSOLETE_REVISI = 'revisi';

    public const OBSOLETE_DINONAKTIFKAN = 'dinonaktifkan';

    /** Nomor finalnya sudah DILEPAS ke kolam dan boleh dipakai dokumen lain? */
    public function nomorDilepas(): bool
    {
        return $this->obsolete_reason === self::OBSOLETE_DINONAKTIFKAN;
    }

    /**
     * Baris yang nomor finalnya masih DITAHAN.
     *
     * Nomor dokumen mutu pada dasarnya ditahan selamanya (lihat
     * DocumentNumberService); satu-satunya jalan keluar adalah dokumen yang
     * DIMATIKAN lewat alur nonaktif berjenjang (F2). Ditulis sebagai scope,
     * bukan `where('obsolete_reason', '!=', ...)`, karena perbandingan `!=`
     * MySQL ikut membuang seluruh baris ber-NULL — yakni hampir semua dokumen.
     */
    public function scopeNomorDitahan(Builder $query): Builder
    {
        return $query->where(fn ($w) => $w->whereNull('obsolete_reason')
            ->orWhere('obsolete_reason', '!=', self::OBSOLETE_DINONAKTIFKAN));
    }

    /**
     * Tahap pengajuan nonaktif yang boleh DIPUTUS $user, beserta batas
     * departemennya (null = lintas departemen).
     *
     * SATU sumber untuk tiga pemakai: penyaring daftar (scope di bawah),
     * penjaga aksi (metode di bawahnya), dan lencana antrean di sidebar.
     *
     * PJO dikenali lewat PERAN, bukan izin `document.approve` saja: izin itu
     * juga dipegang Admin, dan tahap terakhir memang milik PJO.
     *
     * @return array<string, int|null>
     */
    public static function tahapNonaktifUntuk(User $user): array
    {
        $tahap = [];

        if ($user->can('document.review')) {
            $tahap['sh'] = $user->department_id;
        }
        if ($user->can('document.review_md')) {
            $tahap['md'] = null;
        }
        if ($user->can('document.approve') && $user->hasRole(Roles::ROLE_PIMPINAN)) {
            $tahap['pjo'] = null;
        }

        return $tahap;
    }

    /** Dokumen yang menunggu KEPUTUSAN NONAKTIF dari $user. */
    public function scopeMenungguNonaktif(Builder $query, User $user): Builder
    {
        $tahap = self::tahapNonaktifUntuk($user);

        return $query->where('status', 'menunggu_nonaktif')
            ->where(function (Builder $w) use ($tahap) {
                foreach ($tahap as $nama => $deptId) {
                    $w->orWhere(fn (Builder $x) => $x->where('nonaktif_tahap', $nama)
                        ->when($deptId !== null, fn (Builder $y) => $y->where('department_id', $deptId)));
                }
                // Tanpa satu pun wewenang: daftar KOSONG, bukan daftar penuh.
                if ($tahap === []) {
                    $w->whereRaw('1 = 0');
                }
            });
    }

    /** Padanan {@see scopeMenungguNonaktif} untuk satu dokumen yang sudah dimuat. */
    public function bisaMemutuskanNonaktif(User $user): bool
    {
        $tahap = self::tahapNonaktifUntuk($user);

        return $this->status === 'menunggu_nonaktif'
            && array_key_exists((string) $this->nonaktif_tahap, $tahap)
            && (($dept = $tahap[$this->nonaktif_tahap]) === null || $dept === $this->department_id);
    }

    /**
     * Urutan tahap nonaktif dokumen ini (F1).
     *
     * Tahap MD DILEWATI bila MD sendiri yang mengajukan — tak ada gunanya
     * meminta seseorang menyetujui pengajuannya sendiri. Pengaju dikenali lewat
     * PERAN: Admin memegang seluruh izin, dan pengajuan Admin tetap harus
     * melewati MD.
     *
     * @return array<int, string>
     */
    public function tahapanNonaktif(): array
    {
        return $this->nonaktifOleh?->hasRole(Roles::ROLE_MD)
            ? ['sh', 'pjo']
            : ['sh', 'md', 'pjo'];
    }

    /** Tahap SESUDAH tahap sekarang; null = ini tahap terakhir. */
    public function tahapNonaktifBerikut(): ?string
    {
        $urutan = $this->tahapanNonaktif();
        $kini = array_search($this->nonaktif_tahap, $urutan, true);

        return $kini === false ? null : ($urutan[$kini + 1] ?? null);
    }

    /**
     * PAGAR DEPARTEMEN kanal mobile (PLAN-MOBILE-v6 §1.2).
     *
     * Non-Staff, GL, dan SH/DH terkunci di departemennya sendiri; yang lintas
     * departemen hanya pemegang `document.view_all` (PJO / Admin / MD) — kunci
     * yang sama persis dengan `lintas_departemen` di UserResource.
     *
     * Ditaruh di model, bukan disalin ke tiga controller, karena ketiga pintu
     * (daftar, detail, berkas PDF) HARUS sepakat. Menambal salah satu saja
     * menghasilkan gejala yang tak terlihat dari kursi pengembang: daftarnya
     * bersih, tapi PDF-nya tetap terbuka.
     */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        return $query->when(
            ! $user->can('document.view_all'),
            fn ($q) => $q->where('department_id', $user->department_id),
        );
    }

    /** Padanan {@see scopeTerlihatOleh} untuk satu dokumen yang sudah dimuat. */
    public function terlihatOleh(User $user): bool
    {
        return $user->can('document.view_all')
            || $this->department_id === $user->department_id;
    }

    /**
     * Apakah dokumen ini wajib melewati peninjauan Management Development
     * sebelum masuk ke PJO?
     *
     * MD memeriksa sistematika PENULISAN sesudah SH/DH memeriksa SUBSTANSI, dan
     * perannya MENJEMBATANI menuju PJO — jadi hanya masuk akal bagi jenis
     * dokumen yang persetujuannya memang di PJO. Daftarnya di
     * `config/smartpro.php`; menambah IK/SP/JSA adalah keputusan pimpinan.
     *
     * Berlaku untuk seluruh departemen.
     */
    public function perluTinjauanMd(): bool
    {
        $wajib = array_map('strtoupper', config('smartpro.md.jenis_wajib', []));

        return in_array(strtoupper((string) $this->type?->code), $wajib, true);
    }

    /**
     * Dokumen yang SEDANG BERLAKU.
     *
     * Termasuk `sedang_direvisi`: dokumen yang revisinya sedang disusun TETAP
     * berlaku sampai versi barunya disahkan — kalau tidak, departemen mendadak
     * kehilangan acuan kerja begitu seseorang menekan "Ajukan Revisi".
     *
     * Termasuk pula `menunggu_nonaktif` (Fase F), dengan alasan yang SAMA
     * PERSIS: pengajuan nonaktif bisa berjalan berhari-hari melewati tiga
     * tahap, dan bisa ditolak. Sampai keputusan terakhir jatuh, dokumennya
     * masih acuan kerja yang sah — dan nomornya masih ia pegang.
     *
     * Dipusatkan di sini supaya halaman web "Dokumen Berlaku" dan API mobile
     * mustahil memakai definisi yang berbeda.
     */
    public function scopeBerlaku(Builder $query): Builder
    {
        return $query->whereIn('status', ['published', 'sedang_direvisi', 'menunggu_nonaktif']);
    }

    /**
     * Kolom yang boleh diurutkan dari tautan judul tabel → ungkapan SQL-nya.
     *
     * Nilainya LARIK ungkapan, bukan satu string berkoma: `COALESCE(a, b)`
     * memuat komanya sendiri, dan memecah string pada koma memotongnya di
     * tengah fungsi.
     */
    public const KOLOM_URUT = [
        // Nomor yang DITAMPILKAN, bukan salah satu kolomnya: pra-terbit memakai
        // `doc_number`, yang sudah Berlaku memakai `doc_number_final`. Urutan
        // teks = urutan angka karena nomor urutnya berpad nol (`-01`, `-02`).
        'nomor' => ['COALESCE(doc_number_final, doc_number)'],
        // Edisi DULU baru revisi: Edisi 2 Rev 0 lebih baru daripada Edisi 1 Rev 4,
        // jadi mengurutkan `no_revisi` sendirian membalik dua dokumen itu.
        'revisi' => ['CAST(edisi AS UNSIGNED)', 'no_revisi'],
    ];

    /**
     * Urutkan daftar dokumen dari tautan judul kolom.
     *
     * Nama kolomnya TIDAK pernah masuk ke SQL apa adanya — hanya dipakai sebagai
     * kunci ke {@see KOLOM_URUT}. Nilai asing dari query string karena itu jatuh
     * ke urutan bawaan halaman masing-masing, bukan menjadi celah injeksi.
     *
     * `reorder()` WAJIB: tiap halaman sudah memasang urutan bawaannya sendiri
     * (`latest('published_at')`, `latest('updated_at')`, …) SEBELUM scope ini
     * dipanggil. Tanpa membuangnya, urutan pilihan pengguna cuma jadi kunci
     * KEDUA — dan baru terlihat pada dokumen yang cap waktunya kebetulan sama
     * persis. Di layar, tautan judul kolomnya tampak sama sekali tak berfungsi.
     */
    public function scopeUrut(Builder $query, ?string $kolom, ?string $arah = null): Builder
    {
        if (! isset(self::KOLOM_URUT[(string) $kolom])) {
            return $query;
        }

        $query->reorder();

        $arah = strtolower((string) $arah) === 'desc' ? 'desc' : 'asc';

        foreach (self::KOLOM_URUT[$kolom] as $ungkapan) {
            $query->orderByRaw($ungkapan.' '.$arah);
        }

        return $query;
    }

    /**
     * Visibilitas "Status Dokumen" (v3.1 §3.3): dokumen yang dibuat siapa pun
     * di LEVEL JABATAN yang sama, dalam DEPARTEMEN yang sama. Dipakai di menu
     * Status Dokumen (Fase 6). Pimpinan (tanpa dept) → hanya dokumennya sendiri.
     */
    public function scopeCreatedBySameLevel(Builder $query, User $user): Builder
    {
        if (! $user->department_id) {
            return $query->where('created_by', $user->id);
        }

        return $query->whereIn('created_by', User::where('jabatan', $user->jabatan)
            ->where('department_id', $user->department_id)->pluck('id'));
    }

    /**
     * Visibilitas "Status Dokumen Staff" read-only untuk GL & Section Head
     * (v3.1 §3.3): dokumen milik STAFF di departemen tsb. Tanpa hak edit/kirim/hapus.
     */
    public function scopeCreatedByStaffOfDept(Builder $query, ?int $departmentId): Builder
    {
        return $query->whereIn('created_by', User::where('jabatan', User::JABATAN_STAFF)
            ->where('department_id', $departmentId)->pluck('id'));
    }

    /**
     * SATU baris dokumen untuk halaman daftar Inertia.
     *
     * Ada karena enam halaman menggambar baris yang sama (Dokumen Saya, Dokumen
     * Berlaku, Tidak Berlaku, Revisi, Status Dokumen Staff, dan riwayat versi di
     * dalam Tidak Berlaku), dan lima di antaranya dulu memanggil method model
     * DARI DALAM Blade — `displayNumber()`, `isArsip()`, `nomorLuarPola()`,
     * `bisaDisuntingArsipOleh()`. Method tak ikut terserialkan ke JSON, jadi
     * tanpa perataan ini keempatnya harus ditulis ulang di TSX; aturan yang
     * disalin adalah aturan yang suatu hari menyimpang (pakem P5).
     *
     * Yang TIDAK ikut, sengaja: model mentahnya. `documents` memang tak memuat
     * rahasia, tapi relasi `creator` memuat `password` + `remember_token` dan
     * props Inertia terbaca siapa pun lewat "view source" — kesalahan yang sudah
     * dua kali nyaris lolos (Fase 6 & 7). Yang dikirim hanya kolom yang memang
     * digambar layar.
     *
     * Kunci per halaman (masukan, boleh_revisi, versi, …) ditambahkan
     * controllernya masing-masing di atas larik ini.
     */
    public function barisDaftar(User $user): array
    {
        return [
            'id' => $this->id,
            'nomor' => $this->displayNumber(),
            'judul' => $this->title,
            'jenis' => $this->type?->code,
            'dept' => $this->department?->code,
            'status' => $this->status,
            'pembuat' => $this->creator?->name,
            'edisi' => $this->edisi ?? 1,
            'no_revisi' => (int) ($this->no_revisi ?? 0),
            // Dua lencana yang sengaja dipisah (dulu partials/_badge-nomor-lama):
            // "Arsip" = PDF unggahan apa adanya; "Nomor Lama" = nomornya di luar
            // pola {PREFIX}-{JENIS}-{DEPT}-{NN}, dan itu memang diterima.
            'arsip' => $this->isArsip(),
            'nomor_luar_pola' => $this->nomorLuarPola(),
            'boleh_edit_arsip' => $this->bisaDisuntingArsipOleh($user),
        ];
    }

    /** Content as an associative array keyed by section_key. */
    public function contentMap(): array
    {
        return $this->contents->pluck('value_json', 'section_key')->toArray();
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Nomor final bila sudah terbit, selain itu nomor sementara (v3.1 §5).
     *
     * Inilah yang membuat aturan tampil berjalan sendiri: `doc_number_final`
     * baru lahir saat dokumen disahkan, jadi seluruh layar pra-terbit —
     * "Dokumen Saya", "Status Dokumen Staff", "Status Dokumen Departemen",
     * antrean tinjau & persetujuan — otomatis menampilkan nomor SEMENTARA,
     * dan nomor fix hanya muncul pada dokumen yang sudah Berlaku.
     */
    public function displayNumber(): string
    {
        return $this->doc_number_final ?? $this->doc_number ?? '—';
    }

    public function hasFinalNumber(): bool
    {
        return filled($this->doc_number_final);
    }

    /**
     * Dokumen LAMA (arsip, butir 0)? — PDF yang diunggah apa adanya, bukan
     * disusun lewat wizard.
     *
     * Diturunkan dari `arsip_path` alih-alih disimpan sebagai penanda sendiri:
     * satu-satunya hal yang membedakan dokumen arsip dari dokumen SmartPro
     * adalah ia PUNYA berkas, jadi kolom itu sekaligus jawabannya. Konsekuensi
     * yang disengaja: dokumen arsip tak punya `contents`, `reviewer_id`, maupun
     * `approver_id` — setiap layar yang menampilkannya harus tahan ketiganya
     * kosong.
     */
    public function isArsip(): bool
    {
        return filled($this->arsip_path);
    }

    /**
     * PDF-nya berasal dari BERKAS YANG DIUNGGAH, bukan digenerate DomPDF?
     *
     * Bukan sekadar sinonim {@see isArsip()}. Jenis berkelas `unggahan`
     * (PX & FK) memang tak pernah punya bab, jadi jawabannya "ya" bahkan
     * SEBELUM berkasnya diunggah — dan justru keadaan itulah yang penting:
     * dengan `isArsip()` saja, baris PX yang berkasnya belum menyusul jatuh ke
     * DomPDF dan HP menerima PDF KOSONG berkop, bukan pesan "belum diunggah".
     * Kegagalan diam-diam seperti itu tak akan pernah terlihat dari kantor.
     */
    public function dariBerkasUnggahan(): bool
    {
        return $this->isArsip() || $this->type?->class === 'unggahan';
    }

    /**
     * Nomornya di luar pola resmi `{PREFIX}-{JENIS}-{DEPT}-{NN}`?
     *
     * Dokumen lama dibawa masuk dengan nomor apa adanya — memaksanya ke pola
     * baru akan memutus rujukan yang sudah beredar di lapangan (audit, dokumen
     * lain, cetakan). Yang bisa dilakukan hanyalah MENANDAINYA, dan itu tugas
     * method ini: dipakai badge "Nomor Lama" di daftar & halaman detail.
     *
     * PREFIX-nya dibaca dari setelan yang SAMA dengan pembangkit nomor
     * (PLAN-AKSES-v8 Fase 5a). Dulu ia menghardcode `PPA-ADRO`, dan itu jebakan
     * yang menunggu: begitu Admin mengganti prefix site, SETIAP dokumen yang
     * baru dibuat akan langsung dicap "Nomor Lama" — persis kebalikan dari
     * artinya. Dua tempat membaca satu setelan, jadi keduanya mustahil
     * berselisih.
     */
    public function nomorLuarPola(): bool
    {
        $prefix = preg_quote(Pengaturan::prefix(), '/');

        return ! preg_match('/^'.$prefix.'-[A-Z]+-[A-Z0-9\-]+-\d{2}$/', (string) $this->displayNumber());
    }

    /**
     * Bolehkah $user memperbaiki metadata & berkas dokumen arsip ini?
     *
     * Pengunggahnya (atas departemennya sendiri) + Admin — cerminan persis dari
     * siapa yang boleh MENGUNGGAH (keputusan pemilik B7: GL dan Admin). Yang
     * salah ketik memperbaiki miliknya sendiri; Admin menambal sisanya lintas 7
     * departemen.
     *
     * Dipusatkan di sini karena tiga tempat memakainya: tombol di "Dokumen
     * Saya", tombol di "Dokumen Berlaku", dan penjaga di DocumentArsipController.
     * Tombol yang tampil lalu berakhir 403 adalah gejala aturan yang disalin.
     */
    public function bisaDisuntingArsipOleh(User $user): bool
    {
        if (! $this->isArsip()) {
            return false;
        }

        return $user->can('user.manage')
            || ($this->created_by === $user->id && $this->department_id === $user->department_id);
    }

    /** Draft revisi Tipe B? (menunjuk dokumen Berlaku yang direvisinya) */
    public function isRevisionDraft(): bool
    {
        return $this->revises_document_id !== null;
    }

    /**
     * Pakai lembar CATATAN REVISI + langkah wizard ekstra "Log Revisi"?
     *
     * Hanya draft revisi Tipe B, dan JENISNYA BUKAN JSA: atas permintaan pemilik,
     * formulir JSA tidak lagi memakai lembar catatan revisi di halaman depan,
     * sehingga wizard revisi JSA berhenti di langkah terakhir schema (tanpa
     * langkah ke-3). Alur revisinya sendiri TIDAK berubah.
     *
     * Berkas UNGGAHAN juga dikecualikan (Fase H): dokumen yang isinya berupa PDF
     * tak punya bab untuk disunting, jadi langkah wizardnya tak pernah bisa
     * dibuka. Hari ini DocumentController::edit() memulangkan yang ber-arsip_path
     * lebih dulu, TAPI baris FK/PX yang berkasnya belum menyusul (arsip_path
     * NULL) lolos dari penjagaan itu — dan lembar revisinya diisi ke dokumen
     * yang isinya tak pernah dicetak SmartPro.
     */
    public function usesRevisionLog(): bool
    {
        return $this->isRevisionDraft()
            && ($this->type?->code !== 'JSA')
            && ! $this->dariBerkasUnggahan();
    }

    /**
     * Jenisnya mengenal lembar CATATAN REVISI? (SOP/SP/IK — bukan JSA, bukan FK/PX)
     *
     * Dipakai jalur ARSIP (Fase H) untuk memutuskan apakah dokumen lama yang
     * baru diunggah singgah dulu di halaman Catatan Revisi. Terpisah dari
     * {@see usesRevisionLog()} karena pertanyaannya berbeda: yang ini soal
     * JENIS, yang itu soal dokumen ini — dan dokumen arsip selalu dijawab
     * "tidak" oleh yang itu.
     */
    public function jenisBerlembarRevisi(): bool
    {
        return $this->type?->class === 'inti' && $this->type?->code !== 'JSA';
    }

    /**
     * Edisi & No. Revisi yang akan dipikul dokumen ini BEGITU DIKIRIM (butir 7a).
     *
     * Nomor revisi dulu naik pada detik "Ajukan Revisi" ditekan — sebelum
     * pembuat menyentuh apa pun. Keputusan pemilik C1: naik saat draft revisi
     * DIKIRIM. Karena itu angkanya tak lagi tersimpan di kolom selama draft
     * disusun; ia DIHITUNG dari sini, dan satu tempat inilah yang dipakai
     * bersama oleh pengiriman, keterangan di wizard, dan tombol isi-otomatis
     * lembar Catatan Revisi — supaya angka yang dijanjikan di layar tak mungkin
     * berbeda dari angka yang akhirnya tersimpan.
     *
     * Naik HANYA bila draft masih memikul nomor versi yang direvisinya. Itulah
     * penjaga untuk ketiga jalan yang melewati pengiriman lebih dari sekali:
     * kirim ulang sesudah ditolak (Tipe A), tarik lalu kirim lagi, dan override
     * manual Edisi/Revisi di langkah Log Revisi. Tanpa penjaga itu, satu draft
     * revisi bisa naik dua-tiga tingkat tanpa satu pun revisi tambahan.
     *
     * @return array{0:int,1:int} [edisi, no_revisi]
     */
    public function revisiSaatKirim(): array
    {
        $edisi = max(1, (int) ($this->edisi ?: 1));
        $revisi = (int) $this->no_revisi;
        $lama = $this->revisesDocument;

        if ($lama && $edisi === max(1, (int) ($lama->edisi ?: 1)) && $revisi === (int) $lama->no_revisi) {
            return DocumentService::nextEditionRevision($edisi, $revisi);
        }

        return [$edisi, $revisi];
    }
}
