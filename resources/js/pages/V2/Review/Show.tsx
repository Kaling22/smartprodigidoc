import {
    Album02Icon,
    Exchange01Icon,
    File01Icon,
    HourglassIcon,
    InformationCircleIcon,
    ArrowTurnBackwardIcon,
    OctagonXIcon,
    SentIcon,
    Tick02Icon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader } from '@/components/ui-maia/card';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { Textarea } from '@/components/ui-maia/textarea';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DialogAlihkan } from '@/components/v2/tinjau/DialogAlihkan';
import { PanelAi } from '@/components/v2/tinjau/PanelAi';
import { PenyediaTinjau, type KonteksTinjau } from '@/components/v2/tinjau/konteks';
import { SeksiTinjau } from '@/components/v2/tinjau/SeksiTinjau';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { LampiranTinjau, ReviewShowProps } from '@/types/tinjau';
import type { SeksiSchema } from '@/types/wizard';

/**
 * Layar tinjauan per-item, kerangka V2 — kembaran maia dari
 * `pages/Review/Show.tsx`.
 *
 * SATU halaman melayani TIGA layar, persis seperti versi nova dan Blade
 * sebelumnya: peninjauan SH/DH (substansi), peninjauan Management Development
 * (penulisan), dan Masukan Sejawat antar-GL (tanpa keputusan). Yang membedakan
 * hanya props — alamat tujuan, teks petunjuk, label tombol, dan ada-tidaknya
 * panel AI & tombol Alihkan. Bab mana yang bisa ditinjau datang dari SCHEMA +
 * props `tipeDitinjau`; tak satu pun nama bab diketik di sini (pakem P1).
 *
 * Perubahan kerangka, sejalan dengan wizard: blok "tautan Kembali + judul +
 * lencana + dua tombol" yang di versi nova berdiri sendiri di atas isi kini
 * larut ke blok judul layout. Jalan kembalinya TIDAK hilang — ia jadi remah
 * pertama, dan labelnya tetap `backLabel` milik SERVER, bukan kalimat yang
 * dikarang di sini.
 *
 * H1 memuat nama layar DAN judul dokumen ("Tinjau: SOP Perawatan Genset").
 * Layout memakai satu string yang sama untuk H1 dan `<title>` tab, dan tab yang
 * cuma berbunyi judul dokumen tak memberi tahu peninjau — yang sering membuka
 * beberapa dokumen sekaligus — sedang apa ia di sana.
 */
