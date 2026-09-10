import {
    ArrowLeft01Icon,
    ArrowRight01Icon,
    CloudSavingDone01Icon,
    File01Icon,
    FloppyDiskIcon,
    LockIcon,
    Message01Icon,
    RefreshCwIcon,
    SentIcon,
    ViewIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';

import { xsrf } from '@/lib/unggah';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui-maia/alert-dialog';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import {
    barisLogBaru,
    barisLogBerisi,
    PenyediaWizard,
    RevisionLog,
    Seksi,
    type BarisLog,
    type KonteksWizard,
} from '@/components/v2/dokumen/fields';
import { StatusBadge } from '@/components/v2/StatusBadge';
import { AppLayout } from '@/layouts/V2/AppLayout';
import { antarKe, validasiWajib, warnKosong, type Kosong } from '@/lib/validasi';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import type {
    CatatanBaris,
    CatatanYatim,
    DocumentsEditProps,
    LangkahSchema,
    NilaiSeksi,
    SeksiSchema,
} from '@/types/wizard';

const JUDUL_LOG = 'Log Revisi (Catatan Perubahan)';

/**
 * Wizard pengisian dokumen, kerangka V2 — kembaran maia dari
 * `pages/Documents/Edit.tsx`.
 *
 * SELURUH perilakunya disalin persis: autosave 1200 ms, `key` per langkah,
 * panel pratinjau di luar pembungkus ber-`key` itu, validasi dua langkah untuk
 * kolom yang boleh kosong, dan langkah virtual "Log Revisi". Halaman ini tetap
 * TIDAK tahu satu pun nama bab — yang digambarnya datang dari
 * `schema.steps[].sections[]` lewat `<Seksi>` (pakem P1).
 *
 * SATU hal yang berubah, dan itu keputusan pemilik 2026-08-31: **kartu kop
 * dibubarkan.** Versi nova membuka dengan dua kartu penuh — satu berisi
 * nomor/judul/badge/pembuat, satu lagi berisi rentetan langkah — sebelum
 * formulirnya kelihatan. Di layar 768 px tinggi, isian pertama sudah di bawah
 * lipatan sebelum sebaris pun diketik.
 *
 * Sekarang keduanya larut:
 *
 *   - judul dokumen jadi H1 layout, metadatanya jadi sub-line,
 *   - "Lihat PDF" jadi `aksi` — sebaris dengan H1, tempat mata mencarinya,
 *   - rentetan langkah jadi RAIL ramping setinggi satu baris.
 *
 * Nol informasi hilang: nomor, lencana "sementara", jenis, departemen, status,
 * pembuat, dan No. Revisi semuanya masih tercetak, hanya tak lagi memakan satu
 * kartu penuh masing-masing.
 *
 * Tombol **Preview** sengaja TIDAK ikut naik ke `aksi`. Ia bukan aksi halaman
 * melainkan aksi FORMULIR — ia menyimpan isian dulu, baru menyegarkan iframe —
 * dan keadaannya hidup di `<FormLangkah>`. Menaikkannya berarti menyalin
 * `simpanDiam()` ke dua tempat yang harus sepakat selamanya; ia tetap duduk
 * bersama Simpan & Kirim di kaki formulir.
 */
