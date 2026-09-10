import { HistoryIcon, LinkSquare01Icon, RefreshCwIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { FormInformasi } from '@/components/v2/FormInformasi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { InformasiPerbaruiProps } from '@/types/informasi';

/**
 * "Perbarui Informasi" — kembaran V2 `pages/Informasi/Perbarui.tsx`.
 *
 * Revisi di modul ini berarti UNGGAH BERKAS BARU: versi sekarang turun jadi
 * riwayat, tidak dihapus. Nomor & kategori terkunci — keduanya diwarisi dari
 * `induk` dan `StoreInformasiRequest` menolak kiriman yang memuatnya, sehingga
 * riwayat satu nomor mustahil tercecer ke nomor lain karena salah ketik.
 *
 * Konfirmasi sebelum kiriman dipegang `v2/FormInformasi` (terkendali, §3.8) —
 * halaman ini tak menambah lapis konfirmasi kedua.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — baris nomor · judul · lencana revisi pindah ke prop `sub` layout;
 *    jarak & talangnya dipasang sekali di layout.
 *  · Kartu formulir tetap TANPA `CardHeader`: peringatan yang menerangkan
 *    akibat unggahan sudah dibawa `Alert` di dalamnya, dan kepala kartu kedua
 *    di atasnya cuma mengulang kalimat yang sama dengan huruf lebih besar.
 *  · `CardContent className="grid gap-5"` dilepas: jarak antara peringatan dan
 *    formulir diserahkan ke `ui-maia/card` + `FieldGroup`, bukan angka yang
 *    diketik di halaman (preseden `V2/Documents/RincianInformasi`).
 */
export default function InformasiPerbarui() {
    const props = usePage<PageProps & InformasiPerbaruiProps>().props;
    const { induk } = props;

    return (
        <AppLayout
            judul={`Perbarui ${induk.nomor}`}
            remah={[
                { label: props.label, href: route('informasi.index', { kategori: props.kategori }) },
                { label: `Perbarui ${induk.nomor}` },
            ]}
            sub={
                <>
                    <span className="font-mono">{induk.nomor}</span> — {induk.judul}
                    {induk.revisi ? (
                        <Badge variant="secondary" className="ml-2">
                            {induk.revisi}
                        </Badge>
                    ) : null}
                </>
            }
        >
            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent>
                        <Alert className="mb-6">
                            <HugeiconsIcon icon={RefreshCwIcon} strokeWidth={1.5} className="size-4" />
                            <AlertDescription>
                                Berkas ini menjadi <strong>versi yang berlaku</strong> untuk nomor{' '}
                                <span className="font-mono">{induk.nomor}</span>. Versi sekarang pindah
                                ke <strong>Riwayat</strong>, tidak dihapus.
                            </AlertDescription>
                        </Alert>

                        <FormInformasi {...props} />
                    </CardContent>
                </Card>

                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-1.5">
                            <HugeiconsIcon
                                icon={HistoryIcon}
                                strokeWidth={1.5}
                                className="size-4"
                                aria-hidden="true"
                            />
                            Versi Sekarang
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2 text-sm">
                        <Rincian label="Judul">{induk.judul}</Rincian>
                        {induk.revisi ? <Rincian label="Edisi / Revisi">{induk.revisi}</Rincian> : null}
                        {induk.tanggal ? <Rincian label="Tgl Efektif">{induk.tanggal}</Rincian> : null}
                        <Rincian label="Diunggah">{induk.diunggah_lengkap} WITA</Rincian>
                        <Rincian label="Oleh">{induk.oleh ?? '—'}</Rincian>

                        <Button asChild variant="outline" size="sm" className="mt-2 w-fit">
                            <a href={route('informasi.file', induk.id)} target="_blank" rel="noopener">
                                <HugeiconsIcon
                                    icon={LinkSquare01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Buka berkas sekarang
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function Rincian({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid grid-cols-[5rem_1fr] gap-2">
            <span className="text-muted-foreground">{label}</span>
            <span>{children}</span>
        </div>
    );
}
