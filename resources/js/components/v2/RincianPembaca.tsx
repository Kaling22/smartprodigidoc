/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/RincianPembaca.tsx`
 * (PATOKAN-GAYA-V2 Fase 1).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser. Yang berubah hanya
 * dari kit mana komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya
 * (lucide → hugeicons, PATOKAN §3.4). Berkas aslinya sengaja TIDAK disentuh:
 * halaman lama masih memakainya sebagai pembanding selama jendela pratinjau.
 * Fase 7 menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 *
 * DUA penyimpangan dari berkas asli, dan keduanya patokan Fase 0:
 *  • hijau `emerald-600/400` → `--chart-5`, hijau palet `.ui-v2` (§3.5: warna
 *    lain memakai token, nol warna baru). Preseden `v2/dasbor/KartuLog.tsx`;
 *  • `rounded-full` pada `<Avatar>` dilepas — `v2/Avatar` sudah membawanya, dan
 *    menuliskannya lagi membekukan angka yang seharusnya ikut kitnya (§3.2).
 */
import { AlertCircleIcon, CheckmarkCircle01Icon, CheckmarkCircle02Icon, LaptopIcon, SmartPhone01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Badge } from '@/components/ui-maia/badge';
import { Avatar } from '@/components/v2/Avatar';
import type { Cakupan } from '@/types/dasbor';
import type { RincianPembaca as Rincian } from '@/types/dokumen';

/**
 * RINCIAN PEMBACA — siapa sudah membaca, siapa belum.
 *
 * Satu tata letak, DUA pemakai: panel Distribusi di detail dokumen mutu dan
 * halaman rincian Informasi. Keduanya menjawab pertanyaan yang sama persis,
 * jadi menyalin markupnya berarti dua daftar yang suatu hari berbeda tanpa
 * alasan yang bisa dijelaskan.
 *
 * Kolom "Belum Membaca" sengaja diberi lencana merah: itulah satu-satunya
 * bagian yang bisa ditindaklanjuti.
 */
export function RincianPembaca({
    rincian,
    cakupan,
    kosong = 'Tak ada pengguna aktif yang bisa dihitung sebagai sasaran, jadi tak ada yang bisa diukur.',
}: {
    rincian: Rincian;
    cakupan: Cakupan;
    kosong?: string;
}) {
    if (cakupan.sasaran === 0) {
        return <p className="text-muted-foreground text-sm">{kosong}</p>;
    }

    return (
        <div className="grid gap-4 md:grid-cols-2">
            <div>
                <p className="mb-2 flex items-center gap-1.5 text-sm font-semibold">
                    <HugeiconsIcon
                        icon={CheckmarkCircle01Icon}
                        strokeWidth={1.5}
                        className="text-chart-5 size-4"
                        aria-hidden="true"
                    />
                    Sudah Membaca
                    <Badge variant="secondary">{rincian.sudah.length}</Badge>
                </p>
                {rincian.sudah.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada yang membukanya.</p>
                ) : (
                    rincian.sudah.map((r, i) => (
                        <div key={i} className="flex items-center justify-between gap-2 border-b py-1">
                            <span className="flex min-w-0 items-center gap-1.5 text-sm">
                                <Avatar nama={r.nama} foto={r.foto} className="size-6 shrink-0" />
                                <HugeiconsIcon
                                    icon={r.platform === 'mobile' ? SmartPhone01Icon : LaptopIcon}
                                    strokeWidth={1.5}
                                    className="text-muted-foreground size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                <span className="truncate">{r.nama}</span>
                                <span className="text-muted-foreground shrink-0">· {r.jabatan}</span>
                            </span>
                            <span className="text-muted-foreground text-xs">{r.waktu} WITA</span>
                        </div>
                    ))
                )}
            </div>

            <div>
                <p className="mb-2 flex items-center gap-1.5 text-sm font-semibold">
                    <HugeiconsIcon
                        icon={AlertCircleIcon}
                        strokeWidth={1.5}
                        className="text-destructive size-4"
                        aria-hidden="true"
                    />
                    Belum Membaca
                    <Badge variant="destructive">{rincian.belum.length}</Badge>
                </p>
                {rincian.belum.length === 0 ? (
                    <p className="text-chart-5 flex items-center gap-1.5 text-sm">
                        <HugeiconsIcon
                            icon={CheckmarkCircle02Icon}
                            strokeWidth={1.5}
                            className="size-3.5"
                            aria-hidden="true"
                        />
                        Semua sudah membaca.
                    </p>
                ) : (
                    rincian.belum.map((u, i) => (
                        <div key={i} className="flex items-center gap-1.5 border-b py-1 text-sm">
                            <Avatar nama={u.nama} foto={u.foto} className="size-6 shrink-0" />
                            <span className="truncate">{u.nama}</span>
                            <span className="text-muted-foreground shrink-0"> · {u.jabatan}</span>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}
