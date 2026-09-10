/**
 * KEMBARAN MAIA dari `resources/js/components/tinjau/SeksiTinjau.tsx` (REDESAIN-UI-V2 T2).
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
import type { ReactNode } from 'react';

import { Alert02Icon, Cancel01Icon, Message01Icon, OctagonXIcon, RoboticIcon, ShieldCheckIcon, Tick02Icon, TickDouble01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader } from '@/components/ui-maia/card';
import { Textarea } from '@/components/ui-maia/textarea';
import { cn } from '@/lib/utils';
import type { SeksiSchema } from '@/types/wizard';

import { useTinjau } from './konteks';

/**
 * Satu bab dokumen beserta kotak catatan peninjau — pengganti perulangan
 * `@foreach ($schema->allSections() ...)` di `review/show.blade.php`.
 *
 * Bab mana yang muncul di sini ditentukan SCHEMA + props `tipeDitinjau`
 * (`ReviewScreen::TIPE_DITINJAU`), bukan daftar nama bab yang diketik di sini:
 * bab baru yang ditambahkan lewat `schema_json` ikut bisa ditinjau tanpa satu
 * baris TSX pun disentuh (pakem P1).
 */
export function SeksiTinjau({ section, nilai }: { section: SeksiSchema; nilai: unknown }) {
    const items = Array.isArray(nilai) ? nilai : [];

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="bg-muted/50 rounded-t-xl border-b px-4 py-3 font-bold">
                {section.label ?? section.key}
            </CardHeader>
            <CardContent className="p-4">
                {section.type === 'jsa_analysis' ? (
                    <JsaTinjau seksi={section.key} langkah={items} />
                ) : (
                    <DaftarItem section={section} items={items} />
                )}
            </CardContent>
        </Card>
    );
}

/* ── Bab biasa: satu kotak catatan per item ────────────────────────────── */

function DaftarItem({ section, items }: { section: SeksiSchema; items: unknown[] }) {
    /*
    | Kolom bertipe `rich_text` (Deskripsi Aktivitas) menyimpan HTML, dan hanya
    | ia yang boleh dicetak sebagai HTML. Daftar kuncinya dibaca dari SCHEMA,
    | tak pernah ditulis tangan (pakem P1).
    |
    | Isinya SUDAH dibersihkan `PembersihHtml::bersihkan()` di server sebelum
    | masuk props — lihat ReviewScreen::bersihkanRichText(). Membersihkannya di
    | sini bukan pilihan: HTML yang sampai ke `data-page` sudah terlanjur ada di
    | halaman.
    */
    const kunciRich = (section.group_fields ?? section.fields ?? [])
        .filter((f) => f.type === 'rich_text')
        .map((f) => f.key);

    if (items.length === 0) {
        return <p className="text-muted-foreground text-sm">(Kosong)</p>;
    }

    return (
        <>
            {items.map((item, i) => (
                <div key={i} className="mb-3 grid gap-2 border-b pb-3 md:grid-cols-12">
                    <div className="md:col-span-7">
                        <div className="text-muted-foreground text-xs">Item {i + 1}</div>
                        <IsiItem item={item} kunciRich={kunciRich} />
                        <CatatanLama seksi={section.key} item={String(i)} />
                    </div>
                    <div className="md:col-span-5">
                        <KotakAnotasi
                            seksi={section.key}
                            item={String(i)}
                            placeholder="Catatan peninjau untuk item ini (opsional)"
                        />
                    </div>
                </div>
            ))}
        </>
    );
}

/**
 * Isi satu item.
 *
 * Aturan tampil-atau-tidaknya sebuah kolom disalin APA ADANYA dari Blade
 * (`review/show.blade.php:268`), termasuk kekhususannya: kunci `isi` yang
 * memuat jalur `lampiran/...` sengaja tak dicetak — itu nama berkas, bukan
 * kalimat yang perlu ditinjau. Yang bukan string tak pernah dicetak.
 */