export default function ReviewShow() {
    const props = usePage<PageProps & ReviewShowProps>().props;
    const {
        document: doc,
        schema,
        tipeDitinjau,
        contentMap,
        anotasiLama,
        lampiran,
        formAction,
        backUrl,
        pakaiVerdict,
        aiUrl,
        pengalihan,
        alihKandidat,
    } = props;

    const [catatan, setCatatan] = useState<Record<string, Record<string, string>>>({});
    const [dariAi, setDariAi] = useState<Record<string, Record<string, boolean>>>({});
    const [verdicts, setVerdicts] = useState<Record<string, string>>({});
    const [ringkasan, setRingkasan] = useState('');
    const [keputusan, setKeputusan] = useState<'approve' | 'reject' | 'kirim' | null>(null);
    const [alih, setAlih] = useState(Boolean(props.alihOtomatis));

    const seksi = (schema.steps ?? [])
        .flatMap((s) => s.sections ?? [])
        .filter((s: SeksiSchema) => tipeDitinjau.includes(s.type));

    const ubahCatatan = (s: string, item: string, teks: string) =>
        setCatatan((lama) => ({ ...lama, [s]: { ...lama[s], [item]: teks } }));

    const konteks: KonteksTinjau = {
        catatan,
        ubahCatatan,
        dariAi,
        anotasiLama,
        verdicts,
        setVerdict: (item, nilai) => setVerdicts((lama) => ({ ...lama, [item]: nilai })),
        setVerdictBorongan: (refs, nilai) =>
            setVerdicts((lama) => ({ ...lama, ...Object.fromEntries(refs.map((r) => [r, nilai])) })),
        pakaiVerdict,
    };

    /*
    | Daftar kotak catatan yang BENAR-BENAR ada di layar ini, per seksi.
    |
    | Inilah yang membuat Adopsi tak pernah lagi salah alamat. `item_ref` dari
    | AI tidak bisa diandalkan — diukur pada JSA nyata, model memulangkannya
    | KOSONG untuk seluruh temuan — dan menebaknya dari isi dokumen hanya
    | berhasil bila modelnya kebetulan mengutip baris yang dimaksud. Jadi
    | tujuannya tidak ditebak: ia DIPILIH peninjau dari daftar ini, dengan
    | tebakan AI (bila ada) sebagai pilihan awal.
    |
    | Bentuk `ref`-nya sama persis dengan `data-annot` yang dipasang
    | `SeksiTinjau` dan dengan `item_ref` yang disimpan server — satu-satunya
    | sebab pilihan di sini pasti ketemu kotaknya.
    */
    const tujuan: Record<string, { ref: string; label: string }[]> = Object.fromEntries(
        seksi.map((s) => {
            const isi = contentMap[s.key];
            const daftar: { ref: string; label: string }[] = [];

            if (!Array.isArray(isi)) return [s.key, daftar];

            const potong = (teks: unknown) => {
                const t = String(teks ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

                return t.length > 32 ? `${t.slice(0, 32)}…` : t;
            };

            if (s.type === 'jsa_analysis') {
                (isi as Record<string, unknown>[]).forEach((step, li) => {
                    daftar.push({ ref: `L${li}`, label: `Langkah ${li + 1} — ${potong(step?.langkah)}` });

                    ((step?.bahaya ?? []) as Record<string, unknown>[]).forEach((b, bi) => {
                        daftar.push({
                            ref: `L${li}-B${bi}`,
                            label: `${li + 1}.${bi + 1} Bahaya — ${potong(b?.risiko)}`,
                        });

                        ((b?.pengendalian ?? []) as unknown[]).forEach((p, pi) => {
                            daftar.push({
                                ref: `L${li}-B${bi}-P${pi}`,
                                label: `${li + 1}.${bi + 1}.${pi + 1} Kendali — ${potong(p)}`,
                            });
                        });
                    });
                });

                return [s.key, daftar];
            }

            (isi as unknown[]).forEach((item, i) => {
                const teks =
                    item && typeof item === 'object'
                        ? Object.values(item as Record<string, unknown>)
                              .filter((v) => typeof v === 'string')
                              .join(' ')
                        : item;

                daftar.push({ ref: String(i), label: `Item ${i + 1} — ${potong(teks)}` });
            });

            return [s.key, daftar];
        }),
    );

    /*
    | Menaruh saran ke kotak catatan satu baris. Refnya datang dari pilihan
    | peninjau di panel AI, jadi di sini tak ada lagi tebakan maupun jalan
    | mundur ke "kotak pertama seksi" — jalan mundur itulah yang dulu menumpuk
    | belasan temuan jadi satu anotasi di Langkah Kerja #1.
    */
    const adopsi = (sectionKey: string, itemRef: string | number | null, saran: string) => {
        /*
        | Temuan hanya boleh masuk ke kotak yang BENAR-BENAR ditunjuknya.
        |
        | Dulu ada dua jaring pengaman: kalau `item_ref` tak punya kotak, coba
        | item "0"; kalau itu pun tak ada, pakai KOTAK PERTAMA seksi itu. Jaring
        | kedua itulah yang merusak analisa JSA: refnya `L0`/`L0-B1`/`L0-B1-P2`,
        | jadi tiap temuan tanpa ref yang sah jatuh ke `analisa-L0` — kotak
        | Langkah Kerja #1 — dan disambung dengan "\n". Belasan temuan berakhir
        | sebagai SATU anotasi di kolom yang salah, sementara bahaya dan
        | pengendalian yang sebenarnya ditunjuk tetap kosong.
        |
        | Temuan tanpa tujuan yang sah tidak hilang: `PanelAi::adopsi()`
        | mengalihkannya ke Ringkasan begitu fungsi ini memulangkan `false` —
        | dan itu memang tempatnya menurut kontrak AI sendiri, yang menyuruh
        | `item_ref` dikosongkan HANYA bila temuan berlaku untuk seluruh seksi
        | ({@see \App\Services\Ai\AbstractAiReviewer}).
        */
        const ref = itemRef === null || itemRef === undefined || itemRef === '' ? null : String(itemRef);

        if (!ref || !document.querySelector(`[data-annot="${sectionKey}-${ref}"]`)) return false;

        const lama = catatan[sectionKey]?.[ref] ?? '';
        ubahCatatan(sectionKey, ref, lama ? `${lama}\n${saran}` : saran);
        setDariAi((l) => ({ ...l, [sectionKey]: { ...l[sectionKey], [ref]: true } }));

        // Analisa JSA panjang: kotak tujuannya hampir selalu di luar layar,
        // sehingga tanpa ini adopsi terasa seperti temuan yang lenyap.
        document
            .querySelector(`[data-annot="${sectionKey}-${ref}"]`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });

        return true;
    };

    const kirim = (decision?: 'approve' | 'reject') =>
        router.post(formAction, {
            ...(decision ? { decision } : {}),
            summary: ringkasan,
            annotations: catatan,
            annotations_ai: dariAi,
            verdicts,
        });

    return (
        <AppLayout
            judul={`${props.judulLayar ?? 'Tinjau'}: ${doc.judul}`}
            // Remah pertama memakai `backLabel` APA ADANYA. Kalimatnya milik
            // server (controller masing-masing layar), dan menukarnya jadi nama
            // tempat karangan berarti tiga layar yang harus disepakati ulang di
            // TSX — persis yang dihindari pakem P3.
            remah={[{ label: props.backLabel ?? 'Kembali ke antrian', href: backUrl }, { label: doc.nomor }]}
            sub={
                <span className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span className="text-primary font-mono font-semibold">{doc.nomor}</span>
                    <Badge variant="outline">{doc.dept ?? '—'}</Badge>
                    {/* EDISI & REVISI DOKUMEN — angka yang sama dengan yang
                        tercetak di kop PDF. Dulu di sini terpasang
                        `revision_round`, penghitung berapa kali dokumen
                        DIKEMBALIKAN, sehingga dokumen Edisi 1 Revisi 2
                        terbaca "Revisi ke-0" oleh peninjaunya. */}
                    <Badge variant="secondary">
                        Edisi {doc.edisi} · Revisi {doc.noRevisi}
                    </Badge>
                    {doc.putaran > 0 ? <Badge variant="outline">Putaran tinjauan ke-{doc.putaran + 1}</Badge> : null}
                </span>
            }
            aksi={
                <>
                    {/* Alihkan — hanya JSA yang benar-benar ada di tangan orang
                        ini, dan hanya bila ada peninjau lain yang bisa
                        menerimanya. Syaratnya dihitung controller
                        (`kandidatAlih`), jadi layar tak punya salinan aturan. */}
                    {alihKandidat && alihKandidat.length > 0 ? (
                        <Button variant="outline" size="sm" onClick={() => setAlih(true)}>
                            <HugeiconsIcon icon={Exchange01Icon} strokeWidth={1.5} />
                            Alihkan
                        </Button>
                    ) : null}
                    <Button asChild variant="outline" size="sm">
                        <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                            <HugeiconsIcon icon={File01Icon} strokeWidth={1.5} />
                            Lihat PDF
                        </a>
                    </Button>
                </>
            }
        >
            {/* Alasan pengalihan (PLAN B / B4). Lonceng menyimpan `message`
                saja, jadi tanpa kotak ini penerima kehilangan sebab pengalihan
                begitu notifikasinya ditandai terbaca. Dibaca ulang dari audit
                log, sehingga ia bertahan sekeras jejaknya. */}
            {pengalihan ? (
                <div className="bg-accent flex gap-2 rounded-xl border px-3 py-2 text-sm">
                    <HugeiconsIcon icon={Exchange01Icon} strokeWidth={1.5} className="mt-0.5 size-4 shrink-0" />
                    <div>
                        <strong>Dialihkan oleh {pengalihan.dari}</strong>{' '}
                        <span className="text-muted-foreground">· {pengalihan.waktu}</span>
                        <div className="whitespace-pre-line">{pengalihan.alasan}</div>
                    </div>
                </div>
            ) : null}

            {/* Datang dari tombol Alihkan di antrian, tapi ternyata tak ada
                peninjau lain yang bisa menerima. Tombol di sana sengaja hanya
                memeriksa syarat murah, jadi sebabnya dijelaskan DI SINI — kalau
                tidak, tombolnya tampak rusak: diklik, halaman berganti, tak
                terjadi apa-apa. */}
            {props.alihOtomatis && (!alihKandidat || alihKandidat.length === 0) ? (
                <p className="border-chart-3/40 bg-chart-3/10 flex gap-2 rounded-xl border px-3 py-2 text-sm">
                    <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="mt-0.5 size-4 shrink-0" />
                    Pengalihan tidak tersedia untuk dokumen ini — tidak ada peninjau lain yang berwenang dan sedang bisa
                    menerimanya.
                </p>
            ) : null}

            <div className="bg-accent flex gap-2 rounded-xl border px-3 py-2 text-sm">
                <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="mt-0.5 size-4 shrink-0" />
                {/* Teks petunjuk memuat `<strong>` dan seluruhnya milik SERVER
                    (konstanta di controller), bukan masukan pengguna — lihat
                    MdReviewController & MasukanSejawatController. */}
                <span
                    dangerouslySetInnerHTML={{
                        __html:
                            props.petunjuk ??
                            'Beri catatan pada item yang perlu diperbaiki. Kosongkan bila item sudah sesuai. Bila ada satu saja catatan, pilih <strong>Kembalikan untuk Revisi</strong>.',
                    }}
                />
            </div>

            {aiUrl ? (
                <PanelAi
                    url={aiUrl}
                    onAdopsi={adopsi}
                    tujuan={tujuan}
                    onKeRingkasan={(saran) => setRingkasan((l) => (l ? `${l}\n${saran}` : saran))}
                />
            ) : null}

            <PenyediaTinjau value={konteks}>
                {seksi.map((s) => (
                    <SeksiTinjau key={s.key} section={s} nilai={contentMap[s.key]} />
                ))}

                <Card>
                    <CardContent>
                        <Label htmlFor="ringkasan" className="mb-1.5 font-semibold">
                            Ringkasan / Catatan Umum (opsional)
                        </Label>
                        <Textarea
                            id="ringkasan"
                            rows={2}
                            className="mb-3"
                            placeholder="Ringkasan hasil tinjauan..."
                            value={ringkasan}
                            onChange={(e) => setRingkasan(e.target.value)}
                        />

                        <TombolKeputusan props={props} verdicts={verdicts} onPilih={setKeputusan} backUrl={backUrl} />
                    </CardContent>
                </Card>
            </PenyediaTinjau>

            <KartuLampiran daftar={lampiran} />

            {alihKandidat && alihKandidat.length > 0 ? (
                <DialogAlihkan
                    buka={alih}
                    onUbahBuka={setAlih}
                    nomor={doc.nomor}
                    url={props.alihUrl ?? ''}
                    kandidat={alihKandidat}
                    ketersediaan={props.alihKetersediaan ?? {}}
                    ambang={props.ambang ?? { padat: 0, sibuk: 0, jenis: [] }}
                />
            ) : null}

            <ConfirmDialog
                buka={keputusan !== null}
                onUbahBuka={(b) => (b ? null : setKeputusan(null))}
                judul={judulKonfirmasi(keputusan)}
                pesan={pesanKonfirmasi(props, keputusan, verdicts)}
                tombolYa={keputusan === 'reject' ? 'Ya, kembalikan' : 'Ya, kirim'}
                onKonfirmasi={() => {
                    kirim(keputusan === 'kirim' ? undefined : (keputusan ?? undefined));
                    setKeputusan(null);
                }}
            />
        </AppLayout>
    );
}

