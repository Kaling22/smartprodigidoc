/**
 * Satu-satunya komponen panel AI Review Assist — kembaran lama
 * `resources/js/components/tinjau/PanelAi.tsx` sudah dihapus (jendela
 * pratinjau REDESAIN-UI-V2 sudah ditutup).
 */
import { Alert02Icon, Cancel01Icon, HourglassIcon, RoboticIcon, SparklesIcon, Tick02Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useEffect, useRef, useState } from 'react';

import { xsrf } from '@/lib/unggah';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent } from '@/components/ui-maia/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui-maia/select';
import { Textarea } from '@/components/ui-maia/textarea';
import type { TemuanAi } from '@/types/tinjau';

/**
 * AI Review Assist (PRD v2 §9) — pengganti komponen Alpine `aiReview`.
 *
 * AI hanya MEMBANTU; keputusan tetap di peninjau. Panel ini HILANG SELURUHNYA
 * bila `url` null — dipakai saat Admin mematikan bantuan AI untuk akun MD.
 * Sengaja dihilangkan, bukan dikelabukan: tombol mati hanya mengundang orang
 * menekannya berulang kali. Penjagaan sebenarnya tetap di server
 * (`MdReviewController::aiAnalyze`) — menyembunyikan tombol bukan pengamanan.
 *
 * Temuan yang DIADOPSI masuk ke kotak catatan tujuannya, boleh diedit dulu, dan
 * asal-usulnya tercatat lewat `annotations_ai` (CLAUDE.md §12).
 */
/** Nilai pemilih untuk "bukan baris mana pun" — Radix Select melarang nilai kosong. */
const UMUM = '__umum';