function IsiItem({ item, kunciRich }: { item: unknown; kunciRich: string[] }) {
    if (typeof item !== 'object' || item === null) {
        return <div className="text-sm">{String(item ?? '')}</div>;
    }

    return (
        <>
            {Object.entries(item as Record<string, unknown>).map(([k, v]) => {
                if (kunciRich.includes(k)) {
                    return v ? (
                        <div key={k}>
                            <span className="text-muted-foreground text-xs">{k}:</span>
                            <div
                                className="pp-rt-isi"
                                // Sudah dibersihkan DI SERVER (lihat komentar di atas).
                                dangerouslySetInnerHTML={{ __html: String(v) }}
                            />
                        </div>
                    ) : null;
                }

                const tampil =
                    (Boolean(v) && k !== 'isi') ||
                    (typeof v === 'string' && !v.startsWith('lampiran/'));

                return tampil ? (
                    <div key={k} className="text-sm">
                        <span className="text-muted-foreground text-xs">{k}:</span>{' '}
                        {typeof v === 'string' ? v : ''}
                    </div>
                ) : null;
            })}
        </>
    );
}

/* ── Analisa JSA: bersarang tiga lapis ─────────────────────────────────── */

/** Satu Bahaya & Risiko di dalam sebuah Langkah Kerja. */
interface Bahaya {
    risiko?: string;
    pengendalian?: string[];
}

/** Satu Langkah Kerja. */
interface Langkah {
    langkah?: string;
    bahaya?: Bahaya[];
}

/**
 * Analisa JSA ditinjau SAMPAI bersarang: tiap Langkah Kerja, tiap Bahaya &
 * Risiko, dan tiap Tindakan Pengendalian punya kotak catatannya sendiri.
 *
 * Bentuk `item_ref` (`L0`, `L0-B1`, `L0-B1-P2`) SAMA persis dengan yang
 * dipakai Blade sejak awal dan dengan yang dibangun ulang server di
 * {@see ReviewDecision::refPengendalian()} — anotasi dari HP dan dari laptop
 * karena itu menunjuk item yang sama.
 */
function JsaTinjau({ seksi, langkah }: { seksi: string; langkah: unknown[] }) {
    // Dibaca SEKALI di sini, bukan lewat hook di dalam perulangan: hook tak
    // boleh dipanggil di dalam `.map()` (jumlah pemanggilannya ikut berubah
    // saat isi dokumen berubah, dan React memutus urutannya).
    const { verdicts } = useTinjau();

    if (langkah.length === 0) {
        return <p className="text-muted-foreground text-sm">(Belum ada analisa bahaya)</p>;
    }

    return (
        <>
            {(langkah as Langkah[]).map((step, li) => (
                <div key={li} className="mb-3 rounded-lg border p-2">
                    <BarisTinjau
                        seksi={seksi}
                        item={`L${li}`}
                        placeholder="Catatan untuk langkah kerja ini (opsional)"
                    >
                        <div className="text-muted-foreground text-xs">Langkah Kerja {li + 1}</div>
                        <div className="font-semibold">
                            {li + 1}. {step.langkah ?? ''}
                        </div>
                    </BarisTinjau>

                    {(step.bahaya ?? []).map((b, bi) => {
                        const refB = `L${li}-B${bi}`;
                        const kendali = b.pengendalian ?? [];

                        return (
                            <div key={bi} className="pl-3">
                                <BarisTinjau
                                    seksi={seksi}
                                    item={refB}
                                    placeholder="Catatan untuk bahaya ini (opsional)"
                                >
                                    <div className="text-destructive flex items-center gap-1.5 text-xs">
                                        <HugeiconsIcon icon={Alert02Icon} strokeWidth={1.5} className="size-3.5"  />
                                        Bahaya &amp; Risiko {li + 1}.{bi + 1}
                                    </div>
                                    <div className="text-sm">{b.risiko ?? ''}</div>
                                    <Borongan refB={refB} jumlah={kendali.length} />
                                </BarisTinjau>

                                {kendali.map((p, pi) => (
                                    <div key={pi} className="pl-5">
                                        <BarisTinjau
                                            seksi={seksi}
                                            item={`${refB}-P${pi}`}
                                            perluRevisi={verdicts[`${refB}-P${pi}`] === 'perlu_revisi'}
                                            placeholder="Catatan untuk pengendalian ini (opsional)"
                                        >
                                            <div className="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400">
                                                <HugeiconsIcon icon={ShieldCheckIcon} strokeWidth={1.5} className="size-3.5"  />
                                                Tindakan Pengendalian {li + 1}.{bi + 1}.{pi + 1}
                                            </div>
                                            <div className="text-sm">{p}</div>
                                            <TombolVerdict item={`${refB}-P${pi}`} nomor={`${li + 1}.${bi + 1}.${pi + 1}`} />
                                        </BarisTinjau>
                                    </div>
                                ))}
                            </div>
                        );
                    })}
                </div>
            ))}
        </>
    );
}