/**
 * Tiga bentuk tombol, sesuai layar yang sedang dipakai.
 *
 * Pada JSA keputusan DITURUNKAN dari tanda, bukan dipilih: satu ✗ saja berarti
 * dokumen dikembalikan, jadi dua tombol "Loloskan"/"Kembalikan" hanya akan
 * bertentangan dengan tanda yang baru saja dipasang peninjau. Yang ditampilkan
 * adalah AKIBATNYA, dihitung ulang tiap kali sebuah tanda berubah.
 */
function TombolKeputusan({
    props,
    verdicts,
    onPilih,
    backUrl,
}: {
    props: ReviewShowProps;
    verdicts: Record<string, string>;
    onPilih: (k: 'approve' | 'reject' | 'kirim') => void;
    backUrl: string;
}) {
    if (props.pakaiVerdict) {
        const { total, dinilai, revisi } = hitungVerdict(props, verdicts);

        return (
            <div>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap items-center gap-3 text-sm">
                        <span
                            className={
                                dinilai < total
                                    ? 'text-chart-3 flex items-center gap-1.5'
                                    : 'flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400'
                            }
                        >
                            <HugeiconsIcon
                                icon={dinilai < total ? HourglassIcon : Tick02Icon}
                                strokeWidth={1.5}
                                className="size-4"
                            />
                            {dinilai} dari {total} pengendalian dinilai
                        </span>
                        {revisi > 0 ? (
                            <span className="text-destructive flex items-center gap-1.5">
                                <HugeiconsIcon icon={OctagonXIcon} strokeWidth={1.5} className="size-4" />
                                {revisi} perlu revisi
                            </span>
                        ) : null}
                    </div>
                    <Button type="button" disabled={dinilai < total} onClick={() => onPilih('kirim')}>
                        <HugeiconsIcon icon={SentIcon} strokeWidth={1.5} />
                        Kirim Hasil Tinjauan
                    </Button>
                </div>
                {dinilai < total ? (
                    <p className="text-muted-foreground mt-1 text-xs">
                        Tombol aktif setelah semua tindakan pengendalian ditandai.
                    </p>
                ) : null}
            </div>
        );
    }

    // Masukan sejawat (PLAN-AKSES-v8 Fase 2): layar yang sama, TANPA keputusan.
    // Pemberinya bukan peninjau — ia tak boleh meloloskan maupun mengembalikan
    // dokumen, jadi yang tersisa hanya Batal & Kirim.
    if (props.tanpaKeputusan) {
        return (
            <div className="flex justify-end gap-2">
                <Button asChild variant="outline">
                    <Link href={backUrl}>Batal</Link>
                </Button>
                <Button type="button" onClick={() => onPilih('kirim')}>
                    <HugeiconsIcon icon={SentIcon} strokeWidth={1.5} />
                    Kirim
                </Button>
            </div>
        );
    }

    return (
        <div className="flex flex-wrap justify-end gap-2">
            <Button type="button" variant="secondary" onClick={() => onPilih('reject')}>
                <HugeiconsIcon icon={ArrowTurnBackwardIcon} strokeWidth={1.5} />
                {props.labelTolak ?? 'Kembalikan untuk Revisi'}
            </Button>
            <Button type="button" onClick={() => onPilih('approve')}>
                <HugeiconsIcon icon={Tick02Icon} strokeWidth={1.5} />
                {props.labelLolos ?? 'Loloskan'}
            </Button>
        </div>
    );
}

