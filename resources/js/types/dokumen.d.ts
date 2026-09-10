import type { Paginator } from '@/components/v2/DataTable';
import type { Cakupan } from '@/types/dasbor';
import type { Departemen } from '@/types';

/**
 * Kontrak props halaman DAFTAR DOKUMEN (Fase 8) — cerminan
 * `DocumentController@index/@show`, `DocumentRevisionController@{published,
 * obsolete,revisions}`, dan `DocumentStaffStatusController@staffStatus`.
 *
 * Tak satu pun aturan wewenang hidup di berkas ini. Visibilitas datang dari
 * `Document::scopeTerlihatOleh` + penyaringan controller, dan setiap tombol
 * per-baris datang sebagai `boleh_*` yang sudah dijawab model
 * (`bisaDirevisiOleh`, `bisaDiberiMasukanOleh`, `bisaDisuntingArsipOleh`,
 * `pemilikRevisi`). Klien hanya menggambar apa yang dikirim — dan servernya
 * memeriksa ulang semuanya (pakem P4 & P5).
 */

/** Bentuk jadi dari `Document::barisDaftar()`. */
export interface BarisDokumen {
    id: number;
    /** `doc_number_final` bila sudah terbit, selain itu nomor sementara. */
    nomor: string;
    judul: string;
    jenis: string | null;
    dept: string | null;
    /** Kunci status — dirupakan `StatusBadge` lewat props `statusMeta` (P3). */
    status: string;
    pembuat: string | null;
    edisi: string | number;
    no_revisi: number;
    /** PDF unggahan apa adanya, bukan susunan wizard. */
    arsip: boolean;
    /** Nomornya di luar pola `{PREFIX}-{JENIS}-{DEPT}-{NN}`. */
    nomor_luar_pola: boolean;
    boleh_edit_arsip: boolean;
}

/** Satu masukan lapangan — `DocumentController::barisMasukan()`. */
export interface BarisMasukan {
    id: number;
    nomor: string;
    isi: string;
    status: string;
    status_label: string;
    oleh: string | null;
    waktu: string | null;
    balasan: string | null;
    pembalas: string | null;
}

/** `[edisi, revisi]` yang DIJANJIKAN bila revisi diajukan sekarang. */
export type JanjiRevisi = [number, number];

/** Keterangan "pembuat sedang cuti", sudah dirangkai server (locale Indonesia). */
export interface JadwalPembuat {
    nama: string | null;
    jenis: string | null;
    kembali: string | null;
}

/* ── Dokumen Saya / Status Dokumen ─────────────────────────────────────── */

export interface BarisIndex extends BarisDokumen {
    /** Draft miliknya sendiri yang boleh ia kelola (Kirim/Edit/Hapus). */
    milik: boolean;
    /** `null` = lencana masukan sejawat tak ditawarkan; angka = jumlahnya. */
    masukan_sejawat: number | null;
}

export interface DocumentsIndexProps {
    documents: Paginator<BarisIndex>;
    filters: { q?: string; status?: string; type?: string; department_id?: string; sumber?: string };
    types: string[];
    departments: Departemen[];
    /** GL "Dokumen Saya" memuat segala status; peran lain hanya yang berproses. */
    showAllStatuses: boolean;
    statusOpsi: string[];
    /** Jenis pertama yang BOLEH ia susun; `null` = tombol Dokumen Baru tak ada. */
    jenisBaru: string | null;
    prefix: string;
}

/* ── Detail dokumen ────────────────────────────────────────────────────── */

export interface DetailDokumen extends BarisDokumen {
    /** Kunci ke `ketersediaanPembuat`, yang diindeks id PENGGUNA. */
    pembuat_id: number | null;
    jenis_nama: string | null;
    dept_nama: string | null;
    peninjau: string | null;
    penyetuju: string | null;
    terbit: string | null;
    boleh_revisi: boolean;
    boleh_beri_masukan: boolean;
    janji_revisi: JanjiRevisi;
}

