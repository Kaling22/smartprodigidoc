/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/PapanKetersediaan.tsx` (REDESAIN-UI-V2 T2).
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
import { Alert02Icon, HourglassIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Label } from '@/components/ui-maia/label';
import { cn } from '@/lib/utils';
import type { Kandidat, Ketersediaan, SelPita } from '@/types/wizard';

import { Bantuan, useWizard, type PropsField } from './konteks';

/**
 * Papan Ketersediaan Peninjau — pengganti
 * `documents/fields/_papan-ketersediaan.blade.php` (FITUR-BARU-v4 §6).
 *
 * Menggantikan dropdown untuk seksi `peninjau`, `peninjau_penyetuju`, dan
 * `penyetuju`. Semua kandidat berbaris di bawah SATU sumbu waktu, jadi hari
 * yang sama sejajar antar-orang: GL bisa memindai satu kolom ke bawah dan
 * menyimpulkan "Kamis semua sudah masuk". Tugasnya memang MEMBANDINGKAN, bukan
 * membaca satu per satu — itulah yang tak bisa dilakukan dropdown.
 *
 * Nama hari sengaja TIDAK ditulis (berbeda dengan kalender di kartu dashboard).
 * Empat belas label mungil berjejer justru membuat papan terlihat dempet; yang
 * dibutuhkan GL hanyalah tahu di mana satu minggu berakhir — itu diberikan oleh
 * celah pemisah + penanda awal minggu.
 *
 * Kendalinya tetap `<input type="radio">` SUNGGUHAN, dibungkus `<label>`
 * selebar baris. Konsekuensinya gratis dan tanpa satu baris JavaScript: klik di
 * mana saja pada baris memilihnya, panah keyboard berpindah antar-kandidat,
 * fokus terlihat, pembaca layar membacanya sebagai satu grup pilihan, dan
 * kandidat yang penuh/off cukup diberi `disabled` — peramban sendiri yang
 * mencegahnya. Radix `RadioGroup` sengaja TIDAK dipakai di sini: ia menukar
 * radio asli dengan tombol ber-`role`, dan `validasiWajib()` memindai
 * `input[type=radio][required]` yang sungguhan.
 *
 * ATURAN LEBAR yang diwarisi utuh: tak ada satu pun sel berlebar tetap. Semua
 * `flex-1` / `1fr`. Versi sebelumnya mematok 1,15rem — akibatnya nama hari
 * terjepit sementara sisa lebar kartu dibiarkan kosong (CLAUDE.md §6).
 */
export function PapanKetersediaan({ section, value, onChange }: PropsField) {
    const { candidates, ketersediaan, papan, ambang } = useWizard();

    return (
        <PapanPilihPeninjau
            section={section}
            value={value}
            onChange={onChange}
            nama={`sections[${section.key}]`}
            options={candidates[section.key] ?? []}
            jadwal={ketersediaan[section.key] ?? {}}
            memblokir={papan[section.key] ?? true}
            ambang={ambang}
        />
    );
}

/**
 * Isi papannya, TANPA konteks wizard.
 *
 * Dipisah karena papan yang sama dipakai di luar wizard: jendela "Alihkan
 * Peninjauan" (Fase 10) memilih peninjau JSA dengan pertanyaan yang persis
 * sama — "siapa yang paling lapang". Blade lama menjawabnya dengan
 * meng-`@include` partial yang sama beserta enam variabelnya; di sini
 * jawabannya satu komponen yang menerima daftarnya lewat props.
 *
 * Menyalinnya jadi papan kedua berarti dua papan yang harus sepakat tiap kali
 * aturan lebar sel atau rupa pil beban berubah — dan suatu hari tidak sepakat.
 */
export function PapanPilihPeninjau({
    section,
    value,
    onChange,
    nama,
    options,
    jadwal,
    memblokir,
    ambang,
}: PropsField & {
    /** Nama grup radio; menentukan panah keyboard berpindah antar-kandidat mana. */
    nama: string;
    options: Kandidat[];
    jadwal: Record<number, Ketersediaan>;
    memblokir: boolean;
    ambang: { padat: number; sibuk: number; jenis: string[] };
}) {
    const required = section.required ?? false;
    const terpilih = value === null || value === undefined || value === '' ? null : Number(value);

    // Kandidat yang benar-benar bisa dipilih. Pada seksi penyetuju semua selalu
    // bisa dipilih — `memblokir === false` (lihat ReviewerAvailability::PAPAN).
    const bisaDipilih = (o: Kandidat) => !memblokir || (jadwal[o.id]?.tersedia ?? true);
    const adaYangTersedia = options.some(bisaDipilih);

    // Siapa yang paling cepat bebas — dipakai keadaan buntu di bawah.
    const tercepat = options
        .map((o) => jadwal[o.id])
        .filter((a): a is Ketersediaan => Boolean(a?.kembaliIso))
        .sort((a, b) => (a.kembaliIso ?? '').localeCompare(b.kembaliIso ?? ''))[0];

    // Legenda tingkat hanya dicetak bila warnanya BENAR-BENAR muncul di papan
    // ini. Menjelaskan kuning/merah di papan SOP yang seluruhnya netral cuma
    // menyuruh orang menghafal bahasa visual yang tak pernah ia lihat.
    const adaTingkat =
        memblokir && options.some((o) => (jadwal[o.id]?.tingkat ?? 'normal') !== 'normal');

    const pitaPertama = options.length ? (jadwal[options[0].id]?.pita ?? []) : [];

    // Sel ke-0 dan ke-7 memulai minggu baru → diberi celah pemisah.
    const pekanBaru = (i: number) => i > 0 && i % 7 === 0;

    /** Susunan kolom baris papan; ikut dipakai baris kepala agar sejajar. */
    const baris = cn(
        'grid items-center gap-2 md:gap-4',
        memblokir
            ? 'grid-cols-1 md:grid-cols-[minmax(0,14rem)_1fr_auto]'
            : 'grid-cols-1 md:grid-cols-[minmax(0,14rem)_1fr]',
    );

    /** Layar sempit: pita dipotong jadi 7 hari, bukan digulir mendatar. */
    const selLanjut = (i: number) => (i >= 7 ? 'hidden md:block' : '');

    return (
        <div className="mb-6">
            <Label className="mb-1.5 font-semibold">
                {section.label ?? section.key}
                {required ? <span className="text-destructive"> *</span> : null}
            </Label>
            <Bantuan teks={section.hint} />

            {options.length === 0 ? (
                <p className="flex items-center gap-2 rounded-lg border border-chart-3/40 bg-chart-3/10 px-3 py-2 text-sm">
                    <HugeiconsIcon icon={Alert02Icon} strokeWidth={1.5} className="size-4 shrink-0"  />
                    Belum ada kandidat untuk peran ini.
                </p>
            ) : (
                <>
                    <div>
                        {/* Sumbu waktu: penanda awal tiap minggu. Sejajar dengan
                            pita tiap baris karena keduanya memakai susunan kolom
                            dan jumlah sel yang sama. */}
                        <div className={cn(baris, 'hidden pb-1 pl-10 md:grid')}>
                            <span className="text-[0.7rem] text-muted-foreground">Pilih satu baris</span>
                            <span className="flex gap-[3px] px-1" aria-hidden="true">
                                {pitaPertama.map((sel, i) => (
                                    <span
                                        key={i}
                                        className={cn(
                                            'min-w-0 flex-1 text-xs text-foreground/70',
                                            pekanBaru(i) && 'ml-2',
                                            selLanjut(i),
                                        )}
                                    >
                                        {i === 0 || pekanBaru(i) ? (
                                            <span className={cn('whitespace-nowrap', i === 0 && 'font-semibold text-primary')}>
                                                {sel.tanggal}
                                            </span>
                                        ) : null}
                                    </span>
                                ))}
                            </span>
                            {memblokir ? (
                                <span className="text-right text-[0.7rem] whitespace-nowrap text-muted-foreground">Beban</span>
                            ) : null}
                        </div>

                        {options.map((o) => {
                            const a = jadwal[o.id];
                            const terkunci = !bisaDipilih(o);

                            return (
                                <label
                                    key={o.id}
                                    className={cn('relative mb-2 block', terkunci ? 'cursor-not-allowed' : 'cursor-pointer')}
                                >
                                    <input
                                        type="radio"
                                        className="peer absolute top-5 left-3.5 z-10 size-4 accent-primary"
                                        name={nama}
                                        value={o.id}
                                        checked={terpilih === o.id}
                                        disabled={terkunci}
                                        required={required}
                                        onChange={() => onChange(o.id)}
                                    />

                                    <span
                                        className={cn(
                                            'block rounded-xl border border-l-[3px] border-l-transparent py-2.5 pr-3.5 pl-10 transition-colors',
                                            'peer-checked:border-l-primary peer-checked:bg-accent',
                                            'peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ring',
                                            terkunci ? 'opacity-55' : 'hover:bg-accent/50',
                                        )}
                                    >
                                        <span className={baris}>
                                            <span className="min-w-0">
                                                <span className="block truncate text-sm font-semibold">{o.nama}</span>
                                                <span className="block truncate text-xs text-muted-foreground">
                                                    {o.jabatan} · {o.dept} · {o.nrp}
                                                </span>
                                                <span className="mt-1 block text-sm">
                                                    <span
                                                        className={cn(
                                                            terkunci || a?.off
                                                                ? 'font-semibold text-chart-3'
                                                                : 'text-muted-foreground',
                                                        )}
                                                    >
                                                        {/* "Penuh" tak berlaku di seksi penyetuju — di sana yang
                                                            relevan hanya jadwalnya, dan itu pun sekadar keterangan. */}
                                                        {!memblokir && a?.penuh && !a.off ? 'Tersedia hari ini' : (a?.alasan ?? '')}
                                                    </span>
                                                </span>
                                            </span>

                                            <Pita pita={a?.pita ?? []} nama={o.nama} pekanBaru={pekanBaru} selLanjut={selLanjut} />

                                            {/* Beban peninjauan sebagai ANGKA, kolom tersendiri rata
                                                kanan: angka semua kandidat berbaris vertikal sehingga
                                                terbandingkan sekali lihat. Nol tetap dicetak —
                                                kekosongan yang eksplisit lebih terbaca. TIDAK dirender
                                                di seksi penyetuju: di sana angka ini mengukur beban
                                                PENINJAUAN, tak ada hubungannya dengan menyetujui. */}
                                            {memblokir ? (
                                                <span className="order-first text-right whitespace-nowrap md:order-none">
                                                    <span
                                                        className={cn(
                                                            'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium',
                                                            WARNA_TINGKAT[a?.tingkat ?? 'normal'],
                                                        )}
                                                        data-tingkat={a?.tingkat ?? 'normal'}
                                                        // `role="img"` bukan hiasan: tanpanya `aria-label`
                                                        // pada <span> polos diabaikan banyak pembaca layar,
                                                        // dan yang terdengar tinggal "5 dok" telanjang.
                                                        role="img"
                                                        title={`${a?.beban ?? 0} dokumen sedang ditinjau`}
                                                        aria-label={`${a?.beban ?? 0} dokumen sedang ditinjau`}
                                                    >
                                                        {a?.beban ?? 0}
                                                        <span className="ml-1 font-normal opacity-75">dok</span>
                                                    </span>
                                                </span>
                                            ) : null}
                                        </span>
                                    </span>
                                </label>
                            );
                        })}
                    </div>

                    <div className="mt-2 flex flex-wrap gap-3 text-[0.7rem] text-muted-foreground">
                        <span className="flex items-center gap-1.5">
                            <i className="inline-block h-2 w-3.5 rounded-sm bg-primary" /> cuti/off
                        </span>
                        <span className="flex items-center gap-1.5">
                            <i className="inline-block h-2 w-3.5 rounded-sm bg-card ring-1 ring-border" /> tersedia
                        </span>
                        {/* "Kantor tutup" tak punya entri karena tak punya rupa sendiri:
                            akhir pekan tampil sama persis dengan hari kerja. Keterangannya
                            ada di `title` tiap sel. */}
                        {adaTingkat ? (
                            <>
                                <span>
                                    <span className={cn('rounded-md border px-1.5 py-0.5', WARNA_TINGKAT.padat)}>padat</span>{' '}
                                    {ambang.padat}–{ambang.sibuk - 1} dokumen
                                </span>
                                <span>
                                    <span className={cn('rounded-md border px-1.5 py-0.5', WARNA_TINGKAT.sibuk)}>sibuk</span>{' '}
                                    {ambang.sibuk} dokumen atau lebih
                                </span>
                            </>
                        ) : null}
                        <span>Sumbu waktu mulai hari ini.</span>
                    </div>

                    {memblokir && !adaYangTersedia ? (
                        // Buntu: bisa terjadi di departemen yang hanya punya satu SH.
                        // Papan tetap ditampilkan di atas — GL berhak melihat SEBABNYA —
                        // lalu diberi tahu apa yang bisa ia lakukan sekarang.
                        <div className="mt-2 rounded-lg border border-chart-3/40 bg-chart-3/10 px-3 py-2 text-sm">
                            <p className="flex items-center gap-1.5 font-semibold">
                                <HugeiconsIcon icon={HourglassIcon} strokeWidth={1.5} className="size-4"  />
                                Belum ada kandidat yang bisa dipilih sekarang.
                            </p>
                            Simpan dokumen ini sebagai draft dulu.{' '}
                            {tercepat?.kembali ? (
                                <>
                                    Peninjau berikutnya bisa menerima dokumen lagi pada{' '}
                                    <strong>{tercepat.kembali}</strong>.
                                </>
                            ) : null}
                        </div>
                    ) : null}
                </>
            )}
        </div>
    );
}

/**
 * Warna pil beban.
 *
 * `normal` sengaja memakai kelas netral yang sama dengan jenis dokumen tanpa
 * ambang — jadi SOP/IK/SP selalu jatuh ke sini.
 */
const WARNA_TINGKAT: Record<string, string> = {
    normal: 'border-transparent bg-secondary text-secondary-foreground',
    padat: 'border-chart-3/40 bg-chart-3/10 text-chart-3',
    sibuk: 'border-destructive/40 bg-destructive/10 text-destructive',
};

/**
 * Pita 14 hari. Alur latar membuat batang-batangnya terbaca sebagai SATU garis
 * waktu, bukan titik-titik lepas.
 */
function Pita({
    pita,
    nama,
    pekanBaru,
    selLanjut,
}: {
    pita: SelPita[];
    nama: string;
    pekanBaru: (i: number) => boolean;
    selLanjut: (i: number) => string;
}) {
    return (
        <span className="flex gap-[3px] rounded-lg bg-muted/60 p-1">
            {pita.map((sel, i) => (
                <span
                    key={i}
                    // Akhir pekan & libur nasional TAMPIL SAMA PERSIS dengan hari
                    // kerja (ketetapan pemilik). Yang dibedakan pita ini cuma SATU
                    // hal: orangnya ada atau tidak. Keterangannya tak hilang —
                    // `title` tetap berbunyi "Akhir pekan" / "Libur nasional".
                    className={cn(
                        'h-3.5 min-w-0 flex-1 rounded-sm',
                        sel.status === 'off' ? 'bg-primary' : 'bg-card ring-1 ring-border',
                        pekanBaru(i) && 'ml-2',
                        selLanjut(i),
                    )}
                    title={`${nama} — ${sel.label}`}
                />
            ))}
        </span>
    );
}
