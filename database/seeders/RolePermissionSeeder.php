<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC via spatie/laravel-permission (PRD v2 §2).
 *
 * Spatie roles mirror the four jabatan (staff, group_leader, section_head,
 * pimpinan) plus admin_it. The *functional* roles (Pembuat / Peninjau /
 * Approver) are contextual and resolved by Policies from jabatan + document
 * relationship — they are NOT stored as spatie roles.
 */
class RolePermissionSeeder extends Seeder
{
    public const ROLE_ADMIN = 'admin_it';
    public const ROLE_PIMPINAN = 'pimpinan';
    public const ROLE_SECTION_HEAD = 'section_head';
    public const ROLE_DEPARTEMEN_HEAD = 'departemen_head';
    public const ROLE_GROUP_LEADER = 'group_leader';
    public const ROLE_STAFF = 'staff'; // kunci internal tetap; label tampilan "Non-Staff"

    /**
     * Management Development — peninjau KEDUA (sistematika penulisan), sesudah
     * SH/DH dan sebelum PJO.
     *
     * Akunnya BERSAMA: satu akun dipakai siapa pun di departemen itu, jadi tak
     * ada urusan cuti/berhalangan. Konsekuensinya audit log mencatat "MD",
     * bukan nama perorangan (lihat docs/FITUR-BARU-v4.md §2).
     */
    public const ROLE_MD = 'management_development';

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'document.create', 'document.edit', 'document.submit', 'document.delete',
            'document.review', 'document.review_jsa', 'document.review_md',
            'document.approve', 'document.publish',
            'document.request_revision', 'document.change_status',
            // PLAN-REVISI-v6 Fase C: MEMBALAS masukan lapangan dipisah dari
            // MEREVISI dokumen. Dulu keduanya menumpang `request_revision`,
            // sehingga PJO — yang memegangnya demi menyetujui — ikut bisa
            // menutup masukan tujuh departemen yang tak pernah ia tindak.
            'document.feedback_respond',
            'document.view_department', 'document.view_scope', 'document.view_all',
            'user.manage', 'user.approve_registration', 'user.create_staff',
            'audit.view',
            // Menu Informasi (butir 3): MENGUNGGAH & MENGHAPUS. Membacanya
            // sengaja TIDAK berizin — [KUNCI] pemilik "diakses semua orang,
            // semua departemen, semua jabatan", jadi izin baca justru akan
            // mengunci Non-Staff di luar. Keputusan B2: pemegangnya Admin saja
            // dulu; menambahkan SH/DH kelak cukup satu baris di $roles.
            'informasi.manage',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Peran head (SH & DH) identik: bisa meninjau DAN menyetujui (v2 §Fase A).
        //
        // `document.request_revision` DICABUT dari SH/DH (Fase C, keputusan D1 &
        // D4): yang meninjau & menyetujui tak boleh sekaligus yang memulai
        // revisi. Mereka tetap MELIHAT masukan & alasan lewat menu Log Dokumen —
        // hak lihat itu digantung pada `dashboardPenuh()`, bukan pada izin ini.
        $headPerms = [
            'document.review', 'document.approve',
            'document.view_department', 'user.approve_registration', 'audit.view',
        ];

        $roles = [
            self::ROLE_ADMIN => $permissions, // full authority; every action audited (D11)
            // PJO = PENYETUJU saja (bisa menyetujui SEMUA dokumen); TIDAK meninjau.
            self::ROLE_PIMPINAN => [
                'document.approve', 'document.publish',
                'document.view_all', 'user.approve_registration', 'audit.view',
            ],
            self::ROLE_SECTION_HEAD => $headPerms,
            self::ROLE_DEPARTEMEN_HEAD => $headPerms, // wewenang sama dengan Section Head
            // GL = SATU-SATUNYA pembuat; TIDAK meninjau. Sejak Fase C (keputusan
            // D1) ia JUGA yang memulai revisi Tipe B & menonaktifkan dokumen —
            // dialah penyusunnya, jadi dialah yang tahu apa yang perlu diperbaiki.
            // Batasnya "hanya dokumen buatannya" TIDAK bisa dinyatakan izin
            // spatie (izin tak mengenal dokumen mana); itu dijaga
            // Document::bisaDirevisiOleh(). PENGECUALIAN (v3 rev): GL dept SHE & Plant
            // MENINJAU JSA dari departemen lain — peninjauan JSA bertumpu pada
            // kompetensi K3, bukan hierarki. Izinnya sengaja TERPISAH
            // (`document.review_jsa`, bukan `document.review`) agar batas "hanya
            // JSA" terbaca langsung dari namanya; batas "hanya SHE/Plant"
            // ditambahkan di User::canReviewJsa().
            self::ROLE_GROUP_LEADER => [
                'document.create', 'document.edit', 'document.submit', 'document.delete',
                'document.review_jsa',
                'document.request_revision', 'document.feedback_respond',
                // Informasi (butir 3): GL ikut mengunggah. Keputusan B2 semula
                // "Admin saja DULU"; pemilik membukanya ke GL setelah melihatnya
                // berjalan. Aman diberikan karena Informasi TIDAK punya alur
                // mutu — tak ada status yang bisa dilompati, tak ada persetujuan
                // yang bisa dilewati. SH/DH & PJO sengaja BELUM diberi.
                'informasi.manage',
                'document.view_department', 'user.approve_registration', 'audit.view',
            ],
            // MD = peninjau KEDUA, sistematika PENULISAN saja.
            //
            // Izinnya sengaja TERPISAH (`document.review_md`, bukan
            // `document.review`) — sama seperti pola `document.review_jsa` pada
            // GL SHE/Plant. Dengan begitu MD tak pernah bisa masuk ke antrean
            // peninjauan tahap pertama, dan batasnya terbaca langsung dari nama
            // izinnya tanpa perlu menelusuri kode.
            //
            // TIDAK diberi `document.approve`: MD meloloskan ke PJO, bukan
            // menyetujui. TIDAK diberi `document.create`: MD tidak menyusun.
            self::ROLE_MD => [
                'document.review_md',
                // Fase C (D1): MD merevisi & membalas masukan LINTAS 7 dept —
                // ia pemeriksa sistematika penulisan, jadi ialah yang menyapu
                // dokumen yang salah tulis di departemen mana pun. Lingkupnya
                // datang dari `document.view_all` di bawah, bukan dari jabatan
                // (MD tak punya jabatan).
                'document.request_revision', 'document.feedback_respond',
                'document.view_all',   // SOP dari 7 departemen melewatinya
                'audit.view',
            ],
            // Non-Staff = READ-ONLY: hanya melihat dokumen departemennya.
            self::ROLE_STAFF => [
                'document.view_department',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            Role::firstOrCreate(['name' => $roleName])->syncPermissions($perms);
        }

        $this->migrateLegacyRole();
    }

    /** Move any v1 `user_dept` users to `staff`, then drop the obsolete role. */
    private function migrateLegacyRole(): void
    {
        $legacy = Role::where('name', 'user_dept')->first();
        if (! $legacy) {
            return;
        }

        User::role('user_dept')->get()->each(fn (User $u) => $u->syncRoles([self::ROLE_STAFF]));
        $legacy->delete();
    }
}