/** Satu peristiwa timeline; `meta` = `[label, ikon bi-*, rona]`. */
export interface PeristiwaTimeline {
    id: number;
    meta: [string, string, string];
    oleh: string;
    /** URL foto profil pelaku. `null` = pakai inisial (`Avatar` yang mengurus). */
    foto: string | null;
    waktu: string | null;
}

export interface RincianPembaca {
    sudah: {
        nama: string;
        jabatan: string | null;
        foto: string | null;
        platform: string | null;
        waktu: string | null;
    }[];
    belum: { nama: string; jabatan: string | null; foto: string | null }[];
}

export interface DocumentsShowProps {
    document: DetailDokumen;
    timeline: PeristiwaTimeline[];
    masukan: BarisMasukan[];
    /** Yang boleh dicentang jadi alasan revisi — disaring server (§5). */
    masukanBelumDitindak: BarisMasukan[];
    bolehMembalasMasukan: boolean;
    bolehLihatMasukan: boolean;
    /** `null` = panel Distribusi tak dirender sama sekali, bukan dirender kosong. */
    distribusi: Cakupan | null;
    distribusiRincian: RincianPembaca | null;
    ketersediaanPembuat: Record<number, JadwalPembuat>;
}

/* ── Dokumen Berlaku ───────────────────────────────────────────────────── */

export interface BarisBerlaku extends BarisDokumen {
    /** `null` = lencana masukan tak ditawarkan (Non-Staff). */
    masukan_count: number | null;
    boleh_beri_masukan: boolean;
    boleh_revisi: boolean;
    boleh_batal_revisi: boolean;
    boleh_musnahkan: boolean;
    pembuat_id: number | null;
    masukan: BarisMasukan[];
    janji_revisi: JanjiRevisi;
}

export interface DocumentsPublishedProps {
    documents: Paginator<BarisBerlaku>;
    filterType: string | null;
    filters: { q?: string; type?: string; department_id?: string };
    departments: Departemen[];
    jenis: string[];
    /** `DocumentType::RUPA` — `[ikon, warna]` per kode jenis. */
    rupa: Record<string, [string, string]>;
    prefix: string;
    ketersediaanPembuat: Record<number, JadwalPembuat>;
}

/* ── Dokumen Tidak Berlaku ─────────────────────────────────────────────── */

export interface BarisObsolete extends BarisDokumen {
    /** Nomornya kembali ke kolam — dua dokumen boleh sah memegangnya. */
    nomor_dilepas: boolean;
    dinonaktifkan: string | null;
    boleh_rollback: boolean;
    /** Aktifkan kembali akan menuntut nomor baru. */
    nomor_bentrok: boolean;
    nomor_final: string | null;
    boleh_arsipkan: boolean;
    boleh_aktifkan: boolean;
    boleh_musnahkan: boolean;
}

export interface GrupObsolete extends BarisObsolete {
    /** Seluruh versi grup ini, induknya sendiri termasuk. */
    versi: BarisObsolete[];
}

export interface DocumentsObsoleteProps {
    documents: Paginator<GrupObsolete>;
    filters: { q?: string; department_id?: string };
    departments: Departemen[];
    prefix: string;
}

/* ── Dokumen Revisi ────────────────────────────────────────────────────── */

export interface BarisRevisi extends BarisDokumen {
    draft_revisi: boolean;
    status_label: string;
    rangkuman: string | null;
    anotasi: { bagian: string; komentar: string }[];
    anotasi_sisa: number;
}

export interface DocumentsRevisionsProps {
    documents: Paginator<BarisRevisi>;
}

/* ── Status Dokumen Staff / Dokumen Departemen ─────────────────────────── */

export interface BarisStaffStatus extends BarisDokumen {
    boleh_masukan_sejawat: boolean;
}

export interface DocumentsStaffStatusProps {
    documents: Paginator<BarisStaffStatus>;
    filters: { q?: string; status?: string; type?: string; department_id?: string };
    types: string[];
    departments: Departemen[];
    canAll: boolean;
    selectedDept: Departemen | null;
    isGlDept: boolean;
    judul: string;
    statusOpsi: string[];
    prefix: string;
}