export default function DocumentsEdit() {
    const props = usePage<PageProps & DocumentsEditProps>().props;
    const { document: doc, schema, currentStep, totalSteps, isRevLogStep, editable, previewV, rujukanPdfUrl } = props;

    const langkahSchema = schema.steps ?? [];
    const pakaiLogRevisi = totalSteps > langkahSchema.length;

    // Sidik isi cetakan. Isi TETAP → URL tetap → peramban menyajikan dari
    // cache-nya sendiri (nol permintaan, nol kedip). Isi BERUBAH → sidik
    // berubah → dimuat segar.
    const [versi, setVersi] = useState(previewV);
    useEffect(() => setVersi(previewV), [previewV]);

    const judulLangkah = isRevLogStep
        ? JUDUL_LOG
        : (langkahSchema.find((s) => s.step === currentStep)?.title ?? `Langkah ${currentStep}`);

    return (
        <AppLayout
            judul={doc.judul}
            remah={[{ label: 'Dokumen', href: route('documents.index') }, { label: 'Pengisian' }]}
            sub={
                // `sub` dirender di dalam <p>; semua yang di bawah ini <span>,
                // jadi susunannya tetap sah. Lencana & status memakai komponen
                // yang sama dengan daftar dokumen — nol hex diketik di sini,
                // warnanya tetap dari props `statusMeta` (CLAUDE.md §4).
                <span className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span className="text-primary font-mono font-semibold">{doc.nomor}</span>
                    {doc.nomorFinal ? null : (
                        <Badge variant="outline" title="Nomor final dikunci setelah disetujui">
                            sementara
                        </Badge>
                    )}
                    <Badge variant="secondary">{doc.jenis}</Badge>
                    <Badge variant="secondary">{doc.departemen}</Badge>
                    <StatusBadge status={doc.status} />
                    <span aria-hidden="true">·</span>
                    <span>Pembuat: {doc.pembuat}</span>
                    <span aria-hidden="true">·</span>
                    <span>No. Revisi: {doc.noRevisi}</span>
                </span>
            }
            aksi={
                <Button asChild variant="outline" size="sm">
                    <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                        <HugeiconsIcon icon={File01Icon} strokeWidth={1.5} />
                        Lihat PDF
                    </a>
                </Button>
            }
        >
            <RailLangkah
                langkah={[
                    ...langkahSchema.map((s) => ({ n: s.step, judul: s.title ?? '' })),
                    ...(pakaiLogRevisi ? [{ n: langkahSchema.length + 1, judul: JUDUL_LOG }] : []),
                ]}
                aktif={currentStep}
            />

            {editable ? null : (
                <p className="border-chart-3/40 bg-chart-3/10 flex items-center gap-2 rounded-xl border px-3 py-2 text-sm">
                    <HugeiconsIcon icon={LockIcon} strokeWidth={1.5} className="size-4 shrink-0" />
                    <span>
                        {/* Label dari props global, BUKAN kunci mentah (pakem P3). */}
                        Dokumen berstatus <strong>{props.statusLabels[doc.status] ?? doc.status}</strong> — mode baca.
                    </span>
                </p>
            )}

            <UmpanBalikPeninjau
                rangkuman={props.reviewSummary}
                catatanItem={props.catatanItem}
                yatim={props.catatanYatim}
                langkah={langkahSchema}
                aktif={currentStep}
            />

            <div className="grid gap-4 lg:grid-cols-12">
                <div className="lg:col-span-7">
                    <FormLangkah key={`${doc.id}:${currentStep}`} judulLangkah={judulLangkah} versi={versi} onVersi={setVersi} />
                </div>

                <div className="lg:col-span-5">
                    <PanelPratinjau documentId={doc.id} versi={versi} rujukanPdfUrl={rujukanPdfUrl} />
                </div>
            </div>
        </AppLayout>
    );
}

/**
 * Rentetan langkah sebagai RAIL setinggi satu baris.
 *
 * Registry shadcn tak punya blok stepper, jadi ini penyimpangan yang disengaja
 * dan alasannya ditulis di sini (CLAUDE.md §13). Yang dipinjam tetap kosakata
 * maia: pil `rounded-full`, wadah `rounded-2xl`, dan `ring-1 ring-foreground/10`
 * yang sama dengan `Card` — jadi ia terbaca sebagai keluarga yang sama tanpa
 * memakan tinggi sebuah kartu.
 *
 * Angka langkah memakai `tabular-nums`: pada dokumen berlangkah tiga, lebar
 * digitnya tak boleh menggeser label di sebelahnya saat langkahnya berganti.
 */
function RailLangkah({ langkah, aktif }: { langkah: { n: number; judul: string }[]; aktif: number }) {
    return (
        <ol className="ring-foreground/10 bg-card flex flex-wrap items-center gap-x-2 gap-y-1 rounded-2xl px-4 py-2 ring-1">
            {langkah.map(({ n, judul }, i) => (
                <li key={n} className="flex items-center gap-2">
                    <span
                        className={cn(
                            'inline-flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                            n === aktif
                                ? 'bg-primary text-primary-foreground'
                                : n < aktif
                                  ? 'bg-chart-2 text-primary-foreground'
                                  : 'bg-muted text-muted-foreground',
                        )}
                        aria-current={n === aktif ? 'step' : undefined}
                    >
                        {n}
                    </span>
                    <span className={cn('text-sm', n === aktif ? 'text-foreground font-semibold' : 'text-muted-foreground')}>
                        {judul}
                    </span>
                    {i < langkah.length - 1 ? (
                        <HugeiconsIcon
                            icon={ArrowRight01Icon}
                            strokeWidth={1.5}
                            className="text-muted-foreground mx-1 size-3.5"
                            aria-hidden="true"
                        />
                    ) : null}
                </li>
            ))}
        </ol>
    );
}

