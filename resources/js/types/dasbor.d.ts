/**
 * Kontrak props halaman Dashboard — cerminan `DashboardController@index`.
 *
 * Dipisah dari `types/index.d.ts` karena yang di sana adalah props GLOBAL
 * (dibagikan `HandleInertiaRequests` ke semua halaman); yang di sini milik satu
 * halaman saja.
 *
 * Tak satu pun aturan per jabatan hidup di berkas ini: `hero`, `tiles`,
 * `daftarTahap`, `persen`, dan `bolehBukaHalaman` semuanya datang JADI dari
 * server (pakem P5). Yang ada di sini cuma bentuknya.
 */

import type { Paginator } from '@/components/v2/DataTable';
import type { Departemen } from '@/types';

/** Satu ember waktu Overview Dokumen. Ketiganya dihitung sekaligus di server. */
export interface DeretTren {
    labels: string[];
    dibuat: number[];
    berlaku: number[];
    total: number;
    tertinjau: number;
    berlakuTotal: number;
    /** Persen dokumen periode ini yang sudah mencapai tahap milik peran pembaca. */
    growth: number;
    rentang: string;
    /**
     * Judul meter menurut peran pembaca — "Dibuat" (GL), "Tertinjau" (SH/DH/MD,
     * Admin, Non-Staff), "Disetujui" (PJO). Datang dari server: aturan peran
     * tak pernah ditulis di TSX (CLAUDE.md §4).
     */
    meterLabel: string;
    /** Kalimat kaki meter, dirakit server — mis. "7 dari 12 dokumen sudah disetujui". */
    meterKeterangan: string;
}

export type KunciDurasi = 'hari' | 'minggu' | 'bulan';

export type Tren = Record<KunciDurasi, DeretTren>;

export interface Hero {
    jabatan: string;
    dept: string | null;
    jam: string;
    tanggal: string;
    tunggu: string[];
    aksi: { label: string; url: string; utama: boolean }[];
}

export interface Tile {
    label: string;
    nilai: number;
    /** Nama kelas Bootstrap Icons; dipetakan ke lucide di `components/Ikon.tsx`. */
    ikon: string;
    url: string | null;
    /**
     * Kunci `DasborTampilan::AKSI_ALIR` yang menyuapi lencananya; `null` =
     * kartu ini sengaja tak berlencana. Layar tak pernah membacanya — ia ada
     * supaya tes bisa mengunci kartu mana yang dijanjikan punya deret.
     */
    aksi: string | null;
    /**
     * `baik` | `buruk` | `netral` — naiknya angka ini kabar apa. Dihitung
     * `DasborTampilan::tile()`, dan TSX DILARANG menebaknya dari `delta > 0`:
     * tanda angka tak pernah tahu apakah yang tumbuh itu hal yang diinginkan
     * (CLAUDE.md §4). Naiknya "Dokumen Ditolak" buruk; naiknya "Berlaku" baik.
     */
    arah: string;
    /** Sparkline 8 titik bulanan, urut dari yang tertua. `null` = tanpa lencana. */
    deret: number[] | null;
    /**
     * Persen perubahan 30 hari terakhir vs 30 hari sebelumnya. Dipakai HANYA
     * untuk arah panahnya — yang dicetak adalah `deltaLabel`.
     */
    delta: number | null;
    /**
     * Teks lencana siap cetak (`+20.1%`, `-8.3%`, atau `Baru`). `null` = kedua
     * periode kosong, dan lencananya TIDAK digambar sama sekali.
     */
    deltaLabel: string | null;
}

export interface BarisAktivitas {
    id: number;
    pelaku: string;
    aksi: string;
    ikon: string;
    /** `maju` | `netral` | `baik` | `buruk` — bukan nama kelas CSS. */
    rona: string;
    /**
     * Kata benda pendek untuk lencana garis waktu (`Berlaku`, `Ditolak`,
     * `Ditinjau`, `Revisi`, `Masukan`, `Nonaktif`, `Akun`, …). Datang dari
     * `DasborTampilan::AKSI`; JANGAN disimpulkan di TSX dari string `aksi`.
     */
    kategori: string;
    nomor: string | null;
    tautan: string | null;
    waktu: string;
}

