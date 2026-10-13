/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/konteks.tsx` (REDESAIN-UI-V2 T2).
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

import type { CatatanBaris, DokumenPilihan, Kandidat, Ketersediaan, NilaiSeksi, SeksiSchema } from '@/types/wizard';

/**
 * Yang dibutuhkan komponen field DI LUAR nilainya sendiri.
 *
 * Lewat context, bukan prop drilling: `Edit → renderer → RepeatableGroup →
 * RichText` sudah tiga lapis, dan tiap lapis yang harus meneruskan sembilan
 * prop adalah tiga tempat yang bisa lupa meneruskan satu.
 *
 * Isinya SELURUHNYA datang dari server (pakem P3/P5). Tak ada satu pun
 * keputusan — siapa kandidatnya, siapa yang tersedia, papan mana yang
 * memblokir — yang lahir di berkas ini.
 */
export interface KonteksWizard {
    documentId: number;
    editable: boolean;
    /** Isi pemilih lampiran (bab VII SOP). */
    dokumenBerlaku: DokumenPilihan[];
    /** Varian label & nomor bab saat bab opsional DIMATIKAN — dihitung PHP. */
    labelMati: Record<string, { label: string | null; auto_number: string | null }>;
    /**
     * Sakelar bab opsional.
     *
     * ponytail: satu boolean, bukan peta per-kunci — persis seperti store
     * Alpine yang digantikannya. Begitu ada DUA bab opsional dalam satu jenis,
     * ini harus jadi `Record<string, boolean>`, DAN `labelMati` di server harus
     * ikut dihitung per-kombinasi; menaikkan salah satunya saja menghasilkan
     * nomor bab yang salah tanpa satu pun galat.
     */
    babOn: boolean;
    setBabOn: (nyala: boolean) => void;
    candidates: Record<string, Kandidat[]>;
    ketersediaan: Record<string, Record<number, Ketersediaan>>;
    /** key seksi → apakah batas beban MEMBLOKIR di sana. */
    papan: Record<string, boolean>;
    ambang: { padat: number; sibuk: number; jenis: string[] };
    /**
     * `section_key` → `item_ref` → catatan peninjau & sejawat atas item itu.
     *
     * Lewat context justru karena inilah alasan context ini ada: catatan harus
     * sampai ke tingkat TERDALAM (satu tindakan pengendalian JSA duduk empat
     * lapis di bawah `Seksi`), dan meneruskannya sebagai prop berarti empat
     * tempat yang bisa lupa meneruskan.
     */
    catatanItem: Record<string, Record<string, CatatanBaris[]>>;
}

/**
 * Bentuk seragam tiap komponen field.
 *
 * `onChange` menerima nilai LANGSUNG atau FUNGSI pembaru `(nilaiLama) =>
 * nilaiBaru`. Bentuk fungsi inilah yang membuat `RepeatableGroup` aman dari
 * balapan antar-SAUDARA baris: Lexical (editor Isi) memanggil ulang
 * `registerUpdateListener`-nya sendiri setiap kali POHON di sekitarnya
 * dirender ulang — termasuk render yang dipicu baris LAIN yang baru saja
 * dihapus — dan panggilan itu menutup `rows` dari render SEBELUM
 * penghapusan. Dengan nilai LANGSUNG, panggilan telat itu menimpa balik
 * penghapusannya (baris yang sudah dihapus muncul lagi, persis bug yang
 * dilaporkan pemilik 2026-10-07). Dengan fungsi pembaru, React menjalankan
 * TIAP `setState` terhadap hasil yang SEBELUMNYA — jadi panggilan telat
 * beroperasi di atas array yang SUDAH berkurang, bukan salinan basi.
 * Komponen field lain (Text, RichList, dst.) tetap boleh memanggil
 * `onChange(nilai)` langsung seperti biasa — bentuk ini backward-compatible.
 */
export interface PropsField {
    section: SeksiSchema;
    value: NilaiSeksi;
    onChange: (nilai: NilaiSeksi | ((nilaiLama: NilaiSeksi) => NilaiSeksi)) => void;
}

const Konteks = createContext<KonteksWizard | null>(null);

export const PenyediaWizard = Konteks.Provider;

export function useWizard(): KonteksWizard {
    const nilai = useContext(Konteks);

    if (!nilai) {
        throw new Error('Komponen field dipakai di luar PenyediaWizard.');
    }

    return nilai;
}

/** Keterangan bantu di bawah label seksi (`help` / `hint` di schema). */
export function Bantuan({ teks }: { teks?: string }) {
    if (!teks) return null;

    return <p className="mb-2 text-xs text-muted-foreground">{teks}</p>;
}
