/**
 * Bentuk props wizard pengisian dokumen (Fase 9).
 *
 * Seluruhnya CERMINAN dari apa yang dikirim `DocumentController@{create,edit}`.
 * Kalau kontrak di PHP berubah, yang berubah cuma berkas ini — bukan sebelas
 * komponen field di bawahnya.
 *
 * Perhatikan `Schema`: ia dikirim APA ADANYA dari `document_types.schema_json`
 * (pakem P1). Tak satu pun komponen boleh menyalin strukturnya jadi TSX statis —
 * seksi baru yang ditambahkan lewat SQL harus muncul sendiri di form.
 */

/**
 * Satu catatan yang menempel pada SATU item dokumen.
 *
 * Datang dari dua kanal sekaligus — peninjau (`review_annotations`) dan sejawat
 * (`document_feedback_antargl.catatan_json`) — dan dirakit `CatatanPerItem`
 * jadi bentuk tunggal ini. `sumber` yang membedakan lencananya.
 *
 * `kutipan` adalah teks item saat halaman dimuat: `null` berarti itemnya sudah
 * TIDAK ADA (catatannya jatuh ke `catatanYatim`), sedangkan teks yang tak lagi
 * cocok dengan isian hidup berarti barisnya bergeser atau sudah diperbaiki.
 */
export interface CatatanBaris {
    komentar: string;
    oleh: string;
    /** peninjau | sejawat */
    sumber: string;
    kutipan: string | null;
}

/** Catatan yang tak punya baris tempat menempel — item terhapus, atau seksi tanpa kotak catatan. */
export interface CatatanYatim {
    bagian: string;
    item: string;
    komentar: string;
    oleh: string;
    sumber: string;
}

/** Satu kolom di dalam `repeatable_group`. */
export interface BidangSchema {
    key: string;
    /** text | textarea | image | rich_text | document_picker */
    type?: string;
    label?: string;
    placeholder?: string;
    /** Tak wajib, tapi kekosongannya diingatkan saat Kirim. */
    warn_if_empty?: boolean;
    image_accept?: string;
    image_max_mb?: number;
    /**
     * `document_picker` saja (butir 6): nama kolom SAUDARA di baris yang sama
     * yang menampung JUDUL dokumen, sementara kolom ini menampung nomornya.
     * Tanpa kunci ini perilaku lama dipertahankan — satu baris teks gabungan.
     */
    judul_ke?: string;
}

/** Satu seksi (bab) di dalam sebuah langkah. */
export interface SeksiSchema {
    key: string;
    type: string;
    label?: string;
    /** Nama bab tanpa nomor; yang menomori SchemaService, bukan layar. */
    nama?: string;
    help?: string;
    hint?: string;
    placeholder?: string;
    default?: string;
    required?: boolean;
    warn_if_empty?: boolean;
    /** Awalan nomor butir, mis. "6." — sudah jadi dari server. */
    auto_number?: string;
    suggestions?: string[];
    multiple?: boolean;
    min_groups?: number;
    min_items?: number;
    add_button_label?: string;
    group_fields?: BidangSchema[];
    fields?: BidangSchema[];
    /** Bab OPSIONAL (mis. FLOWCHART): kunci isi tempat sakelarnya tersimpan. */
    toggle_key?: string;
    toggle_label?: string;
}

export interface LangkahSchema {
    step: number;
    title?: string;
    title_tanpa_opsional?: string;
    sections?: SeksiSchema[];
}

export interface Schema {
    doc_type?: string;
    steps?: LangkahSchema[];
}

/** Kandidat user_picker — empat kolom, tanpa satu pun kolom rahasia. */
export interface Kandidat {
    id: number;
    nama: string;
    nrp: string;
    dept: string;
    /** Label jabatan ("Section Head"), BUKAN kunci mentah. */
    jabatan: string;
}

export interface SelPita {
    /** Sudah diformat server ("28 Agu") — jangan diformat ulang di peramban. */
    tanggal: string;
    status: 'tersedia' | 'off' | 'tutup';
    label: string;
}

export interface Ketersediaan {
    beban: number;
    batas: number;
    penuh: boolean;
    tingkat: 'normal' | 'padat' | 'sibuk';
    off: boolean;
    tersedia: boolean;
    jenis: string | null;
    /** Sudah diformat server ("Senin, 1 September"), atau null. */
    kembali: string | null;
    /** Bentuk 'Y-m-d' — hanya untuk diurutkan, tak pernah dicetak. */
    kembaliIso: string | null;
    alasan: string;
    pita: SelPita[];
}

/** Satu pilihan pemilih lampiran (bab VII SOP). */
export interface DokumenPilihan {
    jenis: string;
    nomor: string;
    judul: string;
}

/** Nilai satu seksi. Bentuknya ditentukan `type`-nya, jadi sengaja longgar. */
export type NilaiSeksi = unknown;

export type PetaSeksi = Record<string, NilaiSeksi>;

export interface DocumentsCreateProps {
    type: { id: number; code: string; name: string };
    departments: { id: number; code: string; name: string }[];
    defaultDept: number | null;
    canChooseDept: boolean;
    numberPreview: string;
    unggahanSaja: boolean;
    prefix: string;
    /**
     * Nomor bebas yang disarankan server sesudah kiriman ditolak karena
     * nomornya bentrok (butir 4). Null pada kunjungan biasa — dan justru
     * itulah yang menentukan dialognya muncul atau tidak.
     */
    nomorSaran: string | null;
}

export interface DocumentsUnavailableProps {
    type: { code: string; name: string };
    jenisBaru: string | null;
}

export interface DocumentsEditProps {
    document: {
        id: number;
        nomor: string;
        nomorFinal: boolean;
        judul: string;
        jenis: string;
        departemen: string;
        status: string;
        pembuat: string;
        edisi: number;
        noRevisi: number;
    };
    schema: Schema;
    currentStep: number;
    totalSteps: number;
    isRevLogStep: boolean;
    contentMap: PetaSeksi;
    editable: boolean;
    babAktif: boolean;
    labelMati: Record<string, { label: string | null; auto_number: string | null }>;
    judulLangkahMati: string;
    candidates: Record<string, Kandidat[]>;
    ketersediaan: Record<string, Record<number, Ketersediaan>>;
    /** key seksi → apakah batas beban MEMBLOKIR di sana. */
    papan: Record<string, boolean>;
    ambang: { padat: number; sibuk: number; jenis: string[] };
    userValues: Record<string, NilaiSeksi>;
    dokumenBerlaku: DokumenPilihan[];
    previewV: string;
    /** section_key → item_ref → catatan yang menempel di item itu. */
    catatanItem: Record<string, Record<string, CatatanBaris[]>>;
    /** Catatan yang tak punya baris tempat menempel — digambar di kepala halaman. */
    catatanYatim: CatatanYatim[];
    reviewSummary: string | null;
    rujukanPdfUrl: string | null;
    revisiKirim: { edisi: number; revisi: number } | null;
    /**
     * Tgl. Terbit & Tgl. Revisi yang tercetak di kop — hanya draft salinan
     * arsip; null pada draft lain (dan langkah Log Revisi lalu tak menggambar
     * kedua isiannya).
     */
    tanggalCetak: { terbit: string; revisi: string } | null;
}