/**
 * Satu baris tinjauan: keterangan di kiri, kotak catatan di kanan.
 *
 * `perluRevisi` memasang batang merah di tepi kiri. Pada JSA panjang tombolnya
 * terlalu kecil untuk memberi tahu DI MANA temuannya saat menggulir; batang itu
 * menjawab tanpa menambah satu kata pun ke layar.
 */
function BarisTinjau({
    seksi,
    item,
    placeholder,
    perluRevisi = false,
    children,
}: {
    seksi: string;
    item: string;
    placeholder: string;
    perluRevisi?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'mb-2 grid gap-2 rounded-sm md:grid-cols-12',
                perluRevisi && 'border-destructive border-l-[3px] pl-2',
            )}
        >
            <div className="md:col-span-7">
                {children}
                <CatatanLama seksi={seksi} item={item} />
            </div>
            <div className="md:col-span-5">
                <KotakAnotasi seksi={seksi} item={item} placeholder={placeholder} />
            </div>
        </div>
    );
}

/**
 * Tanda ✓/✗ — HANYA di tingkat Tindakan Pengendalian.
 *
 * Di sinilah penilaian K3 sebenarnya terjadi; Langkah & Bahaya cukup diberi
 * catatan. Mulai KOSONG: peninjau harus benar-benar menilai tiap pengendalian,
 * bukan menekan Kirim atas centang bawaan. Server memeriksa ulang kelengkapannya
 * ({@see ReviewDecision::verdictSah}) — layar bisa dilewati, server tidak.
 *
 * `<input type="radio">` SUNGGUHAN, bukan `RadioGroup` Radix: panah keyboard
 * berpindah antar-pilihan dan pembaca layar membacanya sebagai satu grup, tanpa
 * satu baris JavaScript. Alasan yang sama dengan `PapanPilihPeninjau`.
 *
 * Warnanya dibedakan di TIGA sumbu, bukan hanya rona — Sesuai hijau tua & padat,
 * Perlu Revisi merah menyala BERCINCIN. Kebutaan warna merah-hijau adalah yang
 * paling umum, dan ini formulir keselamatan.
 */
function TombolVerdict({ item, nomor }: { item: string; nomor: string }) {
    const { pakaiVerdict, verdicts, setVerdict } = useTinjau();

    if (!pakaiVerdict) return null;

    // Glif hugeicons adalah DATA, bukan komponen (§5) — jadi ia diteruskan
    // lewat prop `icon`, tak pernah dipanggil sebagai `<Ikon />` seperti
    // kembarannya yang lucide.
    const pilihan = [
        { nilai: 'sesuai', label: 'Sesuai', glif: Tick02Icon },
        { nilai: 'perlu_revisi', label: 'Perlu Revisi', glif: Cancel01Icon },
    ] as const;

    return (
        <div className="mt-2 flex" role="group" aria-label={`Penilaian pengendalian ${nomor}`}>
            {pilihan.map(({ nilai, label, glif }, i) => (
                <label
                    key={nilai}
                    className={cn(
                        'relative inline-flex cursor-pointer items-center gap-1 border px-2.5 py-1 text-xs font-medium transition-colors',
                        'has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ring',
                        i === 0 ? 'rounded-l-md' : '-ml-px rounded-r-md',
                        verdicts[item] === nilai
                            ? nilai === 'sesuai'
                                ? 'z-10 border-emerald-700 bg-emerald-700 font-semibold text-white'
                                : 'border-destructive bg-destructive text-destructive-foreground ring-destructive/30 z-10 font-semibold ring-3'
                            : 'hover:bg-accent',
                    )}
                >
                    <input
                        type="radio"
                        className="sr-only"
                        name={`verdicts[${item}]`}
                        value={nilai}
                        required
                        checked={verdicts[item] === nilai}
                        onChange={() => setVerdict(item, nilai)}
                    />
                    <HugeiconsIcon icon={glif} strokeWidth={1.5} className="size-3.5" />
                    {label}
                </label>
            ))}
        </div>
    );
}

