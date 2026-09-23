import {
    ArrowTurnBackwardIcon,
    ClipboardCheckIcon,
    Exchange01Icon,
    FileEditIcon,
    InboxIcon,
    ViewIcon,
} from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { Link, router, usePage } from "@inertiajs/react";

import { Badge } from "@/components/ui-maia/badge";
import { Button } from "@/components/ui-maia/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui-maia/card";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui-maia/empty";
import { ConfirmDialog } from "@/components/v2/ConfirmDialog";
import {
    DataTable,
    Paginasi,
    type Kolom,
    type Paginator,
} from "@/components/v2/DataTable";
import { NomorDokumen } from "@/components/v2/NomorDokumen";
import { PenyaringDokumen } from "@/components/v2/PenyaringDokumen";
import { AppLayout } from "@/layouts/V2/AppLayout";
import type { PageProps } from "@/types";
import type { BarisTinjau } from "@/types/tinjau";

/**
 * "Tinjau Dokumen" — kembaran V2 `pages/Review/Index.tsx`.
 *
 * DUA tabel di satu halaman: antrean yang menunggu tinjauan, dan "Status
 * Revisi" (dokumen yang peninjau ini kembalikan, dan yang masih bisa ia
 * batalkan penolakannya — v3.1 §4.3). Keduanya sengaja memakai `sort`/`dir`
 * yang SAMA: membiarkan yang satu bisa diurutkan sementara yang lain tidak
 * justru terbaca sebagai tombol yang rusak.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — tiap tabel duduk di SATU kartu: tabel di `CardContent` tanpa
 *    talang, paginasi di `CardFooter` bertepi atas (arketipe
 *    `V2/Documents/Revisions.tsx`). Judul "Status Revisi" yang di V1 berdiri
 *    sebagai `<h2>` lepas di antara dua kartu kini jadi `CardHeader` kartunya
 *    sendiri — judul yang melayang di atas kartu membaca seperti judul halaman
 *    kedua, bukan nama tabel.
 *  · §3.6 — paragraf "Dokumen yang menunggu peninjauan Anda." pindah ke prop
 *    `sub` layout; talang & iramanya dipasang sekali di `layouts/V2/AppLayout`.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 */
