import type { Departemen } from '@/types';
import type { Paginator } from '@/components/v2/DataTable';

/**
 * Kontrak props halaman Pengguna & Akses (Fase 6) — cerminan
 * `UserManagementController`, `UserApprovalController`, `AccessProfileController`,
 * dan `PageController@account`.
 *
 * Tak satu pun aturan wewenang hidup di berkas ini. Daftar peran yang boleh
 * ditetapkan datang dari `StoreUserRequest::assignableRoles()`, labelnya dari
 * `User::ROLE_LABELS`/`ROLE_DESKRIPSI`, dan kandidat penetapan akses disaring
 * `AccessProfileController` — klien hanya menggambar apa yang dikirim (pakem P5).
 */

/**
 * Satu baris user di layar admin — bentuk jadi dari `User::barisAdmin()`.
 *
 * `jabatan` mentah SENGAJA tak ada di sini: yang tampil selalu `jabatan_label`
 * (CLAUDE.md §6, dijaga `LabelJabatanTest`).
 */
export interface BarisAdmin {
    id: number;
    name: string;
    nrp: string | null;
    nomor_hp: string | null;
    email: string | null;
    /** Kode departemen ("ICTMD"), `null` bagi Pimpinan & Admin. */
    dept: string | null;
    /** Nama role spatie — dipetakan lewat props `roleLabels`, jangan dicetak mentah. */
    role: string | null;
    jabatan_label: string;
    /** Jabatan yang DIKETIK pendaftar sendiri ("Magang"), bila ada. */
    jabatan_diajukan: string | null;
    status: string;
    aktif: boolean;
    /** Saklar bantuan AI — hanya bermakna untuk akun Management Development. */
    ai: boolean;
}

export interface UsersIndexProps {
    users: Paginator<BarisAdmin>;
    departments: Departemen[];
    filters: { q?: string; department_id?: string };
    akunMd: BarisAdmin[];
    roleLabels: Record<string, string>;
}

export interface UsersFormProps {
    departments: Departemen[];
    /** Peran yang boleh ditetapkan Admin — `StoreUserRequest::assignableRoles()`. */
    roles: string[];
    roleLabels: Record<string, string>;
}

export interface ProfilAkses {
    id: number;
    nama: string;
    keterangan: string | null;
    jenis_dibolehkan: string[];
    boleh_review_jsa: boolean;
    users_count: number;
}

export interface BarisGroupLeader {
    id: number;
    name: string;
    nrp: string | null;
    dept: string | null;
    access_profile_id: number | null;
}

export interface AksesIndexProps {
    profiles: ProfilAkses[];
    groupLeaders: Paginator<BarisGroupLeader>;
    departments: Departemen[];
    filters: { q?: string; department_id?: string; access_profile_id?: string };
    /** Kode jenis yang AKTIF — isi kotak centang profil. */
    jenis: string[];
    /** `DocumentType::RUPA` utuh: kode → [nama ikon `bi-*`, warna hex]. */
    rupa: Record<string, [string, string]>;
}

export interface AkunProps {
    user: {
        name: string;
        nrp: string | null;
        nomor_hp: string | null;
        email: string | null;
        status: string;
        photo_url: string | null;
        punya_foto: boolean;
        jabatan_label: string | null;
        departemen: string | null;
        peran: string;
    };
}
