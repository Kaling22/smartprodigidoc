import type { Paginator } from '@/components/v2/DataTable';
import type { Departemen } from '@/types';
import type { BarisMasukan, JadwalPembuat, JanjiRevisi } from '@/types/dokumen';

/**
 * Kontrak props RIWAYAT & LOG (Fase 12) — cerminan `AuditController`,
 * `JobExecutionController`, dan `DocumentLogController`.
 *
 * Keempat layar ini BACA-SAJA kecuali dua tombol Log Masukan (Balas & Revisi)
 * dan satu tombol Riwayat Pekerjaan (Hapus, Admin IT). Tak satu pun aturan
 * wewenang hidup di berkas ini: tiap tombol datang sebagai `boleh_*` yang sudah
 * dijawab model, dan servernya memeriksa ulang semuanya (pakem P4 & P5).
 */

/* ── Audit Log ─────────────────────────────────────────────────────────── */

export interface BarisAudit {
    id: number;
    waktu: string | null;
    /** Nama pelaku, atau "Sistem" untuk baris tanpa user. */
    oleh: string;
    nrp: string | null;
    aksi: string;
    /** null = aksinya tak menunjuk dokumen mana pun. */
    dokumen: { id: number; nomor: string } | null;
    ip: string | null;
}

export interface AuditIndexProps {
    logs: Paginator<BarisAudit>;
    filters: { q?: string; action?: string };
}

/* ── Riwayat Pekerjaan JSA ─────────────────────────────────────────────── */

export interface BarisPekerjaan {
    id: number;
    nama: string;
    catatan: string | null;
    jsa_judul: string | null;
    jsa_nomor: string | null;
    jsa_dept: string | null;
    pelaksana: string | null;
    nrp: string | null;
    lokasi: string | null;
    /** `d M Y` dalam locale Indonesia — dirangkai server. */
    tanggal: string | null;
    progres: { selesai: number; total: number };
    status: string;
    status_label: string;
}

export interface JobExecutionsIndexProps {
    pekerjaan: Paginator<BarisPekerjaan>;
    filters: { q?: string; status?: string; tanggal_dari?: string; tanggal_sampai?: string };
    /** `JobExecution::STATUS_LABELS` — labelnya tak pernah diketik di TSX. */
    statusLabels: Record<string, string>;
    bolehHapus: boolean;
}

/* ── Log Dokumen → Masukan Lapangan ────────────────────────────────────── */

export interface BarisLogMasukan extends BarisMasukan {
    dokumen: { id: number; nomor: string; judul: string } | null;
    boleh_balas: boolean;
    boleh_revisi: boolean;
}

/**
 * Jendela "Ajukan Revisi", SATU per dokumen — beberapa masukan bisa menunjuk
 * dokumen yang sama. Pengelompokannya dikerjakan server, persis seperti
 * `groupBy('document_id')` di Blade lama (pakem P5).
 */
export interface RevisiDariMasukan {
    id: number;
    nomor: string;
    pembuat_id: number | null;
    janji_revisi: JanjiRevisi;
    masukan: BarisMasukan[];
    /** Masukan pemicunya, sudah tercentang saat jendela dibuka. */
    tercentang: number[];
}

export interface LogMasukanProps {
    masukan: Paginator<BarisLogMasukan>;
    revisi: RevisiDariMasukan[];
    filters: { q?: string; status?: string; department_id?: string };
    departments: Departemen[];
    /** `DocumentFeedback::STATUS_LABELS`. */
    statusOpsi: Record<string, string>;
    bolehMembalas: boolean;
    ketersediaanPembuat: Record<number, JadwalPembuat>;
}

/* ── Log Dokumen → Log Pesan ───────────────────────────────────────────── */

export interface BarisPesan {
    tanggal: string;
    nomor: string;
    judul: string;
    dept: string;
    /** Kunci di `DocumentLogController::TAHAP`. */
    tahap: string;
    oleh: string | null;
    alasan: string;
    /**
     * Tujuan, label, DAN ikonnya seluruhnya dari controller
     * (`DocumentLogController::tautan`): ia bergantung pada PENONTON, bukan
     * hanya tahap. `null` = tanpa tombol (baris pemusnahan — dokumennya lenyap).
     */
    tautan: { url: string; label: string; ikon: string } | null;
}

export interface LogPesanProps {
    pesan: Paginator<BarisPesan>;
    filters: { q?: string; tahap?: string; department_id?: string; dari?: string; sampai?: string };
    /** `DocumentLogController::TAHAP` — `[label, warna]` per kunci tahap. */
    tahapan: Record<string, [string, string]>;
    departments: Departemen[];
    prefix: string;
}