export interface BarisMeja {
    dokumen: { id: number };
    pemegang: string | null;
    menunggu: string;
    tahap: string;
    umur: number;
    /** Kunci status MENTAH — pemasok `StatusBadge`. Label & warnanya dari `statusMeta`. */
    status: string;
    /**
     * `Y-m-d` saat dokumen masuk ke meja yang memegangnya sekarang. Sumbernya
     * sama persis dengan `umur`. Dibandingkan sebagai STRING, jangan lewat
     * `new Date()` — lihat `DashboardController::kalenderOff()`.
     */
    sejak: string | null;
    tingkat: 'baru' | 'sedang' | 'lama';
    daftarTahap: string[];
    ke: number;
    persen: number;
    judul: string;
    nomor: string;
    tautan: string;
    dept: string | null;
    pembuat: { nama: string; jabatan: string; foto: string | null };
}

export interface BarisMasukan {
    id: number;
    isi: string;
    pengirim: string;
    /** NAMA pembuat dokumennya, sudah diratakan di server — bukan relasi. */
    pemilik: string;
    nomor: string;
    tautan: string | null;
    umur: string | null;
    /** LABEL status, siap cetak. */
    status: string;
    /** Kunci status MENTAH (`baru`/`dibaca`/`diadopsi`/`ditolak`) — untuk menyaring. */
    statusKunci: string;
    /** `maju` | `netral` | `baik` | `buruk` — kosakata yang sama dengan `BarisAktivitas.rona`. */
    rona: string;
    /**
     * Jawaban `DocumentFeedback::bisaDibalasOleh()`. Datang JADI dari server:
     * syarat status + izin + batas departemen tak boleh punya salinan di TSX.
     */
    boleh_balas: boolean;
    balasan: string | null;
}

export interface Cakupan {
    pembaca: number;
    sasaran: number;
    persen: number;
    unduhan: number;
}

export interface BarisDistribusi {
    judul: string;
    sub: string;
    ikon: string;
    warna: string;
    gelar: string;
    tautan: string;
    pembaca: { nama: string; foto: string | null }[];
    c: Cakupan;
}

export interface PanelDistribusi {
    baris: BarisDistribusi[];
    rendah: number;
}

export interface DistribusiWidget {
    mutu: PanelDistribusi | null;
    informasi: PanelDistribusi | null;
    bolehBukaHalaman: boolean;
    urlMutu: string;
    urlInformasi: string;
}

export interface SelKalender {
    tanggal: string;
    angka: number;
    off: string | null;
    iniHariIni: boolean;
    lewat: boolean;
    judul: string;
}

export interface KalenderOff {
    judul: string;
    namaHari: string[];
    /** `null` = sel pendahulu supaya tanggal 1 jatuh di kolom harinya. */
    sel: (SelKalender | null)[];
    sebelum: string;
    sesudah: string;
    bisaMundur: boolean;
    bisaMaju: boolean;
    iniBulanIni: boolean;
    urlSebelum: string;
    urlSesudah: string;
    urlHariIni: string;
}

export interface Off {
    id: number;
    jenis: string;
    /** LABEL rentang, siap cetak. TSX tak boleh menyusun ulang kalimat ini. */
    rentang: string;
    /** `Y-m-d`. Dibandingkan sebagai STRING — jangan lewat `new Date()`. */
    mulai: string;
    /** `Y-m-d`. */
    sampai: string;
    catatan: string | null;
    urlBatal: string;
}

/**
 * Sebaran dokumen Berlaku per DEPARTEMEN (D6) — bar bertumpuk gaya "Attendance
 * Overview" (F3 DASBOR-V4).
 *
 * `baris` sudah URUT MENURUN dari server (§P5) — jangan mengurutkan ulang di
 * klien. `per` = jumlah per JENIS dokumen (`DocumentType::kode()`), tumpukan
 * bar-nya; warna tumpukan mengikuti JENIS, bukan departemen (F3 mencabut peta
 * warna per-departemen). `delta`/`deltaLabel`/`arah` mengukur ALIRAN MASUK
 * "berlaku" 30 hari (pola yang sama dengan D1 di `Matrix`), bukan perubahan
 * potret total.
 */