export default function ReviewIndex() {
    const { documents, statusRevisi, filters } = usePage<
        PageProps & {
            documents: Paginator<BarisTinjau>;
            statusRevisi: BarisTinjau[];
            filters: Record<string, string | undefined>;
        }
    >().props;

    const antrean: Kolom<BarisTinjau>[] = [
        {
            judul: "No. Dokumen",
            urut: "nomor",
            render: (d) => <NomorDokumen doc={d} />,
        },
        {
            judul: "Judul",
            render: (d) => <span className="font-medium">{d.judul}</span>,
        },
        {
            judul: "Jenis",
            render: (d) => <Badge variant="outline">{d.jenis ?? "—"}</Badge>,
        },
        {
            judul: "Dept",
            render: (d) => <Badge variant="outline">{d.dept ?? "—"}</Badge>,
        },
        {
            judul: "Pembuat",
            render: (d) => <span className="text-sm">{d.pembuat ?? "—"}</span>,
        },
        {
            judul: "Edisi/Revisi",
            urut: "revisi",
            // Angka DOKUMEN (yang tercetak di kop), bukan penghitung putaran
            // peninjauan — lihat catatan di `Review/Show.tsx`.
            render: (d) => (
                <span className="text-sm">
                    Ed. {d.edisi} · Rev. {d.no_revisi}
                </span>
            ),
        },
        {
            judul: "Aksi",
            kelas: "w-px text-right whitespace-nowrap",
            render: (d) => (
                <div className="flex items-center justify-end gap-2">
                    <TombolPdf id={d.id} />
                    {/* Alihkan langsung dari antrian: GL SHE yang mejanya penuh
                        tak perlu membuka satu per satu dokumen hanya untuk
                        melepasnya. Papan ketersediaannya TIDAK dirakit di sini
                        (satu perhitungan kandidat per baris JSA), jadi tombol
                        ini mengantar ke halaman tinjau dan membukanya di sana. */}
                    {d.boleh_alih ? (
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={route("review.show", {
                                    document: d.id,
                                    alih: 1,
                                })}
                            >
                                <HugeiconsIcon
                                    icon={Exchange01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Alihkan
                            </Link>
                        </Button>
                    ) : null}
                    <Button asChild size="sm">
                        <Link href={route("review.show", d.id)}>
                            <HugeiconsIcon
                                icon={ClipboardCheckIcon}
                                strokeWidth={1.5}
                                className="size-4"
                            />
                            Tinjau
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    const revisi: Kolom<BarisTinjau>[] = [
        {
            judul: "No. Dokumen",
            urut: "nomor",
            render: (d) => <NomorDokumen doc={d} />,
        },
        {
            judul: "Judul",
            render: (d) => <span className="font-medium">{d.judul}</span>,
        },
        {
            judul: "Pembuat",
            render: (d) => <span className="text-sm">{d.pembuat ?? "—"}</span>,
        },
        {
            judul: "Tahap",
            render: () => (
                <Badge variant="destructive">
                    Ditolak — menunggu revisi pembuat
                </Badge>
            ),
        },
        {
            judul: "Aksi",
            kelas: "w-px text-right whitespace-nowrap",
            render: (d) => (
                <div className="flex items-center justify-end gap-2">
                    <TombolPdf id={d.id} />
                    <Button asChild variant="outline" size="sm">
                        <Link href={route("documents.show", d.id)}>
                            <HugeiconsIcon
                                icon={ViewIcon}
                                strokeWidth={1.5}
                                className="size-4"
                            />
                            Lihat
                        </Link>
                    </Button>
                    <ConfirmDialog
                        judul="Batalkan Revisi?"
                        pesan="Batalkan penolakan? Dokumen kembali ditinjau (in_review) untuk Anda periksa ulang."
                        tombolYa="Ya, batalkan"
                        onKonfirmasi={() =>
                            router.post(
                                route("review.cancelRevision", d.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                        pemicu={
                            <Button variant="secondary" size="sm">
                                <HugeiconsIcon
                                    icon={ArrowTurnBackwardIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Batalkan Revisi
                            </Button>
                        }
                    />
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul="Tinjau Dokumen"
            sub="Dokumen yang menunggu peninjauan Anda."
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route("review.index")}
                        filters={filters}
                        labelCari="Cari (no. dokumen / judul)"
                        placeholderCari="mis. no. dokumen atau judul…"
                    />
                </CardHeader>
                <CardContent className="px-0">
                    <DataTable
                        kolom={antrean}
                        baris={documents.data}
                        kunci={(d) => d.id}
                        kosong={
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
                                        Tidak ada dokumen untuk ditinjau.
                                    </EmptyTitle>
                                    {filters.q ? (
                                        <EmptyDescription>
                                            Coba kata kunci lain.
                                        </EmptyDescription>
                                    ) : null}
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>
                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={documents} />
                </CardFooter>
            </Card>

            <Card>
                <CardHeader className="border-b">
                    <CardTitle className="flex items-center gap-1.5">
                        <HugeiconsIcon
                            icon={ArrowTurnBackwardIcon}
                            strokeWidth={1.5}
                            className="size-4"
                        />
                        Status Revisi
                    </CardTitle>
                    <CardDescription>
                        Dokumen yang Anda kembalikan.
                    </CardDescription>
                </CardHeader>
                <CardContent className="px-0">
                    <DataTable
                        kolom={revisi}
                        baris={statusRevisi}
                        kunci={(d) => d.id}
                        kosong={
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
                                        Tidak ada dokumen yang Anda tolak.
                                    </EmptyTitle>
                                    {filters.q ? (
                                        <EmptyDescription>
                                            Coba kata kunci lain.
                                        </EmptyDescription>
                                    ) : null}
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}

function TombolPdf({ id }: { id: number }) {
    return (
        <Button asChild variant="outline" size="icon" title="Lihat PDF">
            <a href={route("documents.pdf", id)} target="_blank" rel="noopener">
                <HugeiconsIcon
                    icon={FileEditIcon}
                    strokeWidth={1.5}
                    className="size-4"
                />
            </a>
        </Button>
    );
}