/**
 * Rangkuman + anotasi peninjau — dua lapis, keduanya wajib ada (CLAUDE.md §13).
 *
 * Rangkuman memakai `whitespace-pre-line`: alasan Ajukan Revisi bisa berisi
 * beberapa baris (masukan lapangan yang diadopsi, satu per baris).
 */
function UmpanBalikPeninjau({
    rangkuman,
    catatanItem,
    yatim,
    langkah,
    aktif,
}: {
    rangkuman: string | null;
    catatanItem: Record<string, Record<string, CatatanBaris[]>>;
    yatim: CatatanYatim[];
    langkah: LangkahSchema[];
    aktif: number;
}) {
    const jumlahSeksi = (key: string) =>
        Object.values(catatanItem[key] ?? {}).reduce((t, daftar) => t + daftar.length, 0);

    /*
    | Berapa catatan di TIAP langkah — bukan daftarnya.
    |
    | Isinya sendiri digambar `CatatanItem` di bawah isian masing-masing, tapi
    | wizard hanya menggambar SATU langkah pada satu waktu: tanpa hitungan ini,
    | catatan di langkah lain tak punya satu pun jejak di layar dan pembuat
    | mengira ia sudah selesai.
    */
    const perLangkah = langkah
        .map((s) => ({
            n: s.step,
            jumlah: (s.sections ?? []).reduce((t, sec) => t + jumlahSeksi(sec.key), 0),
        }))
        .filter((s) => s.jumlah > 0);

    if (!rangkuman && perLangkah.length === 0 && yatim.length === 0) return null;

    return (
        <div className="border-destructive/40 bg-destructive/10 rounded-xl border px-3 py-2 text-sm">
            {rangkuman ? (
                <p className="border-destructive/30 mb-2 border-b pb-2 whitespace-pre-line">
                    <strong>Rangkuman Peninjau:</strong> {rangkuman}
                </p>
            ) : null}

            {perLangkah.length > 0 ? (
                <p className="flex flex-wrap items-center gap-1.5">
                    <HugeiconsIcon icon={Message01Icon} strokeWidth={1.5} className="size-4 shrink-0" />
                    <span className="font-semibold">Catatan per item:</span>
                    {perLangkah.map((s) => (
                        <Badge key={s.n} variant={s.n === aktif ? 'destructive' : 'outline'}>
                            {s.jumlah} di Langkah {s.n}
                        </Badge>
                    ))}
                    <span className="text-muted-foreground">— tampil di bawah isian masing-masing.</span>
                </p>
            ) : null}

            {yatim.length > 0 ? (
                <>
                    <p className="mt-2 font-semibold">Catatan tanpa item — periksa manual:</p>
                    <ul className="mt-1 space-y-1">
                        {yatim.map((c, i) => (
                            // eslint-disable-next-line react/no-array-index-key
                            <li key={i}>
                                <Badge variant="outline" className="mr-1.5">
                                    {c.bagian} · {c.item}
                                </Badge>
                                {c.komentar}
                                <span className="text-muted-foreground"> — {c.oleh}</span>
                            </li>
                        ))}
                    </ul>
                </>
            ) : null}
        </div>
    );
}

/**
 * Isian SATU langkah, beserta autosave, validasi, dan tombol navigasinya.
 *
 * Di-`key` oleh pemanggilnya per langkah, jadi seluruh state di sini lahir
 * segar tiap kali langkahnya berganti — tak ada satu pun nilai langkah lama
 * yang bisa bocor ke langkah berikutnya.
 */
