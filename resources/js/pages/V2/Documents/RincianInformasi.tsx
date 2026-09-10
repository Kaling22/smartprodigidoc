import { ArrowLeft01Icon, Cancel01Icon, FilterIcon, RadioIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Field, FieldLabel } from '@/components/ui-maia/field';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import { PitaCakupan } from '@/components/v2/dasbor/PitaCakupan';
import { RincianPembaca } from '@/components/v2/RincianPembaca';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { RincianInformasiProps } from '@/types/distribusi';

/**
 * RINCIAN distribusi satu informasi — kembaran V2
 * `pages/Documents/RincianInformasi.tsx`.
 *
 * Daftar Distribusi menjawab "berapa persen" dan penyingkap "Per Departemen"
 * menjawab "departemen mana yang tertinggal". Yang belum pernah bisa dibaca
 * adalah SIAPA ORANGNYA — itulah halaman ini, dan hanya itu.
 *
 * Tata letaknya dipinjam utuh dari panel Distribusi di detail dokumen mutu
 * lewat `components/v2/RincianPembaca`: pertanyaannya sama, jadi jawabannya tak
 * pantas tampil dalam dua bentuk berbeda — dan kini panel itu juga digambar
 * dengan `CardHeader` + `CardTitle` yang sama persis dengan `V2/Documents/Show`.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — baris nomor · kategori · revisi dan tombol Kembali pindah ke prop
 *    `sub`/`aksi` layout; jarak & talangnya dipasang sekali di layout.
 *  · §3.9 — pemilih departemen memakai `Field` + `FieldLabel`, bukan `Label`
 *    ber-`mb-1.5` yang angkanya diketik sendiri.
 *  · Kartunya tak lagi `gap-0 py-0` dengan `px-4 py-3` per bagian: jaraknya
 *    diserahkan ke `ui-maia/card`, sepadan seluruh kartu V2 lain.
 */
export default function DocumentsRincianInformasi() {
    const { informasi, cakupan, rincian, departments, deptId } =
        usePage<PageProps & RincianInformasiProps>().props;

    return (
        <AppLayout
            judul="Rincian Distribusi Informasi"
            sub={
                <>
                    <span className="font-mono">{informasi.nomor}</span> · {informasi.kategori}
                    {informasi.revisi ? ` · ${informasi.revisi}` : ''}
                </>
            }
            aksi={
                <Button asChild size="sm" variant="outline">
                    <Link href={route('documents.distribution', { sumber: 'informasi' })}>
                        <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={1.5} className="size-4" />
                        Kembali ke Distribusi
                    </Link>
                </Button>
            }
        >
            <h2 className="text-lg font-bold">{informasi.judul}</h2>

            <Card>
                <CardHeader className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="flex items-center gap-2">
                        <HugeiconsIcon
                            icon={RadioIcon}
                            strokeWidth={1.5}
                            className="size-4"
                            aria-hidden="true"
                        />
                        Distribusi
                    </CardTitle>
                    <div className="min-w-56">
                        <PitaCakupan c={cakupan} />
                    </div>
                </CardHeader>

                {/* Hanya pemegang `document.view_all` yang melihat pemilih ini —
                    SH/DH sudah terkurung ke departemennya di sisi server, jadi
                    daftarnya memang kosong bagi mereka (pakem P4). */}
                {departments.length > 0 ? (
                    <CardContent className="border-b pb-6">
                        <PemilihDept informasiId={informasi.id} departments={departments} deptId={deptId} />
                    </CardContent>
                ) : null}

                <CardContent>
                    <RincianPembaca
                        rincian={rincian}
                        cakupan={cakupan}
                        kosong="Tak ada pengguna aktif yang dihitung sebagai sasaran di lingkup ini, jadi tak ada yang bisa diukur."
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}

/**
 * Penyempit lingkup ke satu departemen.
 *
 * Tetap query string, bukan pemuatan diam-diam: hasilnya bisa ditandai &
 * dibagikan, persis seperti form GET yang digantikannya.
 */
function PemilihDept({
    informasiId,
    departments,
    deptId,
}: {
    informasiId: number;
    departments: { id: number; code: string }[];
    deptId: number | null;
}) {
    /** Radix Select tak menerima nilai kosong sebagai item; `*` = "Semua". */
    const SEMUA = '*';
    const [pilihan, setPilihan] = useState(deptId ? String(deptId) : SEMUA);

    function terapkan(e: FormEvent) {
        e.preventDefault();
        router.get(route('documents.rincianInformasi', informasiId), {
            ...(pilihan === SEMUA ? {} : { department_id: pilihan }),
        });
    }

    return (
        <form onSubmit={terapkan} className="flex flex-wrap items-end gap-2">
            <Field className="w-auto min-w-48">
                <FieldLabel htmlFor="dept">Departemen</FieldLabel>
                <Select value={pilihan} onValueChange={setPilihan}>
                    <SelectTrigger id="dept" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={SEMUA}>Semua (7 departemen)</SelectItem>
                        {departments.map((d) => (
                            <SelectItem key={d.id} value={String(d.id)}>
                                {d.code}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </Field>
            <Button type="submit" size="sm" variant="secondary">
                <HugeiconsIcon icon={FilterIcon} strokeWidth={1.5} className="size-4" />
                Terapkan
            </Button>
            {deptId ? (
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    title="Reset"
                    aria-label="Reset penyaring departemen"
                    onClick={() => router.get(route('documents.rincianInformasi', informasiId))}
                >
                    <HugeiconsIcon icon={Cancel01Icon} strokeWidth={1.5} className="size-4" />
                </Button>
            ) : null}
        </form>
    );
}