/**
 * Berapa pengendalian yang ADA, yang sudah dinilai, dan yang bertanda ✗.
 *
 * Totalnya dihitung dari `contentMap` — sumber yang sama dengan yang digambar
 * `JsaTinjau` DAN yang dibangun ulang server di
 * {@see ReviewDecision::refPengendalian()}, sehingga layar tak pernah
 * memperbolehkan Kirim atas penilaian yang kelak ditolak server.
 */
function hitungVerdict(props: ReviewShowProps, verdicts: Record<string, string>) {
    const analisa = (props.schema.steps ?? [])
        .flatMap((s) => s.sections ?? [])
        .find((s) => s.type === 'jsa_analysis');

    const isi = analisa ? props.contentMap[analisa.key] : null;
    const langkah = Array.isArray(isi) ? (isi as { bahaya?: { pengendalian?: string[] }[] }[]) : [];

    const total = langkah.reduce(
        (n, l) => n + (l.bahaya ?? []).reduce((m, b) => m + (b.pengendalian ?? []).length, 0),
        0,
    );

    const nilai = Object.values(verdicts);

    return {
        total,
        dinilai: nilai.filter(Boolean).length,
        revisi: nilai.filter((v) => v === 'perlu_revisi').length,
    };
}

function judulKonfirmasi(keputusan: 'approve' | 'reject' | 'kirim' | null): string {
    if (keputusan === 'reject') return 'Kembalikan untuk Revisi?';
    if (keputusan === 'approve') return 'Loloskan Dokumen?';

    return 'Kirim Hasil Tinjauan?';
}