function FormLangkah({
    judulLangkah,
    versi,
    onVersi,
}: {
    judulLangkah: string;
    versi: string;
    onVersi: (v: string) => void;
}) {
    const props = usePage<PageProps & DocumentsEditProps>().props;
    const {
        document: doc,
        schema,
        contentMap,
        userValues,
        currentStep,
        totalSteps,
        isRevLogStep,
        editable,
        babAktif,
        judulLangkahMati,
        revisiKirim,
        tanggalCetak,
    } = props;

    const seksiLangkah: SeksiSchema[] = schema.steps?.find((s) => s.step === currentStep)?.sections ?? [];
    const terakhir = currentStep >= totalSteps;

    const [babOn, setBabOn] = useState(babAktif);
    const [nilai, setNilai] = useState<Record<string, NilaiSeksi>>(() =>
        Object.fromEntries(
            seksiLangkah.map((sec) => [
                sec.key,
                sec.type === 'user_picker' ? (userValues[sec.key] ?? null) : (contentMap[sec.key] ?? null),
            ]),
        ),
    );

    // Langkah Log Revisi — di luar schema, jadi keadaannya berdiri sendiri.
    const [logRows, setLogRows] = useState<BarisLog[]>(() => {
        const tersimpan = contentMap.catatan_revisi;

        return Array.isArray(tersimpan) && tersimpan.length ? (tersimpan as BarisLog[]) : [barisLogBaru()];
    });
    const [edisi, setEdisi] = useState(doc.edisi);
    const [noRevisi, setNoRevisi] = useState(doc.noRevisi);
    // null = draft ini bukan salinan arsip; isiannya tak digambar & tak dikirim.
    const [tanggal, setTanggal] = useState(tanggalCetak);
    const [pesanRevisi, setPesanRevisi] = useState('');
    const [menyusunRevisi, setMenyusunRevisi] = useState(false);

    /**
     * Draft ini salinan dokumen lama ("Salin ke wizard"), bukan revisi Tipe B.
     *
     * `tanggalCetak` non-null PERSIS saat `documents.salin_arsip_at` terisi
     * (`DocumentController:673`) — jadi ia sudah menjadi isyarat "ini salinan
     * arsip" yang datang dari server, dan tak perlu prop kedua yang mengatakan
     * hal sama. Kalau syarat di server itu berubah, tombol Revisi di bawah ikut
     * berubah — sengaja: keduanya memang bicara soal draft yang sama.
     */
    const salinanArsip = tanggalCetak !== null;

    const [savedAt, setSavedAt] = useState('');
    const [previewing, setPreviewing] = useState(false);
    const [mengirim, setMengirim] = useState(false);
    const [dialog, setDialog] = useState<'kosong' | 'kosongLanjut' | 'kirim' | null>(null);
    const [kosong, setKosong] = useState<Kosong[]>([]);

    const formRef = useRef<HTMLFormElement>(null);

    /** Kiriman satu langkah — bentuknya sama persis untuk autosave & simpan. */
    const muatan = () => {
        const sections: Record<string, unknown> = { ...nilai };

        // Sakelar bab opsional ikut terkirim: tanpa ini, mematikan "Gunakan
        // Flowchart" baru tersimpan saat pindah langkah, dan pratinjau yang
        // dimuat sebelum itu masih memuat babnya.
        seksiLangkah.forEach((sec) => {
            if (sec.toggle_key) sections[sec.toggle_key] = babOn ? '1' : '0';
        });

        if (isRevLogStep) sections.catatan_revisi = logRows;

        return {
            step: currentStep,
            sections,
            ...(isRevLogStep ? { edisi, no_revisi: noRevisi } : {}),
            ...(isRevLogStep && tanggal
                ? { tanggal_terbit: tanggal.terbit, tanggal_revisi: tanggal.revisi }
                : {}),
        };
    };

    const muatanRef = useRef(muatan);
    muatanRef.current = muatan;

    const simpanDiam = async (): Promise<{ saved_at?: string; v?: string } | null> => {
        try {
            const r = await fetch(route('documents.autosave', doc.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrf(),
                },
                body: JSON.stringify(muatanRef.current()),
            });

            if (!r.ok) {
                toast.error('Gagal menyimpan otomatis.');
                return null;
            }

            return await r.json();
        } catch {
            return null;
        }
    };

    // Autosave 1200ms sesudah ketikan berhenti — sama persis dengan
    // `@input.debounce.1200ms` di Blade. Render pertama dilewati: memuat
    // halaman bukan menyunting, dan menyimpan saat itu cuma menulis ulang isi
    // yang sama sambil membuat "Tersimpan otomatis" menyala tanpa sebab.
    const pertama = useRef(true);
    useEffect(() => {
        if (!editable) return;
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        const t = setTimeout(() => {
            if (mengirim) return;
            void simpanDiam().then((j) => {
                if (j?.saved_at) setSavedAt(j.saved_at);
            });
        }, 1200);

        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [nilai, babOn, logRows, edisi, noRevisi]);

    /**
     * Simpan teks dulu, lalu segarkan HANYA iframe pratinjau — form tak reload.
     *
     * Preview adalah perintah eksplisit ("muat ulang sekarang"), bukan "muat
     * ulang kalau ada yang berubah" — versinya dipaksa unik per klik supaya
     * langkah 2 (autosave sengaja `continue` untuk seksi `user_picker`, dan
     * `updateOrCreate` tak menaikkan `updated_at` bila datanya identik) tetap
     * memuat ulang iframe walau sidik dokumen tak berubah.
     */
    const pratinjau = async () => {
        setPreviewing(true);
        const j = await simpanDiam();
        onVersi(`${j?.v ?? versi}-${Date.now()}`);
        setPreviewing(false);
    };

    const kirimLangkah = (action: 'back' | 'next' | 'save' | 'submit') => {
        setMengirim(true);
        // `sections` BERSARANG (baris → kolom), dan tipe `RequestPayload`
        // Inertia hanya menjanjikan nilai datar. Runtime-nya sanggup: tanpa
        // berkas ia mengirim JSON apa adanya, dengan berkas ia menyusun
        // FormData berkurung — dua-duanya dibaca Laravel sebagai larik
        // bersarang. Yang kurang cuma tipenya.
        router.post(route('documents.saveStep', doc.id), { ...muatan(), action } as never, {
            onFinish: () => setMengirim(false),
        });
    };

    /**
     * Tombol "Revisi" — server membandingkan isi draft dengan versi yang
     * direvisinya, lalu mengukur halaman tiap bab yang berubah dari PDF yang
     * benar-benar dirender.
     *
     * Baris yang CATATANNYA masih kosong dibuang lebih dulu: tombolnya jadi
     * boleh ditekan berkali-kali tanpa menumpuk duplikat, sekaligus menjaga
     * baris revisi terdahulu (catatannya sudah terisi) tetap utuh.
     */
    const isiLogOtomatis = async () => {
        setPesanRevisi('');
        setMenyusunRevisi(true);

        try {
            const r = await fetch(route('documents.revisi.usulan', doc.id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            });
            if (!r.ok) throw new Error();

            const j = await r.json();

            if (!j.baris.length) {
                setPesanRevisi('Belum ada bab yang berbeda dari versi yang berlaku — isi dokumen dulu, lalu tekan Revisi lagi.');

                return;
            }

            const tetap = logRows.filter(barisLogBerisi);
            // SATU baris per klik, bukan satu baris per temuan (keputusan
            // pemilik 2026-09-07): kolom No. Rev menandai SESI revisinya, dan
            // tiap bab yang berubah jadi sub-poin a./b./c. di dalamnya.
            //
            // Nomornya naik seperti angka biasa, TANPA batas atas — ambang
            // 1..5 yang dulu di sini meniru DocumentService::MAKS_REVISI,
            // padahal keduanya menghitung hal berbeda: yang itu versi dokumen,
            // yang ini urutan baris pada lembar catatan. Kelanjutannya dihitung
            // dari baris TERAKHIR yang sudah terisi, supaya klik berulang
            // menyambung, bukan mengulang.
            const urut = tetap.length ? Number(tetap[tetap.length - 1]?.no_rev) || 0 : (Number(j.no_rev) || 1) - 1;
            const babBerubah: { halaman: string; bab: string }[] = j.baris;
            // Kolom HAL. baris induk = gabungan halaman seluruh sub-poinnya.
            const halaman = [...new Set(babBerubah.map((b) => b.halaman).filter(Boolean))].join(', ');

            setLogRows([
                ...tetap,
                {
                    no_rev: urut + 1,
                    tanggal: j.tanggal,
                    halaman,
                    catatan: '',
                    sub: babBerubah.map((b) => ({ catatan: '', bab: b.bab })),
                },
            ]);
        } catch {
            setPesanRevisi('Gagal menyusun catatan revisi. Coba lagi.');
        } finally {
            // `finally`, bukan satu baris di tiap ujung: cabang "belum ada bab
            // yang berbeda" keluar lewat `return` dini, dan garisnya akan
            // berjalan selamanya kalau dimatikan per cabang.
            setMenyusunRevisi(false);
        }
    };

    /** Validasi hanya saat MAJU atau KIRIM — Simpan & Kembali boleh parsial. */
    const lanjut = (action: 'next' | 'submit') => {
        const root = formRef.current;
        if (!root) return;

        const belum = validasiWajib(root);
        if (belum) {
            toast.error('Ada kolom yang belum diisi', {
                description: 'Lengkapi semua kolom yang ditandai merah sebelum melanjutkan.',
            });
            antarKe(belum);

            return;
        }

        if (action === 'next') {
            kirimLangkah('next');

            return;
        }

        const sisa = warnKosong(root);
        if (sisa.length) {
            setKosong(sisa);
            setDialog('kosong');

            return;
        }

        setDialog('kirim');
    };

    const konteks: KonteksWizard = {
        documentId: doc.id,
        editable,
        dokumenBerlaku: props.dokumenBerlaku,
        labelMati: props.labelMati,
        babOn,
        setBabOn,
        candidates: props.candidates,
        ketersediaan: props.ketersediaan,
        papan: props.papan,
        ambang: props.ambang,
        catatanItem: props.catatanItem,
    };

    // Judul ikut menyebut bab yang benar-benar dipakai: "Flowchart, Aktivitas,
    // …" → "Aktivitas, …". Kedua varian dihitung PHP; di sini cuma dipilih.
    const judul = isRevLogStep ? judulLangkah : babOn ? judulLangkah : (judulLangkahMati ?? judulLangkah);

    return (
        <PenyediaWizard value={konteks}>
            <form ref={formRef} onSubmit={(e) => e.preventDefault()}>
                <Card className="relative gap-0 py-0">
                    {/* Garis merah berjalan di puncak kartu selagi tombol Revisi
                        menyusun catatan. Indeterminate — ia tak mengukur
                        kemajuan, cuma membuktikan permintaannya sedang jalan
                        (tanpanya tombol tampak mati sampai barisnya muncul). */}
                    {menyusunRevisi ? (
                        <span aria-hidden className="absolute inset-x-0 top-0 h-0.5 overflow-hidden rounded-t-4xl">
                            <span className="garis-lari bg-primary block h-full w-1/4" />
                        </span>
                    ) : null}

                    <CardHeader className="flex items-center justify-between border-b px-4 py-3">
                        <span className="font-semibold">
                            Langkah {currentStep} — {judul}
                        </span>
                        {savedAt ? (
                            <span className="text-muted-foreground flex items-center gap-1.5 text-sm">
                                <HugeiconsIcon icon={CloudSavingDone01Icon} strokeWidth={1.5} className="size-4" />
                                Tersimpan otomatis {savedAt}
                            </span>
                        ) : null}
                    </CardHeader>

                    <CardContent className="p-4">
                        {isRevLogStep ? (
                            <RevisionLog
                                rows={logRows}
                                onRows={setLogRows}
                                edisi={edisi}
                                noRevisi={noRevisi}
                                onEdisi={setEdisi}
                                onNoRevisi={setNoRevisi}
                                judul={doc.judul}
                                revisiKirim={revisiKirim}
                                pesan={pesanRevisi}
                                tanggal={tanggal}
                                onTanggal={(patch) => setTanggal((lama) => (lama ? { ...lama, ...patch } : lama))}
                                adaTombolRevisi={!salinanArsip}
                            />
                        ) : (
                            seksiLangkah.map((sec) => (
                                <Seksi
                                    key={sec.key}
                                    section={sec}
                                    value={nilai[sec.key]}
                                    onChange={(v) => setNilai((lama) => ({ ...lama, [sec.key]: v }))}
                                />
                            ))
                        )}
                    </CardContent>

                    <CardFooter className="flex justify-between border-t px-4 py-3">
                        <div>
                            {currentStep > 1 ? (
                                editable ? (
                                    <Button type="button" variant="outline" onClick={() => kirimLangkah('back')}>
                                        <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={1.5} />
                                        Kembali
                                    </Button>
                                ) : (
                                    <Button asChild variant="outline">
                                        {/* Pembaca berpindah langkah lewat `?view_step`
                                            (nol tulisan ke basis data) — inilah yang dulu
                                            menutup bug "Kembali berakhir 403". */}
                                        <Link href={route('documents.edit', { document: doc.id, view_step: currentStep - 1 })}>
                                            <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={1.5} />
                                            Kembali
                                        </Link>
                                    </Button>
                                )
                            ) : null}
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" disabled={previewing} onClick={pratinjau}>
                                <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} />
                                Preview
                            </Button>

                            {/* Tombol "Revisi" — HANYA di langkah Log Revisi. Duduk di
                                sebelah Preview karena keduanya berbicara tentang
                                CETAKAN, bukan tentang isian.

                                TIDAK digambar pada salinan dokumen lama: yang
                                dibandingkannya adalah isi draft lawan versi yang
                                direvisinya, sedangkan "versi lama" sebuah salinan
                                arsip cuma berkas PDF unggahan tanpa seksi sama
                                sekali — hasilnya seluruh bab dilaporkan berubah,
                                yaitu daftar sampah. Langkah 3 di sana memang cuma
                                untuk mengetik lembar catatan revisinya sendiri. */}
                            {isRevLogStep && editable && !salinanArsip ? (
                                <Button type="button" variant="outline" disabled={menyusunRevisi} onClick={isiLogOtomatis}>
                                    <HugeiconsIcon icon={RefreshCwIcon} strokeWidth={1.5} />
                                    Revisi
                                </Button>
                            ) : null}

                            {editable ? (
                                terakhir ? (
                                    <>
                                        <Button type="button" variant="outline" onClick={() => kirimLangkah('save')}>
                                            <HugeiconsIcon icon={FloppyDiskIcon} strokeWidth={1.5} />
                                            Simpan
                                        </Button>
                                        <Button type="button" onClick={() => lanjut('submit')}>
                                            <HugeiconsIcon icon={SentIcon} strokeWidth={1.5} />
                                            Kirim
                                        </Button>
                                    </>
                                ) : (
                                    <Button type="button" onClick={() => lanjut('next')}>
                                        Langkah Berikutnya
                                        <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} />
                                    </Button>
                                )
                            ) : terakhir ? null : (
                                <Button asChild>
                                    <Link href={route('documents.edit', { document: doc.id, view_step: currentStep + 1 })}>
                                        Langkah Berikutnya
                                        <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} />
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </CardFooter>
                </Card>
            </form>

            {/*
              Peringatan DUA LANGKAH untuk kolom yang boleh kosong.

              Langkah pertama menyebut apa saja yang kosong dan menawarkan "Isi
              dulu". Yang memilih "Tetap kirim" ditanya SEKALI LAGI — dokumen
              yang sudah dikirim tak bisa disunting lagi, jadi tombol yang
              meloloskan kekosongan tak boleh sedekat satu klik dari niat
              asal-asalan.

              Dirakit dari primitif `AlertDialog`, bukan `ConfirmDialog`: yang
              ini butuh DUA jalan keluar yang sama-sama berbuat sesuatu, dan
              `ConfirmDialog` hanya menyediakan satu.
            */}
            <AlertDialog open={dialog === 'kosong'} onOpenChange={(b) => setDialog(b ? 'kosong' : null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Ada isian yang masih kosong</AlertDialogTitle>
                        <AlertDialogDescription asChild>
                            <div>
                                Kolom berikut belum diisi:
                                <ul className="mt-2 list-inside list-disc">
                                    {[...new Set(kosong.map((k) => k.label))].map((l) => (
                                        <li key={l}>{l}</li>
                                    ))}
                                </ul>
                                <p className="mt-2 text-sm">
                                    Kolom ini tidak wajib — dokumen tetap bisa dikirim tanpanya.
                                </p>
                            </div>
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setDialog('kosongLanjut')}>Tetap kirim</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => {
                                setDialog(null);
                                if (kosong[0]) antarKe(kosong[0].el);
                            }}
                        >
                            Isi dulu
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <ConfirmDialog
                buka={dialog === 'kosongLanjut'}
                onUbahBuka={(b) => setDialog(b ? 'kosongLanjut' : null)}
                judul="Kirim tanpa mengisinya?"
                pesan="Dokumen tidak bisa diedit setelah dikirim (masih bisa Ditarik selama belum ditinjau)."
                tombolYa="Ya, kirim apa adanya"
                onKonfirmasi={() => kirimLangkah('submit')}
            />

            <ConfirmDialog
                buka={dialog === 'kirim'}
                onUbahBuka={(b) => setDialog(b ? 'kirim' : null)}
                judul="Kirim Dokumen?"
                pesan="Kirim dokumen untuk ditinjau? Dokumen tidak bisa diedit setelah dikirim (masih bisa Ditarik selama belum ditinjau)."
                tombolYa="Ya, kirim"
                onKonfirmasi={() => kirimLangkah('submit')}
            />
        </PenyediaWizard>
    );
}

