/**
 * Kontrak props layar Pengaturan & Master Data (Fase 7) — cerminan
 * `PengaturanController` dan `MasterDataController`.
 *
 * Tak satu pun aturan hidup di berkas ini. Daftar penyedia AI datang dari
 * `SimpanAiRequest::PENYEDIA`, kolom kategori dari
 * `InformasiKategori::KOLOM_TERSEDIA`, rupa jenis dari `DocumentType::RUPA` —
 * ketiganya konstanta PHP yang dulu dibaca dari dalam Blade dan kini dikirim
 * sebagai props (pakem P3/P5).
 */

/* ============================ 5a — Penomoran ============================ */

export interface PenomoranProps {
    prefix: string;
    namaSite: string;
}

/* =========================== 5b — Master Data =========================== */

export interface BarisDepartemen {
    id: number;
    code: string;
    name: string;
    alias: string | null;
    documents_count: number;
    users_count: number;
    is_active: boolean;
}

export interface BarisJenis {
    id: number;
    code: string;
    name: string;
    /** Masih di dalam alur mutu — bukan "total dikurangi berlaku". */
    berjalan_count: number;
    berlaku_count: number;
    is_active: boolean;
}

export interface BarisKategori {
    id: number;
    /** Beku seumur hidup barisnya: `informasi.kategori` menyimpannya sebagai TEKS. */
    slug: string;
    nama: string;
    deskripsi: string | null;
    /** Nama kelas Bootstrap Icons; digambar lewat `components/Ikon.tsx`. */
    ikon: string;
    kolom: string[];
    is_active: boolean;
}

export interface MasterProps {
    departemen: BarisDepartemen[];
    jenis: BarisJenis[];
    kategori: BarisKategori[];
    /** slug → jumlah dokumen Informasi yang berlaku. */
    jumlahInformasi: Record<string, number>;
    /** `InformasiKategori::KOLOM_TERSEDIA` — kunci → label. */
    kolomTersedia: Record<string, string>;
    /** `DocumentType::RUPA` — kode → [nama ikon `bi-*`, warna hex]. */
    rupa: Record<string, [string, string]>;
}

/* ======================== 5c — Konfigurasi Sistem ======================== */

/**
 * Setelan AI TANPA kedua kunci API-nya.
 *
 * `Pengaturan::ai()` memuat `key` & `cadangan_key` dalam bentuk terbaca;
 * controller membuangnya sebelum props dirakit, sebab payload Inertia terbaca
 * siapa pun lewat "view source". Yang sampai ke layar hanya `keyTopeng`.
 */
export interface SetelanAi {
    enabled: boolean;
    provider: string | null;
    model: string | null;
    cadangan_provider: string | null;
    cadangan_model: string | null;
}

export interface PenyediaAiSehat {
    /** "Utama" / "Cadangan". */
    label: string;
    provider: string | null;
    model: string | null;
    /** `aktif` · `dimatikan` · `penyedia kosong` · `kunci kosong` · `model kosong`. */
    status: string;
}

export interface Kesehatan {
    php: string;
    laravel: string;
    zona: string;
    debug: boolean;
    lampiran_bytes: number;
    dokumen: number;
    dokumen_berlaku: number;
    /** Termasuk yang sudah di-soft-delete — angka yang dipikul tombol musnahkan. */
    dokumen_semua: number;
    pengguna: number;
    /** Keadaan kedua penyedia AI — kuncinya TIDAK pernah ikut, hanya statusnya. */
    ai: PenyediaAiSehat[];
    mailer: string;
    mail_host: string | null;
    mail_dari: string | null;
    mail_paksa_ke: string | null;
}

export interface SistemProps {
    ai: SetelanAi;
    keyTopeng: string | null;
    cadanganKeyTopeng: string | null;
    /** `SimpanAiRequest::PENYEDIA` — kunci → label. */
    penyedia: Record<string, string>;
    kesehatan: Kesehatan;
    /**
     * Saklar gabung-PDF dokumen lama (`Pengaturan::arsipGabungCoverAktif()`).
     * Aktif = potong halaman 1-2 berkas arsip, ganti dengan Cover+Catatan
     * Revisi; nonaktif = isi diketik ulang di wizard (alur lama).
     */
    arsipGabungAktif: boolean;
}
