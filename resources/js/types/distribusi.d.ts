import type { Cakupan } from '@/types/dasbor';
import type { BarisDokumen, RincianPembaca } from '@/types/dokumen';
import type { Departemen } from '@/types';

/**
 * Kontrak props Fase 11 — cerminan `DocumentDistributionController` dan
 * `DocumentArsipController`.
 *
 * Tak satu pun angka cakupan dihitung di klien: `pembaca`/`sasaran`/`persen`
 * datang dari `DocumentDistribution` & `InformasiDistribution`, yang memang
 * dirancang menerima kumpulan supaya daftar ini tak melahirkan N+1 (pakem P5).
 */

/* ── Distribusi ────────────────────────────────────────────────────────── */

/** Satu dokumen mutu Berlaku beserta jangkauannya. */
export interface BarisDistribusiDokumen extends BarisDokumen {
    cakupan: Cakupan;
}

/** Cakupan satu departemen atas satu informasi (`InformasiDistribution::perDept`). */
export interface CakupanDept extends Cakupan {
    dept: string;
}

/** Satu informasi berlaku beserta jangkauannya. */
export interface BarisDistribusiInformasi {
    id: number;
    nomor: string | null;
    judul: string;
    /** LABEL kategori, sudah dibaca dari `InformasiKategori` di server (P3). */
    kategori: string;
    /** "Edisi 2 Rev 1", atau null bila informasinya tak bernomor revisi. */
    revisi: string | null;
    cakupan: Cakupan;
    /** Kosong bagi yang bukan pemegang `document.view_all` — expander tak terbit. */
    perDept: CakupanDept[];
}

/**
 * SATU halaman, DUA sumber — persis seperti `distribution.blade.php` yang
 * memilih antara tabelnya sendiri dan `_distribusi-informasi`. Yang membedakan
 * cuma `sumber`; penjaga akses, saklar sumber, dan pita cakupannya sama.
 */
export type DistribusiProps =
    | {
          sumber: 'mutu';
          documents: BarisDistribusiDokumen[];
          filters: { q?: string; type?: string; department_id?: string };
          departments: Departemen[];
          jenis: string[];
          prefix: string;
          canAll: boolean;
          rendah: number;
      }
    | {
          sumber: 'informasi';
          informasi: BarisDistribusiInformasi[];
          kategoriOpsi: Record<string, string>;
          filters: { q?: string; kategori?: string };
          canAll: boolean;
          rendah: number;
      };

export interface RincianInformasiProps {
    informasi: Omit<BarisDistribusiInformasi, 'cakupan' | 'perDept'>;
    cakupan: Cakupan;
    rincian: RincianPembaca;
    /** Kosong bagi SH/DH — mereka sudah terkurung ke departemennya di server. */
    departments: Departemen[];
    deptId: number | null;
}

/* ── Dokumen lama (arsip) ──────────────────────────────────────────────── */

/** Bentuk jadi dari `DocumentArsipController::barisArsip()`. */
export interface DokumenArsip {
    id: number;
    nomor: string;
    judul: string;
    jenis: string | null;
    jenis_nama: string | null;
    dept: string | null;
    dept_nama: string | null;
    edisi: number;
    no_revisi: number;
    /** `Y-m-d`, sudah dirangkai server untuk `<input type="date">`. */
    tanggal_efektif: string | null;
    /** SOP/SP/IK saja — JSA & unggahan tak pernah punya lembar revisi. */
    berlembar_revisi: boolean;
}

export interface ArsipEditProps {
    document: DokumenArsip;
}

export interface ArsipCatatanProps {
    document: DokumenArsip;
    /** Baris lembar yang sudah tersimpan; bentuknya sama dengan langkah wizard. */
    baris: unknown[];
    /** Edisi/Revisi yang BERLAKU saat dokumen dikirim (butir 7a). */
    revisiKirim: { edisi: number; revisi: number };
    /**
     * SISA halaman V1 saja. Server BERHENTI mengirimnya sejak pilihan "gabung"
     * dicabut (rencana pra-produksi Fase 2) — tinggal di sini semata supaya
     * kembaran V1 yang dibekukan tetap lolos `tsc`. Halaman V2 tak memakainya.
     */
    jumlahHalaman: number;
}