function pesanKonfirmasi(
    props: ReviewShowProps,
    keputusan: 'approve' | 'reject' | 'kirim' | null,
    verdicts: Record<string, string>,
): string {
    if (keputusan === 'reject') {
        return props.konfirmasiTolak ?? 'Kembalikan dokumen ke pembuat untuk revisi?';
    }

    if (keputusan === 'approve') {
        return props.konfirmasiLolos ?? 'Loloskan dokumen ke tahap persetujuan?';
    }

    if (props.tanpaKeputusan) {
        return 'Kirim masukan Anda kepada pembuat dokumen? Status dokumen tidak berubah.';
    }

    const revisi = Object.values(verdicts).filter((v) => v === 'perlu_revisi').length;

    return revisi > 0
        ? `${revisi} pengendalian ditandai Perlu Revisi. Dokumen akan dikembalikan ke pembuat.`
        : 'Semua pengendalian sesuai. Loloskan dokumen ke tahap persetujuan?';
}

/** Foto lampiran + komentar (v3.1 §6.2) — bisa dilihat DAN dikomentari. */
function KartuLampiran({ daftar }: { daftar: LampiranTinjau[] }) {
    if (daftar.length === 0) return null;

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="flex items-center gap-1.5 border-b px-4 py-3 font-semibold">
                <HugeiconsIcon icon={Album02Icon} strokeWidth={1.5} className="size-4" />
                Foto Lampiran &amp; Komentar
            </CardHeader>
            <CardContent className="grid gap-3 p-4 md:grid-cols-2">
                {daftar.map((att) => (
                    <FotoLampiran key={att.id} att={att} />
                ))}
            </CardContent>
        </Card>
    );
}

