import {
    CheckmarkCircle01Icon, FileEditIcon, InformationCircleIcon, LinkSquare01Icon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { FieldGroup } from '@/components/ui-maia/field';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { barisLogBaru, RevisionLog, type BarisLog } from '@/components/v2/dokumen/fields/RevisionLog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { ArsipCatatanProps } from '@/types/distribusi';

/**
 * Lembar CATATAN REVISI dokumen lama — kembaran V2
 * `pages/Documents/Arsip/Catatan.tsx`.
 *
 * Isian lembarnya adalah komponen yang SAMA dengan langkah "Log Revisi" wizard
 * (`v2/dokumen/fields/RevisionLog`), dan penyimpannya juga sama
 * (`DocumentWizard::persistRevisionLog`) — jadi lembar dokumen lama mustahil
 * tersimpan dalam bentuk yang berbeda dari lembar dokumen web.
 *
 * Pratinjau di panel kanan bukan hiasan: berkas itulah yang isinya diketik ulang
 * di wizard sesudah lembar ini tersimpan. `src`-nya ditulis langsung di markup,
 * pola yang sama dengan panel pratinjau wizard.
 *
 * Sejak rencana pra-produksi Fase 2 (butir 7) halaman ini tinggal SATU jalan:
 * pilihan "gabung" beserta isian `potong_halaman`/`halaman_awal` dicabut, dan
 * dokumen hasil salinan langsung Berlaku begitu dikirim dari wizard.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.9 — jarak antar-isian jadi milik `FieldGroup`, bukan `mb-1.5`/`mb-3`
 *    yang diketik di berkas ini.
 *  · §3.2/§3.5 — pita keterangan `bg-accent rounded-lg` dan daftar galat
 *    `border-destructive/40` → komponen `alert` resmi (`variant="destructive"`
 *    untuk galat). Nol warna baru, nol radius yang diketik ulang.
 *  · §3.6 — nomor · judul · lencana pindah ke prop `sub` layout.
 */
export default function DocumentsArsipCatatan() {
    const { document: doc, baris, revisiKirim, errors } =
        usePage<PageProps & ArsipCatatanProps>().props;

    const [konfirmasi, setKonfirmasi] = useState(false);
    const [logRows, setLogRows] = useState<BarisLog[]>(() =>
        Array.isArray(baris) && baris.length ? (baris as BarisLog[]) : [barisLogBaru()],
    );

    const form = useForm<{ edisi: number; no_revisi: number }>({
        edisi: doc.edisi,
        no_revisi: doc.no_revisi,
    });

    // Baris lembar tinggal di state-nya sendiri lalu ditempelkan saat kirim —
    // bentuk kiriman JSON bersarang yang sama dengan langkah wizard (Fase 9.5),
    // dan `persistRevisionLog` membaca keduanya lewat `input('sections.…')`.
    const kirim = () => {
        form.transform((data) => ({ ...data, sections: { catatan_revisi: logRows } }));
        form.post(route('documents.arsip.catatan.store', doc.id));
    };

    const daftarGalat = Object.values(errors ?? {});

    return (
        <AppLayout
            judul={`Catatan Revisi Dokumen Lama: ${doc.nomor}`}
            sub={
                <span className="flex flex-wrap items-center gap-1.5">
                    <span className="font-mono">{doc.nomor}</span> — {doc.judul}
                    <Badge variant="outline">{doc.jenis}</Badge>
                    <Badge variant="outline">{doc.dept}</Badge>
                </span>
            }
        >
            <h2 className="text-lg font-bold">Lembar Catatan Revisi</h2>

            <Alert>
                <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                <AlertDescription>
                    Dokumen ini <strong>sudah Berlaku</strong>. Sesudah lembar ini tersimpan, isinya
                    diketik ulang di wizard — berkas yang Anda unggah{' '}
                    <strong>tak pernah ditimpa</strong> dan tetap jadi rujukan.
                </AlertDescription>
            </Alert>

            {daftarGalat.length > 0 ? (
                <Alert variant="destructive">
                    <AlertDescription>
                        <ul className="list-disc pl-4">
                            {daftarGalat.map((e) => (
                                <li key={e}>{e}</li>
                            ))}
                        </ul>
                    </AlertDescription>
                </Alert>
            ) : null}

            <form
                onSubmit={(e: FormEvent) => {
                    e.preventDefault();
                    setKonfirmasi(true);
                }}
                className="grid gap-4 lg:grid-cols-12"
            >
                {/* KIRI: formulir */}
                <Card className="lg:col-span-7">
                    <CardContent>
                        <FieldGroup>
                            <RevisionLog
                                rows={logRows}
                                onRows={setLogRows}
                                edisi={form.data.edisi}
                                noRevisi={form.data.no_revisi}
                                onEdisi={(n) => form.setData('edisi', n)}
                                onNoRevisi={(n) => form.setData('no_revisi', n)}
                                judul={doc.judul}
                                revisiKirim={revisiKirim}
                                // Tombol "Revisi" (isi otomatis) hidup di footer wizard,
                                // bukan di lembarnya — jadi di halaman ini tak ada yang
                                // bisa menerbitkan pesan itu.
                                pesan=""
                                // Tanggal kop diisi di langkah Log Revisi WIZARD, pada
                                // draft salinannya. Halaman ini masih menyunting dokumen
                                // unggahan aslinya, yang PDF-nya bukan cetakan kita.
                                tanggal={null}
                                onTanggal={() => {}}
                            />
                        </FieldGroup>

                        <div className="mt-6 flex flex-wrap gap-2">
                            <Button type="submit" disabled={form.processing}>
                                <HugeiconsIcon
                                    icon={CheckmarkCircle01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Simpan &amp; Salin ke Web
                            </Button>
                            <Button asChild variant="ghost">
                                <Link href={route('documents.published')}>Nanti Saja</Link>
                            </Button>
                        </div>
                        <p className="text-muted-foreground mt-2 text-xs">
                            "Nanti Saja" tak membatalkan apa pun — dokumennya sudah terdaftar &amp;
                            Berlaku. Lembar ini bisa diisi belakangan lewat menu Dokumen Berlaku, tombol
                            Perbaiki.
                        </p>
                    </CardContent>
                </Card>

                {/* KANAN: berkas yang baru diunggah. */}
                <Card className="gap-0 py-0 lg:sticky lg:top-4 lg:col-span-5 lg:self-start">
                    <CardHeader className="flex items-center justify-between border-b px-4 py-3">
                        <CardTitle className="flex items-center gap-2 text-sm">
                            <HugeiconsIcon
                                icon={FileEditIcon}
                                strokeWidth={1.5}
                                className="size-4"
                                aria-hidden="true"
                            />
                            Berkas yang diunggah
                        </CardTitle>
                        <Button asChild size="sm" variant="outline">
                            <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                                <HugeiconsIcon
                                    icon={LinkSquare01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Buka
                            </a>
                        </Button>
                    </CardHeader>
                    <CardContent className="h-[78vh] p-0">
                        <iframe
                            title="Berkas dokumen lama"
                            src={`${route('documents.pdf', doc.id)}#toolbar=1&navpanes=0&view=Fit`}
                            className="size-full border-0"
                        />
                    </CardContent>
                    <CardFooter className="text-muted-foreground border-t px-4 py-3 text-xs">
                        Berkas inilah yang isinya diketik ulang di wizard — telusuri halamannya di sini
                        sambil mengetik.
                    </CardFooter>
                </Card>
            </form>

            <ConfirmDialog
                buka={konfirmasi}
                onUbahBuka={setKonfirmasi}
                judul="Lanjutkan?"
                pesan="Dokumen akan disalin ke wizard untuk diketik ulang. Berkas unggahan tetap bisa dilihat sebagai rujukan, dan sesudah dikirim dokumen langsung Berlaku."
                tombolYa="Ya, lanjutkan"
                onKonfirmasi={kirim}
            />
        </AppLayout>
    );
}