export function PanelAi({
    url,
    onAdopsi,
    onKeRingkasan,
    tujuan,
}: {
    url: string;
    /**
     * Kotak catatan yang benar-benar ada di layar, per `section_key`. Dipakai
     * mengisi pemilih tujuan tiap temuan — lihat komentar di `Review/Show.tsx`.
     */
    tujuan: Record<string, { ref: string; label: string }[]>;
    /**
     * Menaruh saran ke kotak catatan sebuah item. Mengembalikan `false` bila
     * kotak tujuannya tak ada — pemanggil lalu menjatuhkannya ke Ringkasan.
     */
    onAdopsi: (sectionKey: string, itemRef: string | number | null, saran: string) => boolean;
    onKeRingkasan: (saran: string) => void;
}) {
    const [loading, setLoading] = useState(false);
    const [analyzed, setAnalyzed] = useState(false);
    const [ringkasan, setRingkasan] = useState('');
    const [temuan, setTemuan] = useState<TemuanAi[]>([]);
    const [galat, setGalat] = useState('');
    const [detik, setDetik] = useState(0);
    const jam = useRef<ReturnType<typeof setInterval> | null>(null);
    const jemput = useRef<ReturnType<typeof setInterval> | null>(null);

    // Hentikan kedua pencacah di SATU tempat: tiga cabang selesai (sukses, AI
    // mati, galat) semuanya harus mematikannya, dan yang terlupa akan terus
    // berjalan diam-diam di latar. `useEffect` menutup cabang keempat yang
    // Alpine tak punya: halaman ditinggalkan selagi AI masih berpikir.
    useEffect(
        () => () => {
            clearInterval(jam.current ?? undefined);
            clearInterval(jemput.current ?? undefined);
        },
        [],
    );

    const selesai = () => {
        setLoading(false);
        clearInterval(jam.current ?? undefined);
        clearInterval(jemput.current ?? undefined);
    };

    // Analisis berjalan di pekerja antrean (bisa sampai beberapa menit) —
    // POST hanya MEMULAI dan langsung kembali (202); hasilnya dijemput lewat
    // GET di alamat yang sama tiap 3 detik. Menyerah sesudah 300 detik supaya
    // pekerja antrean yang mati tak membuat penghitung berjalan selamanya
    // tanpa satu pun pesan.
    const BATAS_JEMPUT_DETIK = 300;

    const terapkanHasil = (d: { enabled?: boolean; summary?: string; findings?: TemuanAi[] }) => {
        setAnalyzed(true);
        setRingkasan(d.summary || '');
        setTemuan(d.findings || []);
        if (d.enabled === false) setGalat(d.summary || '');
    };

    const analisis = async () => {
        setLoading(true);
        setGalat('');
        setDetik(0);
        jam.current = setInterval(() => setDetik((d) => d + 1), 1000);

        try {
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': xsrf(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const d = await r.json();

            if (!r.ok) {
                selesai();
                setAnalyzed(false);
                setGalat(d.message ?? d.summary ?? `Analisis AI gagal (HTTP ${r.status}).`);
                return;
            }
            if (d.status !== 'antre') {
                // AI mati / toggle akun mati: aiGuard menjawab tanpa mengantre.
                selesai();
                terapkanHasil(d);
                return;
            }

            // Penghitung TERPISAH dari `detik` (state React): closure interval
            // ini merekam nilai `detik` pada saat dibuat dan tak pernah melihat
            // pembaruannya, jadi batas menyerah dihitung lewat variabel lokal.
            let jemputan = 0;
            jemput.current = setInterval(async () => {
                jemputan += 1;
                if (jemputan * 3 >= BATAS_JEMPUT_DETIK) {
                    selesai();
                    setGalat('AI belum menjawab setelah 5 menit. Kemungkinan pekerja antrean (queue:work) tidak berjalan — lanjutkan tinjauan manual.');
                    return;
                }

                try {
                    const s = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const sd = await s.json();

                    if (!s.ok) {
                        selesai();
                        setAnalyzed(false);
                        setGalat(sd.message ?? sd.summary ?? `Analisis AI gagal (HTTP ${s.status}).`);
                        return;
                    }
                    if (sd.status === 'selesai' || sd.enabled === false) {
                        selesai();
                        terapkanHasil(sd);
                    }
                } catch {
                    selesai();
                    setGalat('Gagal memanggil AI. Lanjutkan tinjauan manual.');
                }
            }, 3000);
        } catch {
            selesai();
            setGalat('Gagal memanggil AI. Lanjutkan tinjauan manual.');
        }
    };

    const buang = (i: number) => setTemuan((lama) => lama.filter((_, j) => j !== i));

    /*
    | Tujuan tiap temuan: `item_ref` dari AI bila ia menunjuk kotak yang memang
    | ada, selain itu KOSONG = Ringkasan. Peninjau bebas menggantinya sebelum
    | menekan Adopsi, dan itulah yang membuat saran tak pernah lagi mendarat di
    | kolom yang salah: yang menentukan tujuan adalah orangnya, bukan tebakan.
    */
    const refAwal = (f: TemuanAi) => {
        const ref = f.item_ref === null || f.item_ref === undefined ? '' : String(f.item_ref);

        return (tujuan[f.section_key] ?? []).some((t) => t.ref === ref) ? ref : UMUM;
    };

    const [pilihan, setPilihan] = useState<Record<number, string>>({});

    const adopsi = (i: number) => {
        const f = temuan[i];
        const ref = pilihan[i] ?? refAwal(f);

        if (ref === UMUM || !onAdopsi(f.section_key, ref, f.suggestion)) {
            onKeRingkasan(f.suggestion);
        }

        buang(i);
    };

    return (
        <Card>
            <CardContent>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <span className="flex items-center gap-1.5 font-bold">
                        <HugeiconsIcon icon={RoboticIcon} strokeWidth={1.5} className="size-4"  />
                        AI Review Assist
                        <span className="text-muted-foreground text-sm font-normal">
                            — saran, bukan keputusan
                        </span>
                    </span>
                    <Button type="button" variant="outline" size="sm" onClick={analisis} disabled={loading}>
                        {loading ? (
                            <>
                                <HugeiconsIcon icon={HourglassIcon} strokeWidth={1.5} className="animate-pulse"  />
                                Menganalisis… {detik}d
                            </>
                        ) : (
                            <>
                                <HugeiconsIcon icon={SparklesIcon} strokeWidth={1.5} />
                                Analisis dengan AI
                            </>
                        )}
                    </Button>
                </div>

                {/* Penantiannya MEMANG lama (model gratis "reasoning": 20–40
                    detik). Detik berjalan + perkiraan waktu ditulis apa adanya
                    supaya peninjau tak menyangka aplikasinya hang lalu menekan
                    tombolnya berulang. */}
                {loading ? (
                    <p className="text-muted-foreground mt-2 flex items-center gap-1.5 text-sm">
                        <HugeiconsIcon icon={HourglassIcon} strokeWidth={1.5} className="size-3.5"  />
                        AI sedang membaca seluruh dokumen — biasanya 1–2 menit untuk JSA. Jangan tutup
                        halaman ini.
                    </p>
                ) : null}

                {ringkasan ? (
                    <p className="bg-muted mt-3 mb-2 rounded-lg border px-3 py-2 text-sm">
                        <strong>Ringkasan AI:</strong> {ringkasan}
                    </p>
                ) : null}

                {temuan.length > 0 ? (
                    <div className="mt-2">
                        <p className="text-muted-foreground mb-2 text-sm">
                            Temuan — <strong>Adopsi</strong> untuk memasukkan ke catatan (boleh diedit
                            dulu), atau <strong>Tolak</strong>:
                        </p>
                        {temuan.map((f, i) => (
                            <div key={i} className="bg-muted/40 mb-2 rounded-lg border p-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <Badge variant="outline" className="font-mono">
                                        {f.section_key}
                                        {labelRef(f.item_ref)}
                                    </Badge>
                                    <Badge variant={f.severity === 'critical' ? 'destructive' : 'secondary'}>
                                        {f.severity}
                                    </Badge>
                                </div>
                                {/* Temuan "info" = catatan/kekuatan, bukan masalah → label beda. */}
                                <p className="mt-1 text-sm">
                                    <strong>{f.severity === 'info' ? 'Catatan:' : 'Masalah:'}</strong>{' '}
                                    {f.issue}
                                </p>
                                <label className="text-muted-foreground mt-1 block text-xs">
                                    <strong>
                                        {f.severity === 'info' ? 'Keterangan' : 'Saran perbaikan'}
                                    </strong>{' '}
                                    (boleh diedit):
                                </label>
                                <Textarea
                                    rows={4}
                                    className="text-sm"
                                    value={f.suggestion}
                                    onChange={(e) =>
                                        setTemuan((lama) =>
                                            lama.map((t, j) =>
                                                j === i ? { ...t, suggestion: e.target.value } : t,
                                            ),
                                        )
                                    }
                                />
                                <label className="text-muted-foreground mt-2 block text-xs">
                                    <strong>Masukkan ke:</strong>
                                </label>
                                <Select
                                    value={pilihan[i] ?? refAwal(f)}
                                    onValueChange={(v) => setPilihan((lama) => ({ ...lama, [i]: v }))}
                                >
                                    <SelectTrigger className="w-full" aria-label="Kotak tujuan temuan">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {/* Nilai kosong tak bisa dipakai Radix Select, jadi Ringkasan
                                            punya nilainya sendiri dan diterjemahkan di `adopsi()`. */}
                                        <SelectItem value="__umum">Ringkasan / Catatan Umum</SelectItem>
                                        {(tujuan[f.section_key] ?? []).map((t) => (
                                            <SelectItem key={t.ref} value={t.ref}>
                                                {t.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <div className="mt-1 flex justify-end gap-1">
                                    <Button type="button" size="sm" onClick={() => adopsi(i)}>
                                        <HugeiconsIcon icon={Tick02Icon} strokeWidth={1.5} />
                                        Adopsi
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => buang(i)}
                                    >
                                        <HugeiconsIcon icon={Cancel01Icon} strokeWidth={1.5} />
                                        Tolak
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : null}

                {analyzed && temuan.length === 0 && !galat ? (
                    <p className="mt-2 flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-400">
                        <HugeiconsIcon icon={Tick02Icon} strokeWidth={1.5} className="size-4"  />
                        Tidak ada temuan signifikan dari AI.
                    </p>
                ) : null}

                {galat ? (
                    <p className="text-destructive mt-2 flex items-center gap-1.5 text-sm">
                        <HugeiconsIcon icon={Alert02Icon} strokeWidth={1.5} className="size-4"  />
                        {galat}
                    </p>
                ) : null}
            </CardContent>
        </Card>
    );
}

/**
 * "L0-B1-P2" → " › Langkah 1 › Bahaya 2 › Kendali 3".
 *
 * Penandanya berguna bagi mesin, tak terbaca oleh peninjau; nomornya digeser ke
 * basis 1 supaya cocok dengan yang tercetak di formulir.
 */
function labelRef(ref: string | number | null): string {
    if (ref === null || ref === undefined || ref === '') return '';

    const m = String(ref).match(/^L(\d+)(?:-B(\d+))?(?:-P(\d+))?$/);
    if (!m) return ` › item ${Number(ref) + 1}`;

    const nama = ['Langkah', 'Bahaya', 'Kendali'];

    return m
        .slice(1)
        .reduce((s, v, i) => (v === undefined ? s : `${s} › ${nama[i]} ${Number(v) + 1}`), '');
}