/**
 * Penanda borongan satu bahaya: mengisi seluruh pengendalian di bawahnya.
 *
 * Sebagian besar bahaya pengendaliannya sesuai semua; memaksa peninjau menekan
 * tiap baris satu-satu hanya menambah ketukan tanpa menambah ketelitian. Tanda
 * per baris TETAP ada dan tetap wajib — tombol ini cuma mengisinya sekaligus.
 *
 * Muncul hanya bila pengendaliannya LEBIH DARI SATU; untuk bahaya
 * berpengendalian tunggal ia cuma menduplikasi tombol tepat di bawahnya.
 */
function Borongan({ refB, jumlah }: { refB: string; jumlah: number }) {
    const { pakaiVerdict, setVerdictBorongan } = useTinjau();

    if (!pakaiVerdict || jumlah <= 1) return null;

    const refs = Array.from({ length: jumlah }, (_, i) => `${refB}-P${i}`);

    return (
        <div className="mt-2 flex flex-wrap items-center gap-2">
            <span className="text-muted-foreground text-xs">Tandai {jumlah} pengendalian sekaligus:</span>
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setVerdictBorongan(refs, 'sesuai')}
            >
                <HugeiconsIcon icon={TickDouble01Icon} strokeWidth={1.5} />
                Semua Sesuai
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setVerdictBorongan(refs, 'perlu_revisi')}
            >
                <HugeiconsIcon icon={OctagonXIcon} strokeWidth={1.5} />
                Semua Perlu Revisi
            </Button>
        </div>
    );
}

/* ── Potongan bersama ──────────────────────────────────────────────────── */

/** Catatan putaran peninjauan sebelumnya — tetap terlihat selama revisi (§3.3). */
function CatatanLama({ seksi, item }: { seksi: string; item: string }) {
    const daftar = useTinjau().anotasiLama[seksi]?.[item] ?? [];

    return (
        <>
            {daftar.map((komentar, i) => (
                <div key={i} className="text-destructive mt-1 flex gap-1.5 text-xs">
                    <HugeiconsIcon icon={Message01Icon} strokeWidth={1.5} className="size-3.5 shrink-0"  />
                    <span>Catatan sebelumnya: {komentar}</span>
                </div>
            ))}
        </>
    );
}

/**
 * Kotak catatan satu item.
 *
 * `data-annot` dipertahankan dari Blade: panel AI mengantar layar ke kotak
 * tujuan sesudah sebuah temuan diadopsi, dan pada analisa JSA panjang kotak itu
 * hampir selalu di luar layar.
 */
function KotakAnotasi({
    seksi,
    item,
    placeholder,
}: {
    seksi: string;
    item: string;
    placeholder: string;
}) {
    const { catatan, ubahCatatan, dariAi } = useTinjau();

    return (
        <>
            <Textarea
                data-annot={`${seksi}-${item}`}
                rows={2}
                className="text-sm"
                placeholder={placeholder}
                value={catatan[seksi]?.[item] ?? ''}
                onChange={(e) => ubahCatatan(seksi, item, e.target.value)}
            />
            {dariAi[seksi]?.[item] ? (
                <div className="text-primary mt-1 flex items-center gap-1.5 text-xs">
                    <HugeiconsIcon icon={RoboticIcon} strokeWidth={1.5} className="size-3.5"  />
                    Diadopsi dari saran AI
                </div>
            ) : null}
        </>
    );
}