export interface SebaranDepartemen {
    total: number;
    persenTerbesar: number;
    delta: number | null;
    deltaLabel: string | null;
    arah: string;
    baris: { kode: string; nama: string; jumlah: number; persen: number; per: Record<string, number> }[];
}

/**
 * Sebaran dokumen departemen SENDIRI, per JENIS × STATUS (F4a DASBOR-V4) —
 * pasangan `SebaranDepartemen` khusus SH/DH. `per` di sini berisi jumlah per
 * STATUS (bukan per jenis) — sumbunya dibalik karena SH/DH cuma satu
 * departemen, jadi bar-nya per jenis dan tumpukannya per status.
 *
 * Kunci kepala kartunya SENGAJA sama persis dengan `SebaranDepartemen`
 * (keputusan pemilik 2026-09-07: satu gaya untuk kedua kartu). Yang diukur
 * lencananya berbeda — `dibuat` 30 hari, bukan `berlaku` — dan itu ditentukan
 * SERVER, jadi TSX tetap tak perlu tahu perbedaannya.
 */
export interface SebaranJenisDept {
    total: number;
    persenTerbesar: number;
    delta: number | null;
    deltaLabel: string | null;
    arah: string;
    baris: { kode: string; nama: string; jumlah: number; persen: number; per: Record<string, number> }[];
}

/**
 * Satu baris `aktivitasTabel` (F6 DASBOR-V4) — bentuknya SENGAJA sejajar
 * `pembuat` di `BarisMeja`, kartu yang sudah terbukti bebas kebocoran (§P4).
 */
export interface AktivitasBaris {
    id: number;
    judul: string;
    nomor: string;
    tautan: string;
    status: string;
    dibuat: string | null;
    diperbarui: string | null;
    persen: number;
    ke: number;
    dariTahap: number;
    pembuat: { nama: string; foto: string | null; jabatan: string };
}

/** Satu baris sorotan berpanah di kartu Performa PIC. Bentuknya sejajar `Tile`. */
export interface Sorotan {
    label: string;
    /** Sudah diformat server (mis. `2,4`) — TSX tak memformat ulang angka ini. */
    nilai: string;
    /** `baik` | `buruk` | `netral` — dari server, bukan dari tanda `delta`. */
    arah: string;
    delta: number | null;
    /** `null` = tanpa panah. Rata-rata beban memang tak punya deret waktu (§P2b). */
    deltaLabel: string | null;
}

/**
 * Kartu Performa PIC (D9). `wajah` HANYA memuat nama, foto, jumlah, rincian
 * (empat integer) dan ket — bukan model `User`, tanpa `id`/`nrp`/`email` (§P4).
 *
 * `ket` sudah dirakit SERVER ("meninjau 10 · menyetujui 2"); merakitnya di sini
 * berarti kosakata alur punya salinan kedua di klien.
 */
export interface WajahPic {
    nama: string;
    foto: string | null;
    /** Peringkat wajah — TANPA `dibuat` (K-H). Kartunya bukan papan peringkat. */
    jumlah: number;
    rincian: { dibuat: number; ditinjau: number; diperiksa: number; disetujui: number };
    ket: string;
}

export interface PerformaPic {
    /** F4b DASBOR-V4 (D3) — kosakata kartu berbeda per lingkup, dari server. */
    judul: string;
    subjudul: string;
    /** Satuan `TumpukanWajah` — "GL" bagi SH/DH, "PIC" bagi PJO/MD/Admin. */
    satuan: string;
    picAktif: number;
    wajah: WajahPic[];
    sorotan: Sorotan[];
}

export interface Sebaran {
    nama: string;
    jumlah: number;
    ikon: string;
    warna: string;
}