function FotoLampiran({ att }: { att: LampiranTinjau }) {
    const [komentar, setKomentar] = useState('');

    return (
        <div className="rounded-xl border p-2">
            <img src={att.url} alt={att.nama} className="mb-2 max-h-56 w-full rounded-lg object-contain" />
            <div className="mb-2 text-sm">
                {att.komentar.length === 0 ? (
                    <span className="text-muted-foreground">Belum ada komentar.</span>
                ) : (
                    att.komentar.map((c) => (
                        <div key={c.id} className="border-primary mb-1 border-l-3 pl-2">
                            <strong>{c.oleh}</strong>: {c.isi}{' '}
                            <span className="text-muted-foreground">· {c.waktu}</span>
                        </div>
                    ))
                )}
            </div>
            <form
                className="flex gap-1"
                onSubmit={(e) => {
                    e.preventDefault();
                    router.post(
                        route('attachments.comment', att.id),
                        { comment: komentar },
                        { preserveScroll: true, onSuccess: () => setKomentar('') },
                    );
                }}
            >
                <Input
                    required
                    maxLength={1000}
                    placeholder="Komentari foto ini..."
                    value={komentar}
                    onChange={(e) => setKomentar(e.target.value)}
                />
                <Button type="submit" variant="outline" size="icon" aria-label="Kirim komentar">
                    <HugeiconsIcon icon={SentIcon} strokeWidth={1.5} />
                </Button>
            </form>
        </div>
    );
}
