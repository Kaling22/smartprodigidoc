/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/RevisionLog.tsx` (REDESAIN-UI-V2 T2).
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
import { Delete02Icon, PlusSignIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Button } from '@/components/ui-maia/button';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { Textarea } from '@/components/ui-maia/textarea';

/** Satu temuan di dalam sebuah sesi revisi — tercetak sebagai a./b./c. */
export interface SubBarisLog {
    catatan?: string;
    /** Nama bab yang berubah; hanya petunjuk layar, tak tersimpan. */
    bab?: string;
}

/**
 * Satu baris lembar CATATAN REVISI = satu SESI revisi.
 *
 * No. Rev naik SEKALI per klik tombol Revisi, dan tiap temuan yang terdeteksi
 * jadi sub-poin di `sub` (tercetak a./b./c. di dalam sel CATATAN). Kolom HAL.
 * memuat gabungan halaman seluruh sub-poinnya. `bab` hanya petunjuk layar, tak
 * tersimpan. Baris tanpa `sub` tetap sah — itulah bentuk lama, dan ia tercetak
 * persis seperti dulu.
 */
export interface BarisLog {
    no_rev?: number | string | null;
    tanggal?: string;
    halaman?: string;
    catatan?: string;
    bab?: string;
    sub?: SubBarisLog[];
}

export const barisLogBaru = (): BarisLog => ({ no_rev: null, tanggal: '', halaman: '', catatan: '', sub: [] });

/** Baris dianggap terisi bila kalimat induknya ATAU salah satu sub-poinnya ada isinya. */
export const barisLogBerisi = (row: BarisLog): boolean =>
    String(row.catatan ?? '').trim() !== '' || (row.sub ?? []).some((s) => String(s.catatan ?? '').trim() !== '');

/**
 * Langkah virtual "Log Revisi" — pengganti
 * `documents/fields/_revision_log.blade.php`.
 *
 * Hanya muncul pada draft revisi Tipe B, dan letaknya DI LUAR schema (langkah
 * ke-N+1). Ia mengisi lembar CATATAN REVISI yang tercetak di halaman DEPAN PDF,
 * dengan baris yang terakumulasi lintas revisi. JSA dikecualikan — lihat
 * `Document::usesRevisionLog()`.
 *
 * Tombol **Revisi** yang mengisi No. Rev / Tanggal / Hal. otomatis TIDAK ada di
 * sini melainkan di footer wizard, bersebelahan dengan Preview: keduanya
 * berbicara tentang CETAKAN, bukan tentang isian. Yang dikirimkannya ke sini
 * cuma hasilnya.
 */
export function RevisionLog({
    rows,
    onRows,
    edisi,
    noRevisi,
    onEdisi,
    onNoRevisi,
    judul,
    revisiKirim,
    pesan,
    tanggal,
    onTanggal,
    adaTombolRevisi = false,
}: {
    rows: BarisLog[];
    onRows: (baris: BarisLog[]) => void;
    edisi: number;
    noRevisi: number;
    onEdisi: (n: number) => void;
    onNoRevisi: (n: number) => void;
    judul: string;
    revisiKirim: { edisi: number; revisi: number } | null;
    pesan: string;
    /** Tanggal kop — null pada draft yang bukan salinan arsip (server yang menentukan). */
    tanggal: { terbit: string; revisi: string } | null;
    onTanggal: (patch: { terbit?: string; revisi?: string }) => void;
    /**
     * Apakah footer wizard benar-benar menggambar tombol "Revisi".
     *
     * Lembar dokumen lama (`Arsip/Catatan`) dan wizard salinan arsip TIDAK
     * memilikinya, jadi kalimat petunjuk di bawah tak boleh menyebutnya di sana
     * — petunjuk yang menunjuk tombol yang tak ada lebih membingungkan daripada
     * tak ada petunjuk sama sekali.
     */
    adaTombolRevisi?: boolean;
}) {
    const daftar = rows.length ? rows : [barisLogBaru()];

    const ubah = (i: number, patch: Partial<BarisLog>) =>
        onRows(daftar.map((r, n) => (n === i ? { ...r, ...patch } : r)));

    const hapus = (i: number) => {
        const sisa = daftar.filter((_, n) => n !== i);
        onRows(sisa.length ? sisa : [barisLogBaru()]);
    };

    const ubahSub = (i: number, j: number, catatan: string) =>
        ubah(i, { sub: (daftar[i]?.sub ?? []).map((s, n) => (n === j ? { ...s, catatan } : s)) });

    const tambahSub = (i: number) => ubah(i, { sub: [...(daftar[i]?.sub ?? []), { catatan: '' }] });

    const hapusSub = (i: number, j: number) => ubah(i, { sub: (daftar[i]?.sub ?? []).filter((_, n) => n !== j) });

    return (
        <div>
            {/* Blok tunggal "sebelum → sesudah", SELALU tergambar (F7b) — dulu
                kalimat "menjadi Edisi X Revisi Y" cuma muncul saat angkanya
                berbeda dari isian, dan hilang tepat saat kebetulan sama. */}
            <div className="mb-3 rounded-lg border bg-muted/40 p-3">
                <p className="mb-2 text-sm font-semibold">Penomoran versi ini</p>
                <div className="mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                    <span className="text-muted-foreground">Sekarang</span>
                    <span className="font-medium">
                        Edisi {edisi} · Revisi {noRevisi}
                    </span>
                    <span className="text-muted-foreground" aria-hidden>→</span>
                    <span className="text-muted-foreground">Setelah dikirim</span>
                    <span className="font-semibold text-primary">
                        {revisiKirim ? `Edisi ${revisiKirim.edisi} · Revisi ${revisiKirim.revisi}` : '—'}
                    </span>
                </div>

                <div className="grid gap-2 md:grid-cols-4">
                    <div>
                        <Label className="mb-1 text-sm font-semibold">Edisi</Label>
                        <Input type="number" min={1} name="edisi" value={edisi} onChange={(e) => onEdisi(Number(e.target.value))} />
                    </div>
                    <div>
                        <Label className="mb-1 text-sm font-semibold">Revisi</Label>
                        <Input
                            type="number"
                            min={0}
                            name="no_revisi"
                            value={noRevisi}
                            onChange={(e) => onNoRevisi(Number(e.target.value))}
                        />
                    </div>
                    <div className="md:col-span-2">
                        <Label className="mb-1 text-sm font-semibold">Judul Dokumen</Label>
                        <Input value={judul} readOnly disabled />
                    </div>
                </div>
                <p className="mt-1 text-xs text-muted-foreground">Otomatis (roll-over) — boleh diubah manual.</p>
            </div>

            {/* Tanggal yang TERCETAK di kop. Hanya salinan dokumen lama yang
                memilikinya: pada dokumen yang benar-benar ditinjau, tanggal
                terbit datang dari pengesahan dan tak boleh diketik tangan.
                Servernya yang memutuskan (prop null = jangan gambar). */}
            {tanggal ? (
                <div className="mb-3 rounded-lg border bg-muted/40 p-3">
                    <p className="mb-2 text-sm font-semibold">Tanggal pada kop cetak</p>
                    <div className="grid gap-2 md:grid-cols-2">
                        <div>
                            <Label className="mb-1 text-sm font-semibold">Tgl. Terbit / Efektif</Label>
                            <Input
                                type="date"
                                name="tanggal_terbit"
                                value={tanggal.terbit}
                                readOnly
                                disabled
                            />
                            <p className="mt-1 text-xs text-muted-foreground">
                                Bawaannya Tanggal Efektif yang diisi saat dokumen lamanya diunggah — bukan hari
                                pengetikan ulang.
                            </p>
                        </div>
                        <div>
                            <Label className="mb-1 text-sm font-semibold">Tgl. Revisi</Label>
                            <Input
                                type="date"
                                name="tanggal_revisi"
                                value={tanggal.revisi}
                                onChange={(e) => onTanggal({ revisi: e.target.value })}
                            />
                            <p className="mt-1 text-xs text-muted-foreground">Bawaan hari ini — boleh diubah manual.</p>
                        </div>
                    </div>
                </div>
            ) : null}

            <Label className="mb-1.5 font-semibold">Catatan Perubahan (Revisi)</Label>
            <p className="mb-2 text-xs text-muted-foreground">
                Satu baris = satu catatan pada lembar CATATAN REVISI; baris revisi terdahulu ikut tercetak.
                {adaTombolRevisi ? (
                    <>
                        {' '}
                        Tombol <strong>Revisi</strong> mengisi No. Rev, Tanggal, dan Hal.; catatannya Anda ketik
                        sendiri.
                    </>
                ) : null}
            </p>

            {pesan ? (
                <p className="mb-2 rounded-lg border border-chart-3/40 bg-chart-3/10 px-3 py-2 text-sm">{pesan}</p>
            ) : null}

            {daftar.map((row, i) => (
                // eslint-disable-next-line react/no-array-index-key
                <div key={i} className="mb-2 rounded-lg border bg-muted/40 p-3">
                    <div className="grid items-start gap-2 md:grid-cols-12">
                        <div className="md:col-span-2">
                            <Label className="mb-1 text-sm">No. Rev</Label>
                            <Input
                                type="number"
                                min={0}
                                name={`sections[catatan_revisi][${i}][no_rev]`}
                                value={row.no_rev ?? ''}
                                placeholder={String(revisiKirim?.revisi ?? '')}
                                data-optional
                                onChange={(e) => ubah(i, { no_rev: e.target.value })}
                            />
                        </div>
                        <div className="md:col-span-3">
                            <Label className="mb-1 text-sm">Tanggal Rev</Label>
                            <Input
                                type="date"
                                name={`sections[catatan_revisi][${i}][tanggal]`}
                                value={row.tanggal ?? ''}
                                data-optional
                                onChange={(e) => ubah(i, { tanggal: e.target.value })}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Label className="mb-1 text-sm">Hal.</Label>
                            <Input
                                name={`sections[catatan_revisi][${i}][halaman]`}
                                value={row.halaman ?? ''}
                                placeholder="mis. 1-4"
                                data-optional
                                onChange={(e) => ubah(i, { halaman: e.target.value })}
                            />
                        </div>
                        <div className="md:col-span-5">
                            <Label className="mb-1 text-sm">Catatan Revisi</Label>
                            {/* Nama bab yang berubah dipakai sebagai placeholder,
                                bukan diisikan: yang otomatis hanya nomor, tanggal,
                                dan halaman. */}
                            <Textarea
                                rows={2}
                                name={`sections[catatan_revisi][${i}][catatan]`}
                                value={row.catatan ?? ''}
                                placeholder={row.bab ? `Apa yang berubah pada ${row.bab}?` : 'mis. Perubahan'}
                                data-optional
                                onChange={(e) => ubah(i, { catatan: e.target.value })}
                            />
                        </div>
                    </div>

                    {/* Sub-poin a./b./c. — isi sesungguhnya sebuah sesi revisi.
                        Kalimat di atas cuma pembukanya ("Perubahan"), dan tiap
                        temuan diketik di sini supaya tercetak sebagai butir
                        bernomor huruf di dalam SATU kolom No. Rev. */}
                    <div className="mt-2 space-y-2 border-l-2 pl-3">
                        {(row.sub ?? []).map((sub, j) => (
                            // eslint-disable-next-line react/no-array-index-key
                            <div key={j} className="flex items-start gap-2">
                                <span className="text-muted-foreground mt-2 w-4 shrink-0 text-sm tabular-nums">
                                    {String.fromCharCode(97 + (j % 26))}.
                                </span>
                                <Textarea
                                    rows={2}
                                    name={`sections[catatan_revisi][${i}][sub][${j}][catatan]`}
                                    value={sub.catatan ?? ''}
                                    placeholder={
                                        sub.bab ? `Apa yang berubah pada ${sub.bab}?` : 'mis. Pimpinan Departemen dari ... ke ...'
                                    }
                                    data-optional
                                    onChange={(e) => ubahSub(i, j, e.target.value)}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    aria-label={`Hapus sub-poin ${String.fromCharCode(97 + (j % 26))}`}
                                    onClick={() => hapusSub(i, j)}
                                >
                                    <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} />
                                </Button>
                            </div>
                        ))}
                        <Button type="button" variant="outline" size="sm" onClick={() => tambahSub(i)}>
                            <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                            Tambah Sub-Poin
                        </Button>
                    </div>
                    <Button type="button" variant="outline" size="sm" className="mt-2" onClick={() => hapus(i)}>
                        <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} />
                        Hapus Poin Catatan
                    </Button>
                </div>
            ))}

            <Button type="button" variant="outline" size="sm" className="w-full" onClick={() => onRows([...daftar, barisLogBaru()])}>
                <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                Tambah Sesi Revisi
            </Button>
        </div>
    );
}