export interface BarisMatriks {
    label: string;
    status: string;
    per: Record<string, number>;
    total: number;
    // F2 DASBOR-V4 (D1) — lencana ALIRAN MASUK 30 hari, hanya terisi pada
    // baris yang dipantau `LacakStatus`; baris lain kirim null.
    delta: number | null;
    deltaLabel: string | null;
    arah: string | null;
    aliranKet: string | null;
}

export interface DashboardProps {
    user: { name: string };
    greeting: string;
    hero: Hero;
    tiles: Tile[];
    menungguDiMeja: BarisMeja[];
    masukanWidget: BarisMasukan[];
    /**
     * Jumlah PENUH masukan dengan penyaring yang sama persis dengan
     * `masukanWidget` (yang dipotong `config('smartpro.dashboard.masukan_widget')`).
     * Footer kartu memakai ini — bukan `masukanWidget.length`.
     */
    masukanTotal: number;
    /** `null` bagi yang tak berwenang — kartunya TIDAK dirender sama sekali. */
    distribusiWidget: DistribusiWidget | null;
    /**
     * Bukan-`null` HANYA bagi PJO, MD, dan Admin IT — gerbangnya
     * `can('document.view_all')` (REVISI-UI-V3 §4.2). Kartu berisi tujuh
     * departemen cuma berarti bagi yang berwenang atas ketujuhnya.
     *
     * Gerbangnya BERBEDA dari `performaPic`, jadi keduanya tak boleh diuji
     * sepasang di halaman — lihat catatan di sana.
     */
    sebaranDepartemen: SebaranDepartemen | null;
    /**
     * F4a DASBOR-V4 — pasangan `sebaranDepartemen` khusus SH/DH. Bukan-`null`
     * HANYA bagi SH/DH; `null` bagi GL, Non-Staff, PJO, MD, Admin (mereka
     * masing-masing tak punya baris ke-5 kolom ini, atau sudah punya
     * `sebaranDepartemen`).
     */
    sebaranJenisDept: SebaranJenisDept | null;
    /**
     * `null` bagi GL dan Non-Staff (REVISI-UI-V3 §4.1). SH/DH mendapatnya
     * berisi GL departemennya sendiri; PJO/MD/Admin berisi GL+SH+DH lintas
     * tujuh departemen. Saringan itu dikerjakan SERVER — TSX tak pernah tahu
     * jabatan siapa pun (CLAUDE.md §4).
     *
     * Gerbangnya BERBEDA dari `sebaranDepartemen`: pada dashboard SH/DH kartu
     * ini berdiri SENDIRI, jadi barisnya digambar per kartu + ganjal, bukan
     * sepasang seperti sebelum 2026-09-01.
     */
    performaPic: PerformaPic | null;
    /**
     * F6 DASBOR-V4 — tabel "Aktivitas Terbaru" gaya Recent Projects. Gerbang
     * SAMA dengan `sebaranDepartemen` (`document.view_all`): `null` bagi GL,
     * SH/DH, Non-Staff. BUKAN `menungguDiMeja` diperbesar — itu tetap 4 baris.
     */
    aktivitasTabel: Paginator<AktivitasBaris> | null;
    /** `null` sepasang dengan `aktivitasTabel` — tak pernah terisi sendirian. */
    aktivitasFilters: Record<string, string | undefined> | null;
    aktivitasDepartemen: Departemen[];
    aktivitasStatusOpsi: string[];
    kalenderOff: KalenderOff;
    offSaya: Off[];
    offAktif: { id: number; urlBatal: string } | null;
    stats: Record<string, number>;
    queues: Record<string, number>;
    isCreator: boolean;
    isPjo: boolean;
    isDeptHead: boolean;
    isMd: boolean;
    /** Non-Staff — penentu wajah kartu Masukan. */
    isNonStaff: boolean;
    activities: BarisAktivitas[];
    tren: Tren;
    matrix: BarisMatriks[];
    jenisList: string[];
    sebaran: Record<string, Sebaran>;
    jenisOff: Record<string, string>;
    urlOffStore: string;
}
