import type { Kandidat, Ketersediaan, PetaSeksi, Schema } from '@/types/wizard';
import type { BarisDokumen } from '@/types/dokumen';

/**
 * Bentuk props layar peninjauan & persetujuan (Fase 10).
 *
 * Seluruhnya CERMINAN dari `App\Services\ReviewScreen` + kelima controller di
 * atasnya. Kalau kontrak di PHP berubah, yang berubah cuma berkas ini.
 *
 * Perhatikan `schema`: ia dikirim APA ADANYA dari `document_types.schema_json`
 * (pakem P1) — layar tinjau membacanya, tak pernah menyalin strukturnya jadi
 * TSX statis. Begitu pula `tipeDitinjau`, yang menentukan bab mana yang punya
 * kotak catatan: daftarnya datang dari `ReviewScreen::TIPE_DITINJAU`.
 */

/** Satu baris antrean tinjau (barisDaftar + kolom khas halamannya). */
export interface BarisTinjau extends BarisDokumen {
    /** Antrean tinjau: JSA yang benar-benar ada di tangan orang ini. */
    boleh_alih?: boolean;
    /** Antrean MD & persetujuan: siapa yang memeriksa substansinya. */
    peninjau?: string | null;
}

/** Satu baris antrean Persetujuan Nonaktif. */
export interface BarisNonaktif extends BarisDokumen {
    pengaju: string | null;
    tahap: string | null;
    tahapLabel: string;
    alasan: string | null;
    tahapTerakhir: boolean;
}

export interface KomentarLampiran {
    id: number;
    oleh: string;
    isi: string;
    /** Sudah diformat server ("2 jam yang lalu"). */
    waktu: string | null;
}

export interface LampiranTinjau {
    id: number;
    url: string;
    nama: string;
    komentar: KomentarLampiran[];
}

/** Satu temuan AI (bentuknya dari `AiReviewerInterface`). */
export interface TemuanAi {
    section_key: string;
    item_ref: string | number | null;
    severity: 'info' | 'minor' | 'major' | 'critical' | string;
    issue: string;
    suggestion: string;
}

export interface ReviewShowProps {
    document: {
        id: number;
        nomor: string;
        judul: string;
        dept: string | null;
        edisi: number;
        noRevisi: number;
        /** Berapa kali dokumen DIKEMBALIKAN — bukan nomor revisinya. */
        putaran: number;
    };
    schema: Schema;
    /** Tipe seksi yang punya kotak catatan (`ReviewScreen::TIPE_DITINJAU`). */
    tipeDitinjau: string[];
    /** Isi dokumen; kolom `rich_text` sudah DIBERSIHKAN di server. */
    contentMap: PetaSeksi;
    /** section_key → item_ref → catatan putaran sebelumnya. */
    anotasiLama: Record<string, Record<string, string[]>>;
    lampiran: LampiranTinjau[];

    formAction: string;
    backUrl: string;
    backLabel?: string;
    judulLayar?: string;
    /** Berisi `<strong>` — teks tetap milik server, bukan masukan pengguna. */
    petunjuk?: string;
    labelTolak?: string;
    labelLolos?: string;
    konfirmasiTolak?: string;
    konfirmasiLolos?: string;
    /** Masukan Sejawat: tanpa Loloskan/Kembalikan, hanya Batal & Kirim. */
    tanpaKeputusan?: boolean;
    /** JSA: tanda ✓/✗ per Tindakan Pengendalian. */
    pakaiVerdict: boolean;
    /** `null` = panel AI hilang seluruhnya (Admin mematikannya, atau sejawat). */
    aiUrl?: string | null;

    pengalihan?: { dari: string; alasan: string; waktu: string } | null;
    /** `null` = pengalihan tak berlaku di layar ini. */
    alihKandidat?: Kandidat[] | null;
    alihKetersediaan?: Record<number, Ketersediaan>;
    ambang?: { padat: number; sibuk: number; jenis: string[] };
    alihUrl?: string;
    alihOtomatis?: boolean;
}

export interface ApprovalsShowProps {
    document: {
        id: number;
        nomor: string;
        judul: string;
        dept: string | null;
        departemen: string | null;
        pembuat: string | null;
        pembuatNrp: string | null;
        peninjau: string | null;
        edisi: number;
        noRevisi: number;
    };
}

export interface MasukanSejawatShowProps {
    document: {
        id: number;
        nomor: string;
        judul: string;
        dept: string | null;
        status: string;
    };
    masukan: {
        id: number;
        oleh: string;
        waktu: string | null;
        ringkasan: string | null;
        perSection: {
            label: string;
            /** `ref` sudah jadi kalimat ("Langkah 1.2 — Bahaya & Risiko"). */
            items: { ref: string; komentar: string }[];
        }[];
    }[];
}
