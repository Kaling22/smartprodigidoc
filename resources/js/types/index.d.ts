import type { Page, PageProps as InertiaPageProps } from '@inertiajs/core';

/**
 * Bentuk props global yang dikirim `HandleInertiaRequests::share()`.
 *
 * Satu berkas, dipakai seluruh halaman lewat `usePage<PageProps>()`. Kalau
 * kontrak di PHP berubah, yang berubah cuma berkas ini — bukan 78 halaman.
 */

export interface AuthUser {
    id: number;
    name: string;
    nrp: string;
    /** Kunci mentah. JANGAN pernah dicetak ke layar — pakai `jabatan_label`. */
    jabatan: string | null;
    jabatan_label: string;
    jabatan_short: string;
    department_id: number | null;
    photo_url: string | null;
}

export interface Departemen {
    id: number;
    code: string;
    name: string;
}

export interface Notifikasi {
    id: string;
    message: string;
    /** Nama kelas Bootstrap Icons; dipetakan ke lucide di `components/Ikon.tsx`. */
    icon: string;
    read_at: string | null;
    created_at: string;
}

/** Satu baris menu sidebar — datang jadi dari `App\Services\NavigasiSidebar`. */
export interface MenuItem {
    label: string;
    icon: string;
    /** null = menu induk (dropdown) atau item terkunci. */
    href: string | null;
    active: boolean;
    /** null bila nol: menu tanpa tugas tak menampilkan angka. */
    badge: number | null;
    title: string | null;
    /** Di luar profil akses / ditutup Admin: tampil, tapi mustahil diklik. */
    locked?: boolean;
    /** Titik penanda "ada pekerjaan di salah satu sub-menu". */
    dot?: boolean;
    items?: MenuItem[];
}

export interface MenuBagian {
    /** null = bagian tanpa judul. */
    label: string | null;
    items: MenuItem[];
}

/** `[warna, warnaGelap, ikon]` — apa adanya dari `Document::STATUS_META`. */
export type StatusMeta = Record<string, [string, string, string]>;

export interface PageProps extends InertiaPageProps {
    auth: {
        user: AuthUser | null;
        department: Departemen | null;
        roles: string[];
        can: Record<string, boolean>;
    };
    notifications: { unread: number; items: Notifikasi[] };
    flash: { success: string | null; error: string | null };
    navigation: MenuBagian[];
    /**
     * Isi modal "tugas menunggu" pasca-login; `null` bila tak ada flash
     * `antrean_awal` atau antreannya kosong (lihat HandleInertiaRequests).
     */
    antreanAwal: { label: string; icon: string; jumlah: number; url: string }[] | null;
    statusMeta: StatusMeta;
    statusLabels: Record<string, string>;
}

export type SmartProPage = Page<PageProps>;
