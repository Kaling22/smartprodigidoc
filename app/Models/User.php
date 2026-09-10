<?php

namespace App\Models;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // HasApiTokens: token Sanctum untuk aplikasi mobile. Sisi web TIDAK memakainya
    // (tetap sesi cookie), jadi penambahan ini tak mengubah perilaku web sedikit pun.
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /*
    | Jabatan (structural identity, PRD v2 §2.1). Penentu alur persetujuan.
    |
    | KENAPA kunci 'staff' BERLABEL "Non-Staff" — dan kenapa itu BENAR, bukan
    | tambal-sulam yang menunggu dirapikan:
    |
    | Di PT PPA, Group Leader, Section Head, dan Departemen Head ITULAH staff.
    | "Non-Staff" adalah orang di luar jajaran itu — teknisi, magang, helper.
    | Jadi konstanta ini menandai jabatan PALING BAWAH dalam alur: boleh membaca
    | dokumen departemennya dan mengirim masukan lapangan, tapi tak menyusun,
    | tak meninjau, tak menyetujui.
    |
    | Konsekuensinya, JANGAN "membetulkan" istilah `staff` di tempat lain menjadi
    | non-staff. Menu "Status Dokumen Staff" (dokumen buatan staff, dilihat
    | SH/DH) memakai kata itu dengan arti yang tepat; menggantinya justru akan
    | membalik maknanya. Yang boleh disebut Non-Staff hanyalah pemegang jabatan
    | INI — dan itu urusan LABEL, ditangani JABATAN_LABELS/JABATAN_SHORT di bawah.
    |
    | Kunci internalnya sendiri tetap 'staff': ia tersimpan di kolom
    | `users.jabatan`, jadi nama role spatie, dan ikut terkirim apa adanya ke
    | aplikasi mobile lewat UserResource::$role. Menggantinya berarti migrasi
    | data + memecah kontrak yang sudah dibaca aplikasi terpasang — biaya besar
    | tanpa satu pun perbaikan yang terlihat pengguna.
    */
    public const JABATAN_STAFF = 'staff';
    public const JABATAN_GROUP_LEADER = 'group_leader';
    public const JABATAN_SECTION_HEAD = 'section_head';
    public const JABATAN_DEPARTEMEN_HEAD = 'departemen_head';
    public const JABATAN_PIMPINAN = 'pimpinan';

    public const JABATAN_LABELS = [
        self::JABATAN_STAFF => 'Non-Staff',
        self::JABATAN_GROUP_LEADER => 'Group Leader',
        self::JABATAN_SECTION_HEAD => 'Section Head',
        self::JABATAN_DEPARTEMEN_HEAD => 'Departemen Head',
        self::JABATAN_PIMPINAN => 'Pimpinan',
    ];

    /**
     * Label PERAN spatie untuk layar — satu sumber, dua bentuk.
     *
     * Berbeda dari JABATAN_LABELS di atas: itu memetakan `users.jabatan` (lima
     * jabatan alur dokumen), ini memetakan nama role spatie (tujuh — Admin IT &
     * Management Development memang tak punya jabatan alur, lihat
     * UserManagementController::turunanPeran()).
     *
     * Dulu peta ini tersalin di TIGA Blade sekaligus (`users/index`,
     * `users/create`, `users/edit`) — dan komentar di `users/edit` sendiri sudah
     * mencatat kekhawatirannya: "kalau suatu saat daftarnya bertambah, keduanya
     * harus ikut". Sejak halaman-halamannya jadi TSX, salinan keempat akan
     * duduk di klien, tempat CLAUDE.md §4 melarangnya. Jadi dipusatkan di sini
     * dan dikirim sebagai props.
     *
     * DUA peta, bukan satu yang disambung: daftar user memakai nama PENDEK
     * (muat di lencana kolom sempit), sedangkan dropdown pemilihan peran
     * memakai kalimat penjelas — dan menyambung keduanya dengan tanda pisah
     * menghasilkan bunyi yang tak sama dengan yang sudah dibaca pengguna.
     */
    public const ROLE_LABELS = [
        'admin_it' => 'Admin IT',
        'pimpinan' => 'Pimpinan',
        'section_head' => 'Section Head',
        'departemen_head' => 'Departemen Head',
        'group_leader' => 'Group Leader',
        // Kunci perannya tetap `staff`; di layar jabatan ini bernama "Non-Staff".
        self::JABATAN_STAFF => 'Non-Staff',
        'management_development' => 'Management Development',
    ];

    /** Bentuk panjang — isi dropdown "Jabatan / Peran" saat membuat & mengubah akun. */
    public const ROLE_DESKRIPSI = [
        'admin_it' => 'Admin IT — wewenang penuh',
        'pimpinan' => 'Pimpinan (PJO) — approver final',
        'section_head' => 'Section Head — peninjau & approver',
        'departemen_head' => 'Departemen Head — peninjau & approver',
        'group_leader' => 'Group Leader — satu-satunya pembuat',
        self::JABATAN_STAFF => 'Non-Staff — read-only (teknisi, helper, magang)',
        'management_development' => 'Management Development — peninjau tahap kedua',
    ];

    /** Singkatan jabatan untuk kolom sempit di layar ("GL ICTMD"). */
    public const JABATAN_SHORT = [
        self::JABATAN_STAFF => 'Non-Staff',
        self::JABATAN_GROUP_LEADER => 'GL',
        self::JABATAN_SECTION_HEAD => 'SH',
        self::JABATAN_DEPARTEMEN_HEAD => 'DH',
        self::JABATAN_PIMPINAN => 'PJO',
    ];

    protected $fillable = [
        'name',
        'username',
        'nrp',
        'jabatan',
        'jabatan_diajukan',
        'nomor_hp',
        'email',
        'photo_path',
        'password',
        'department_id',
        'status',
        // Saklar bantuan AI pada peninjauan Management Development. WAJIB ada di
        // sini: tanpa masuk fillable, update() mengabaikannya DIAM-DIAM — saklarnya
        // tampak tertekan di layar tapi nilainya tak pernah tersimpan.
        'ai_review_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Profil akses — jenis dokumen yang boleh disusun (PLAN-AKSES-v7 Fase 4).
     *
     * NULL = Tanpa Akses. Itu nilai bawaan yang DISENGAJA, bukan data yang
     * belum terisi: lihat migration create_access_profiles_table.
     */
    public function accessProfile(): BelongsTo
    {
        return $this->belongsTo(AccessProfile::class);
    }

    /** URL foto profil (avatar) bila ada, jika tidak null. */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? \Illuminate\Support\Facades\Storage::url($this->photo_path) : null;
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    /** Rentang cuti / off day / dinas luar (FITUR-BARU-v4 §6). */
    public function offDays(): HasMany
    {
        return $this->hasMany(UserOffDay::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Satu baris user untuk LAYAR ADMIN — daftar user, akun MD, dan persetujuan
     * pendaftaran.
     *
     * KENAPA ADA: ketiga tabel itu dulu memanggil `getRoleNames()->first()`,
     * `jabatanLabel()`, `isActive()`, dan `$u->department->code` langsung di
     * dalam Blade. Model mentah tak boleh dikirim ke klien (ikut membocorkan
     * `password` & `remember_token`, lihat UserResource), dan menyalin
     * pemetaannya ke tiga controller hanya melahirkan tiga bentuk yang suatu
     * hari berbeda.
     *
     * SATU bentuk untuk ketiganya, bukan tiga yang dipangkas pas-pasan: yang
     * dihemat dengan memangkas cuma beberapa kunci per baris, sedangkan yang
     * dibayar adalah tiga daftar yang harus diingat bersama.
     *
     * `roles` dan `department` WAJIB sudah di-eager-load pemanggilnya —
     * kalau tidak, tiap baris menembakkan dua query sendiri.
     *
     * @return array<string, mixed>
     */
    public function barisAdmin(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nrp' => $this->nrp,
            'nomor_hp' => $this->nomor_hp,
            'email' => $this->email,
            'dept' => $this->department?->code,
            'role' => $this->getRoleNames()->first(),
            // Kunci mentah `jabatan` sengaja TIDAK ikut — yang tampil di layar
            // selalu labelnya (CLAUDE.md §6, dijaga LabelJabatanTest).
            'jabatan_label' => $this->jabatanLabel(),
            'jabatan_diajukan' => $this->jabatan_diajukan,
            'status' => $this->status,
            'aktif' => $this->isActive(),
            'ai' => (bool) $this->ai_review_enabled,
        ];
    }

    /**
     * Akun peninjau Management Development yang aktif — SASARAN NOTIFIKASI.
     *
     * Sengaja memilih lewat PERAN, bukan izin `document.review_md`. Admin
     * memegang SELURUH izin, jadi pemilihan berbasis izin ikut menjaring Admin
     * dan membuatnya dibanjiri notifikasi tiap ada SOP — bukan itu maksudnya.
     *
     * Pembagiannya: **hak akses** halaman MD tetap dijaga izin (fleksibel, bisa
     * ditempelkan ke unit mana pun kelak), sedangkan **sasaran notifikasi**
     * memakai peran (tepat sasaran).
     *
     * Biasanya hanya berisi SATU akun bersama, dipakai siapa pun di departemen
     * itu. Tetap dikembalikan sebagai koleksi supaya menambah akun kedua kelak
     * tak menuntut perubahan kode.
     */
    public function scopePeninjauMd(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->where('name', 'management_development'));
    }

    /**
     * Orang yang dihitung sebagai SASARAN distribusi: pengguna aktif, tanpa Admin IT.
     *
     * Admin IT dikecualikan karena ia tak pernah menjadi tujuan distribusi dokumen
     * mutu — ia mengurus sistemnya, bukan menjalankan prosedurnya. Menghitungnya
     * sebagai sasaran membuat setiap departemen tampak tertinggal satu orang
     * selamanya, dan tak ada satu pun gejala yang memperlihatkan sebabnya.
     *
     * SATU scope untuk kedua service distribusi (dokumen mutu & informasi):
     * dua penyaring Admin yang ditulis terpisah adalah dua penyaring yang suatu
     * hari tak lagi sepakat, dan ketidaksepakatannya hanya terlihat sebagai
     * persentase yang berbeda di dua halaman.
     */
    public function scopeSasaranDistribusi(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', RolePermissionSeeder::ROLE_ADMIN));
    }

    /** Label jabatan yang layak dibaca manusia (bukan kunci internal). */
    public function jabatanLabel(): string
    {
        return self::JABATAN_LABELS[$this->jabatan] ?? (string) $this->jabatan;
    }

    /**
     * Nama jabatan gaya dokumen resmi. Sama dengan {@see jabatanLabel()}, kecuali
     * `pimpinan` yang di dokumen SELALU ditulis "PJO" (CLAUDE.md §6 menyebutnya
     * "pimpinan/PJO"). Dipisah agar tampilan lain tetap memakai label biasa.
     */
    private function jabatanFormalLabel(): string
    {
        return $this->jabatan === self::JABATAN_PIMPINAN ? 'PJO' : $this->jabatanLabel();
    }

    /**
     * "Group Leader (ICTMD)" — dipakai di kolom JABATAN halaman pengesahan
     * SOP/SP/IK dan baris "Jabatan :" pada blok TTD JSA.
     * PJO tidak punya departemen → cukup "PJO", tanpa kurung kosong.
     */
    public function jabatanWithDepartment(): string
    {
        $jabatan = $this->jabatanFormalLabel();
        $dept = $this->department?->code;

        return $dept ? "{$jabatan} ({$dept})" : $jabatan;
    }

    /** "GL ICTMD" — bentuk ringkas untuk kolom sempit di layar. */
    public function jabatanShort(): string
    {
        $jabatan = self::JABATAN_SHORT[$this->jabatan] ?? $this->jabatanFormalLabel();
        $dept = $this->department?->code;

        return $dept ? "{$jabatan} {$dept}" : $jabatan;
    }

    /**
     * Boleh MEMBUAT dokumen jenis ini? (PLAN-AKSES-v7 Fase 4, butir 3)
     *
     * Tiga lapis, urutannya penting:
     *   1. izin `document.create` — pagar lama, tak dicabut sedikit pun;
     *   2. Admin IT lolos penuh (CLAUDE.md §6) — kalau tidak, satu profil yang
     *      salah sunting bisa mengunci orang yang seharusnya membetulkannya;
     *   3. sisanya (= GL) harus punya profil yang MENYEBUT jenis itu.
     *
     * Tanpa profil = tanpa wewenang, bukan "boleh semuanya". Konsekuensinya
     * disengaja & disetujui pemilik: saat rilis SELURUH GL kehilangan wewenang
     * menyusun sampai Admin menetapkan profilnya.
     */
    public function bolehBuatJenis(?string $code): bool
    {
        if (! $this->can('document.create')) {
            return false;
        }

        if ($this->can('user.manage')) {
            return true;
        }

        return $code !== null && in_array(
            strtoupper($code),
            $this->accessProfile?->jenis_dibolehkan ?? [],
            true
        );
    }

    /**
     * Jenis PERTAMA yang boleh disusunnya, atau null bila tak satu pun.
     *
     * Untuk tombol pintasan "Dokumen Baru" di dashboard & daftar dokumen.
     * Keduanya dulu menembak `type=SOP` mati — sesudah profil akses berlaku,
     * itu berarti tombol yang tampil lalu berakhir 403 bagi GL yang justru
     * berwenang menyusun IK. Mengembalikan KODE, bukan boolean, supaya
     * pemanggilnya sekaligus tahu ke mana harus menaut.
     *
     * Urutannya urutan DocumentType::kode() — urutan tampil di seluruh
     * antarmuka, jadi yang terpilih selalu jenis "paling depan" yang ia punya.
     */
    public function jenisPertamaBoleh(): ?string
    {
        foreach (DocumentType::kode() as $code) {
            if ($this->bolehBuatJenis($code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Boleh meninjau dokumen JSA (dan HANYA JSA)?
     *
     * Lapis 1 — izin `document.review_jsa` (dipegang role Group Leader).
     * Lapis 2 — SHE **atau** profil akses. Izin menempel ke PERAN, jadi semua
     * GL memilikinya; batas ini tak bisa datang dari izin dan harus dicek di
     * sini.
     *
     * PLANT dicabut di Fase 3 (keputusan pemilik butir 2): yang menentukan
     * kelayakan meninjau JSA adalah KOMPETENSI K3 orangnya, bukan papan nama
     * departemennya — dan "SHE + Plant" adalah pendekatan departemen. SHE
     * tetap OTOMATIS karena K3 memang tugas pokoknya; GL mana pun (termasuk
     * Plant) mendapatkannya kembali lewat profil akses, satu per satu, atas
     * penetapan Admin.
     *
     * Peninjauan per jenis dokumen tetap dijaga ReviewController; siapa yang
     * boleh DIPILIH sebagai peninjau ditentukan DocumentParticipantResolver.
     */
    public function canReviewJsa(): bool
    {
        return $this->can('document.review_jsa')
            && ($this->department?->code === 'SHE'
                || (bool) $this->accessProfile?->boleh_review_jsa);
    }

    /** "Angga Margi Saputro - GL ICTMD" — satu baris untuk daftar & dropdown. */
    public function nameWithJabatan(): string
    {
        return $this->name.' - '.$this->jabatanShort();
    }

    /**
     * Boleh menyetujui pendaftaran akun dari SELURUH departemen?
     *
     * Admin IT (user.manage) dan PJO. PJO sengaja dikenali lewat PERANnya,
     * bukan lewat departemen: PJO memang TIDAK punya departemen (CLAUDE.md §6),
     * sehingga penyaringan `department_id` selalu menghasilkan daftar KOSONG.
     * Itulah sebabnya dulu PJO tak pernah bisa menyetujui akun meski sudah
     * memegang izin `user.approve_registration`.
     */
    /**
     * Berhak atas dashboard LENGKAP (CLAUDE.md v4 §6).
     *
     * Non-Staff satu-satunya peran terbatas; GL, SH, DH, PJO, Admin, dan MD
     * mendapat seluruh widget. Dipusatkan di sini karena aturannya dulu ditulis
     * ulang di empat tempat dengan bunyi berbeda-beda, dan Admin/MD — yang
     * `jabatan`-nya NULL — diam-diam jatuh ke cabang `else` di beberapa di
     * antaranya lalu kehilangan widget tanpa ada yang meniatkannya.
     */
    public function dashboardPenuh(): bool
    {
        return $this->jabatan !== self::JABATAN_STAFF;
    }

    /**
     * Boleh membuka halaman "Distribusi" (siapa sudah membaca dokumen mana)?
     *
     * SH/DH (departemennya) plus pemegang `document.view_all` (PJO, Admin, MD).
     * GL sengaja TIDAK: ia mendapat KARTU-nya di dashboard — cakupan rendah
     * memberitahunya tulisannya belum sampai — tapi tak berwenang menegur
     * siapa pun, jadi halaman penuhnya cuma bising baginya.
     *
     * Dulu ini ditulis sebagai `can('document.request_revision')` di empat
     * tempat. Sejak PLAN-REVISI-v6 Fase C izin itu berpindah ke GL & MD,
     * sehingga rumus lama justru MENCABUT menunya dari SH/DH & PJO — pihak yang
     * satu-satunya berkepentingan menegur cakupan rendah. Karena itu aturannya
     * dinyatakan langsung di sini, bukan dititipkan pada izin yang artinya lain.
     */
    public function bisaLihatDistribusi(): bool
    {
        return $this->can('document.view_all')
            || in_array($this->jabatan, [self::JABATAN_SECTION_HEAD, self::JABATAN_DEPARTEMEN_HEAD], true);
    }

    /**
     * Boleh membaca dokumen Tidak Berlaku (arsip) dari aplikasi mobile?
     *
     * GL / SH / DH / PJO boleh; Non-Staff TIDAK. Alasannya bukan kerahasiaan
     * melainkan keselamatan kerja: dokumen obsolete tak boleh pernah jadi acuan
     * orang di lapangan, dan cara paling pasti menjaminnya adalah tak pernah
     * menampilkannya kepada mereka.
     *
     * Aturannya kebetulan sama bunyinya dengan dashboardPenuh(), TAPI sengaja
     * ditulis terpisah — yang satu soal kelengkapan widget, yang ini soal isi
     * dokumen; menyatukannya berarti mengubah salah satunya kelak ikut mengubah
     * yang lain tanpa ada yang meniatkannya.
     */
    public function bisaLihatArsip(): bool
    {
        return $this->jabatan !== self::JABATAN_STAFF;
    }

    /**
     * Boleh mencatat ketersediaan diri (cuti / off day / dinas luar)?
     *
     * Non-Staff TIDAK (PLAN-MOBILE-v6 §2.1). Off day di SmartPro bukan cuti
     * kepegawaian — itu urusan HCGA — melainkan sinyal penjadwalan yang satu-
     * satunya pembacanya adalah {@see \App\Services\ReviewerAvailability}:
     * "siapa yang tak boleh ditunjuk sebagai peninjau minggu ini". Non-Staff
     * tak pernah ditunjuk meninjau apa pun, jadi catatannya tak pernah dibaca
     * siapa pun dan hanya membuat mereka mengira sudah mengajukan cuti.
     *
     * Dipakai kanal HP MAUPUN web: menutup menunya di HP saja bukan pembatasan,
     * hanya penyamaran.
     */
    public function bisaCatatKetersediaan(): bool
    {
        return $this->jabatan !== self::JABATAN_STAFF;
    }

    /**
     * Boleh MELAKSANAKAN pekerjaan mengacu JSA (checklist lapangan)?
     *
     * Non-Staff & GL saja (PLAN-MOBILE-v6 §2.3, ketetapan pemilik). SH/DH, PJO,
     * MD, dan Admin MELIHAT pekerjaan departemennya — itu urusan pengawasan —
     * tapi tak pernah menjadi pelaksananya.
     *
     * Kebalikannya {@see bisaCatatKetersediaan()}, dan itu memang disengaja:
     * yang turun ke lapangan mencentang pengendalian, yang mengawasi mencatat
     * ketersediaan sebagai peninjau.
     */
    public function bisaKerjaJsa(): bool
    {
        return in_array($this->jabatan, [
            self::JABATAN_STAFF,
            self::JABATAN_GROUP_LEADER,
        ], true);
    }

    /**
     * Seberapa lebar daftar "tim" yang boleh dibaca orang ini dari HP —
     * kotak masuk Masukan Dokumen (§2.2) dan Riwayat Pekerjaan (§2.3).
     *
     *   `sendiri`    → Non-Staff: hanya miliknya.
     *   `departemen` → GL / SH / DH: seluruh orang di departemennya.
     *   `semua`      → pemegang `document.view_all` (PJO / Admin / MD).
     *
     * Yang dipusatkan di sini KEPUTUSANNYA, bukan query-nya: kedua daftar itu
     * menyambung ke departemen lewat jalan yang berbeda (masukan lewat
     * departemen DOKUMENNYA, pekerjaan lewat departemen PELAKSANANYA), jadi
     * memaksakan satu scope untuk keduanya justru menyembunyikan bedanya.
     */
    public function lingkupTim(): string
    {
        return match (true) {
            $this->can('document.view_all') => 'semua',
            $this->jabatan === self::JABATAN_STAFF => 'sendiri',
            default => 'departemen',
        };
    }

    public function approvesAllDepartments(): bool
    {
        return $this->can('user.manage')
            || $this->hasRole(\Database\Seeders\RolePermissionSeeder::ROLE_PIMPINAN);
    }

    /**
     * Pendaftar berstatus `pending` yang boleh dilihat/ditindak oleh $actor.
     * Dipakai bersama oleh halaman Persetujuan Akun & kartu antrean dashboard
     * agar keduanya tak mungkin memakai aturan yang berbeda.
     */
    public function scopePendingVisibleTo(Builder $query, self $actor): Builder
    {
        return $query->where('status', 'pending')
            ->unless($actor->approvesAllDepartments(),
                fn (Builder $q) => $q->where('department_id', $actor->department_id));
    }
}
