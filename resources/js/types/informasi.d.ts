import type { Paginator } from '@/components/v2/DataTable';

/**
 * Kontrak props menu INFORMASI (Fase 12) — cerminan `InformasiController`.
 *
 * Informasi bukan dokumen mutu: tak ada wizard, tak ada peninjau/penyetuju, tak
 * ada nomor terbitan. Yang dibawa ke layar karena itu jauh lebih sedikit — dan
 * yang SENGAJA tidak dibawa adalah `file_path` (jalur berkas privat di disk
 * `local`, namanya diacak Laravel) beserta relasi `uploader` (model User utuh).
 * Berkasnya dibuka lewat rute `informasi.file`, satu-satunya pintu berpenjaga.
 */

/** Satu versi Informasi — dipakai baris utama MAUPUN barisan riwayatnya. */
export interface VersiInformasi {
    id: number;
    nomor: string;
    judul: string;
    edisi: number | null;
    no_revisi: number | null;
    /** "Edisi 4 Rev 2", atau null bila kategorinya memang tak bernomor edisi. */
    revisi: string | null;
    /** `tanggal_efektif` sebagai `d/m/Y`. */
    tanggal: string | null;
    diunggah: string | null;
    diunggah_lengkap: string | null;
    oleh: string | null;
    /** POSTER boleh berupa gambar — menentukan ikon tombol Buka. */
    gambar: boolean;
}

export interface BarisInformasi extends VersiInformasi {
    /**
     * Versi lama nomor INI saja, terbaru lebih dulu. Menempel di barisnya
     * sendiri, bukan peta kedua ber-key nomor: dua larik yang harus dicocokkan
     * ulang di klien adalah dua larik yang suatu hari tak cocok (Fase 11).
     */
    riwayat: VersiInformasi[];
}

export interface InformasiIndexProps {
    kategori: string;
    label: string;
    /** Nama kelas `bi-*`; dipetakan ke lucide di `components/Ikon.tsx`. */
    ikon: string;
    /** `informasi_kategori.kolom_json` — menentukan kolom tabel & isian form. */
    kolom: string[];
    kelola: boolean;
    daftar: Paginator<BarisInformasi>;
    filters: { q?: string };
    prefix: string;
}

/** Props yang dipakai KEDUA formulir (Tambah & Perbarui). */
export interface InformasiFormProps {
    kategori: string;
    label: string;
    kolom: string[];
    /** Ekstensi yang diterima kategori ini (`accept` + keterangan). */
    ekstensi: string[];
    prefix: string;
    /** Roll-over Edisi/Revisi, dihitung `DocumentService::nextEditionRevision`. */
    edisiBaru: number;
    revisiBaru: number;
}

export interface InformasiCreateProps extends InformasiFormProps {
    induk: null;
}

export interface InformasiPerbaruiProps extends InformasiFormProps {
    induk: VersiInformasi & {
        /** `Y-m-d` — satu-satunya bentuk yang diterima `<input type="date">`. */
        tanggal_input: string | null;
    };
}

export interface InformasiNonaktifProps {
    kat: { nama: string; ikon: string };
}
