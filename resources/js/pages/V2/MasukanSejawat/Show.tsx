import {
    InboxIcon, InformationCircleIcon, Message01Icon, ViewIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { StatusBadge } from '@/components/v2/StatusBadge';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { MasukanSejawatShowProps } from '@/types/tinjau';

/**
 * Halaman baca-saja seluruh masukan sejawat atas satu dokumen — kembaran V2
 * `pages/MasukanSejawat/Show.tsx`.
 *
 * SATU halaman, bukan modal per baris: Dokumen Saya, Log Pesan, dan lonceng
 * ketiganya butuh tujuan yang sama, dan tiga modal yang harus sepakat isinya
 * adalah tiga modal yang suatu hari tidak sepakat.
 *
 * Nama bagian dan penunjuk item datang SUDAH JADI dari server: keduanya membaca
 * schema (`tujuan_ruang_lingkup` → "I. TUJUAN"; `L0-B1-P2` → "Langkah 1.2.3 —
 * Tindakan Pengendalian"), dan schema bukan urusan lapisan tampilan.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — judul dokumen, baris nomor · dept · status, dan tombol "Lihat
 *    Dokumen" pindah ke prop `sub`/`aksi` layout.
 *  · §3.2/§3.5 — pita `bg-accent … rounded-lg` jadi `alert` resmi; kotak
 *    per-bagian `rounded-lg` jadi `rounded-2xl`, radius kotak yang berperan
 *    sebagai kartu. Kartunya sendiri tak lagi `gap-0 py-0` dengan `px-4 py-3`
 *    per bagian (preseden `RincianInformasi`).
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 */
export default function MasukanSejawatShow() {
    const { document: doc, masukan } = usePage<PageProps & MasukanSejawatShowProps>().props;

    return (
        <AppLayout
            judul={`Masukan Sejawat: ${doc.nomor}`}
            sub={
                <span className="flex flex-wrap items-center gap-1.5">
                    <span className="font-medium">{doc.judul}</span>
                    <span className="text-primary font-mono text-sm">{doc.nomor}</span>
                    <Badge variant="outline">{doc.dept ?? '—'}</Badge>
                    <StatusBadge status={doc.status} />
                </span>
            }
            aksi={
                <Button asChild variant="outline" size="sm">
                    <Link href={route('documents.show', doc.id)}>
                        <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-4" />
                        Lihat Dokumen
                    </Link>
                </Button>
            }
        >
            <Alert>
                <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                <AlertDescription>
                    Catatan dari rekan sedepartemen atas dokumen ini. Ini{' '}
                    <strong>bukan hasil peninjauan</strong> — tak ada status yang berubah karenanya,
                    dan Anda bebas memakainya atau tidak saat menyunting dokumen.
                </AlertDescription>
            </Alert>

            {masukan.length === 0 ? (
                <Card>
                    <CardContent>
                        <Empty className="border-0">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <HugeiconsIcon
                                        icon={InboxIcon}
                                        strokeWidth={1.5}
                                        className="size-6"
                                    />
                                </EmptyMedia>
                                <EmptyTitle>
                                    Belum ada masukan sejawat atas dokumen ini.
                                </EmptyTitle>
                            </EmptyHeader>
                        </Empty>
                    </CardContent>
                </Card>
            ) : (
                masukan.map((m) => (
                    // Anchor per kartu supaya Log Pesan & lonceng bisa menaut
                    // langsung ke SATU masukan, bukan hanya ke halamannya.
                    <Card key={m.id} id={`m${m.id}`}>
                        <CardHeader className="flex flex-wrap items-center justify-between gap-2">
                            <CardTitle className="flex items-center gap-2">
                                <HugeiconsIcon
                                    icon={Message01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                {m.oleh}
                            </CardTitle>
                            <span className="text-muted-foreground text-sm">{m.waktu}</span>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            {m.ringkasan ? (
                                <div>
                                    <div className="text-muted-foreground mb-1 text-xs font-semibold">
                                        Ringkasan / Catatan Umum
                                    </div>
                                    <p className="whitespace-pre-line">{m.ringkasan}</p>
                                </div>
                            ) : null}

                            {m.perSection.map((s, i) => (
                                <div key={i} className="grid gap-2 rounded-2xl border p-3">
                                    <div className="text-sm font-semibold">{s.label}</div>
                                    {s.items.map((c, j) => (
                                        <div key={j} className="flex flex-wrap gap-2">
                                            <Badge variant="secondary" className="whitespace-nowrap">
                                                {c.ref}
                                            </Badge>
                                            <span className="whitespace-pre-line">{c.komentar}</span>
                                        </div>
                                    ))}
                                </div>
                            ))}

                            {m.perSection.length === 0 && !m.ringkasan ? (
                                <p className="text-muted-foreground text-sm">(Masukan kosong)</p>
                            ) : null}
                        </CardContent>
                    </Card>
                ))
            )}
        </AppLayout>
    );
}