/**
 * Panel pratinjau kanan.
 *
 * `view=Fit` menyuruh penampil PDF memuat SATU HALAMAN PENUH ke dalam bingkai:
 * halamannya mengecil sampai muat seluruhnya dan menyisakan ruang kosong, bukan
 * terpotong. `toolbar=0&navpanes=0` — nomor halaman sudah tercetak di kop.
 *
 * Keterangan "Menyiapkan pratinjau…" DITINDIH iframe (bukan disembunyikan lewat
 * kelas): iframe yang belum terisi itu tembus pandang, jadi keterangannya
 * terlihat selama memuat lalu tertutup sendiri saat penampil PDF mengecat — nol
 * pergantian kelas, nol kedip.
 *
 * Tombol "PDF" yang di versi nova duduk di kepala panel ini sudah naik ke
 * `aksi` layout — kepala panel kini hanya memegang pergantian tab, dan tak ada
 * satu pun tombol yang tergambar dua kali.
 */
function PanelPratinjau({
    documentId,
    versi,
    rujukanPdfUrl,
}: {
    documentId: number;
    versi: string;
    rujukanPdfUrl: string | null;
}) {
    const [tab, setTab] = useState<'pratinjau' | 'referensi'>('pratinjau');
    const [rujukanDimuat, setRujukanDimuat] = useState(false);

    const src = `${route('documents.pdf', documentId)}?v=${versi}#toolbar=0&navpanes=0&view=Fit`;

    return (
        <Card className="sticky top-4 gap-0 py-0">
            <CardHeader className="flex items-center justify-between border-b px-4 py-2">
                {rujukanPdfUrl ? (
                    // Dua tab, bukan dua panel bersanding: layar 1366px tak cukup
                    // untuk formulir + dua A4, dan yang dibutuhkan memang
                    // bergantian — baca rujukan, lalu ketik.
                    <div className="flex gap-1">
                        <TabPratinjau aktif={tab === 'pratinjau'} onKlik={() => setTab('pratinjau')}>
                            <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-3.5" />
                            Pratinjau
                        </TabPratinjau>
                        <TabPratinjau
                            aktif={tab === 'referensi'}
                            onKlik={() => {
                                setTab('referensi');
                                setRujukanDimuat(true);
                            }}
                        >
                            <HugeiconsIcon icon={File01Icon} strokeWidth={1.5} className="size-3.5" />
                            Referensi
                        </TabPratinjau>
                    </div>
                ) : (
                    <span className="flex items-center gap-1.5 text-sm font-semibold">
                        <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-4" />
                        Pratinjau PDF
                    </span>
                )}
            </CardHeader>

            <CardContent className="relative h-[78vh] bg-[#525659] p-0">
                <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 text-sm text-white/60">
                    <HugeiconsIcon icon={File01Icon} strokeWidth={1.5} className="size-8" />
                    Menyiapkan pratinjau…
                </div>

                <iframe
                    id="previewFrame"
                    title="Pratinjau PDF"
                    src={src}
                    className={cn('relative size-full border-0', tab === 'pratinjau' ? 'block' : 'hidden')}
                />

                {/* `src` baru dipasang saat tabnya dibuka: dokumen lama bisa
                    puluhan MB, dan memuatnya untuk tab yang mungkin tak pernah
                    dilihat membuat setiap langkah wizard terasa berat. Sesudah
                    dibuka sekali ia tetap termuat. */}
                {rujukanPdfUrl ? (
                    <iframe
                        title="Berkas dokumen lama"
                        src={rujukanDimuat ? `${rujukanPdfUrl}#toolbar=1&navpanes=0&view=Fit` : undefined}
                        className={cn('relative size-full border-0', tab === 'referensi' ? 'block' : 'hidden')}
                    />
                ) : null}
            </CardContent>
        </Card>
    );
}

/** Tab pratinjau/referensi — pil maia, bukan tab Radix: dua tombol tanpa panel. */
function TabPratinjau({ aktif, onKlik, children }: { aktif: boolean; onKlik: () => void; children: ReactNode }) {
    return (
        <button
            type="button"
            onClick={onKlik}
            className={cn(
                'focus-visible:ring-ring/50 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm transition-colors focus-visible:ring-[3px] focus-visible:outline-none',
                aktif ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent',
            )}
        >
            {children}
        </button>
    );
}
