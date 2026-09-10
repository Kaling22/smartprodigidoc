/**
 * KEMBARAN MAIA dari `resources/js/components/tinjau/konteks.tsx` (REDESAIN-UI-V2 T2).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser. Yang berubah hanya
 * dari kit mana komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya
 * (lucide → hugeicons, §5). Berkas aslinya sengaja TIDAK disentuh: 41 halaman
 * lama masih memakainya sebagai pembanding selama jendela pratinjau. Tranche 4
 * menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 */
import { createContext, useContext } from 'react';

/**
 * Keadaan formulir tinjauan yang dibutuhkan komponen di BAWAH halamannya.
 *
 * Lewat context, bukan prop drilling — pola yang sama dengan
 * `components/v2/dokumen/fields/konteks.tsx`. Analisa JSA bersarang tiga lapis
 * (Langkah → Bahaya → Pengendalian), dan tiap lapis yang harus meneruskan enam
 * prop adalah tiga tempat yang bisa lupa meneruskan satu.
 *
 * Tak ada satu pun aturan di sini: siapa boleh menilai apa dijawab server
 * (`ReviewAccess`, `ReviewDecision::verdictSah`), dan kelengkapan tanda ✓/✗
 * diperiksa ULANG di sana — layar bisa dilewati, server tidak (pakem P4).
 */
export interface KonteksTinjau {
    /** section_key → item_ref → isi kotak catatan. */
    catatan: Record<string, Record<string, string>>;
    ubahCatatan: (seksi: string, item: string, teks: string) => void;
    /** Kotak yang isinya DIADOPSI dari saran AI — ikut terkirim sbg `annotations_ai`. */
    dariAi: Record<string, Record<string, boolean>>;
    /** Catatan putaran sebelumnya, dari server (baca-saja). */
    anotasiLama: Record<string, Record<string, string[]>>;
    /** JSA: item_ref pengendalian → `sesuai` | `perlu_revisi`. */
    verdicts: Record<string, string>;
    setVerdict: (item: string, nilai: string) => void;
    /** Menandai BANYAK pengendalian satu bahaya sekaligus. */
    setVerdictBorongan: (refs: string[], nilai: string) => void;
    pakaiVerdict: boolean;
}

const Konteks = createContext<KonteksTinjau | null>(null);

export const PenyediaTinjau = Konteks.Provider;

export function useTinjau(): KonteksTinjau {
    const nilai = useContext(Konteks);

    if (!nilai) {
        throw new Error('Komponen tinjau dipakai di luar PenyediaTinjau.');
    }

    return nilai;
}
