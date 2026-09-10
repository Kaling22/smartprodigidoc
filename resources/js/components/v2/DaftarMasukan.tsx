/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/DaftarMasukan.tsx`
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
 *  • kotak tiap masukan `rounded-lg` → `rounded-2xl` — kotak buatan sendiri
 *    yang berperan sebagai kartu disamakan dengan `ui-maia/card` (§3.2);
 *  • hijau `emerald-600/400` → `--chart-5`, hijau palet `.ui-v2` (§3.5).
 */
import { CheckmarkCircle02Icon, InboxIcon, ReplyIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Input } from '@/components/ui-maia/input';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import type { BarisMasukan } from '@/types/dokumen';

/**
 * Daftar masukan lapangan.
 *
 * Dipakai DUA tempat: panel "Masukan Lapangan" di halaman detail dokumen, dan
 * riwayat "Masukan Anda sebelumnya" di jendela Non-Staff. Satu komponen supaya
 * tampilan status & balasan tak pernah berbeda antar halaman.
 *
 * `bolehMembalas` WAJIB false saat komponen ini dipasang di dalam form lain —
 * form bersarang tidak sah di HTML dan tak akan terkirim. (Itu tetap berlaku
 * di React: yang dirender pada akhirnya markup yang sama.)
 */
export function DaftarMasukan({
    masukan,
    bolehMembalas,
}: {
    masukan: BarisMasukan[];
    bolehMembalas: boolean;
}) {
    if (masukan.length === 0) {
        return (
            <p className="text-muted-foreground flex items-center gap-2 text-sm">
                <HugeiconsIcon icon={InboxIcon} strokeWidth={1.5} className="size-4" aria-hidden="true" />
                Belum ada masukan.
            </p>
        );
    }

    return (
        <div className="grid gap-2">
            {masukan.map((m) => (
                <KartuMasukan key={m.id} m={m} bolehMembalas={bolehMembalas} />
            ))}
        </div>
    );
}

/**
 * Rona status memakai `variant` Badge registry, bukan empat warna karangan.
 * Labelnya sendiri datang dari server (`DocumentFeedback::statusLabel()`), jadi
 * tak ada teks status yang diketik di sini.
 */
function ronaStatus(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'baru') return 'destructive';
    if (status === 'diadopsi') return 'default';

    return 'secondary';
}

function KartuMasukan({ m, bolehMembalas }: { m: BarisMasukan; bolehMembalas: boolean }) {
    const form = useForm({ balasan: '' });
    const [konfirmasi, setKonfirmasi] = useState(false);

    /*
    | Tombolnya tetap `type="submit"` dan konfirmasi MENYUSUL sesudah formulir
    | lolos — pola terkendali `ConfirmDialog` (PATOKAN §3.8). Dengan pemicu
    | biasa, tombolnya terpaksa `type="button"` dan `required` peramban pada
    | kotak balasan ikut mati diam-diam.
    */
    function balas(e: FormEvent) {
        e.preventDefault();
        setKonfirmasi(true);
    }

    function kirim() {
        form.post(route('documents.feedback.respond', m.id), {
            preserveScroll: true,
            onSuccess: () => form.reset('balasan'),
        });
    }

    return (
        <div className="rounded-2xl border p-2">
            <div className="flex items-start justify-between gap-2">
                <span className="text-muted-foreground font-mono text-xs">{m.nomor}</span>
                <Badge variant={ronaStatus(m.status)}>{m.status_label}</Badge>
            </div>

            <p className="text-sm">{m.isi}</p>
            <p className="text-muted-foreground text-xs">
                {m.oleh ?? '—'} · {m.waktu} WITA
            </p>

            {m.balasan ? (
                <div className="bg-muted mt-2 rounded-r border-l-4 px-2 py-1 text-sm">
                    <span className="inline-flex items-center gap-1 font-semibold">
                        <HugeiconsIcon icon={ReplyIcon} strokeWidth={1.5} className="size-3.5" aria-hidden="true" />
                        Balasan
                    </span>
                    {m.pembalas ? <span className="text-muted-foreground"> — {m.pembalas}</span> : null}
                    <div>{m.balasan}</div>
                </div>
            ) : null}

            {m.status === 'diadopsi' && !m.balasan ? (
                <p className="text-chart-5 mt-1 flex items-center gap-1 text-sm">
                    <HugeiconsIcon
                        icon={CheckmarkCircle02Icon}
                        strokeWidth={1.5}
                        className="size-3.5"
                        aria-hidden="true"
                    />
                    Diadopsi menjadi bahan revisi dokumen ini.
                </p>
            ) : null}

            {/* Menutup masukan TANPA revisi; yang diadopsi ditutup otomatis saat
                Ajukan Revisi. */}
            {bolehMembalas && (m.status === 'baru' || m.status === 'dibaca') ? (
                <form onSubmit={balas} className="mt-2 flex gap-2">
                    <Input
                        name="balasan"
                        required
                        maxLength={2000}
                        placeholder="Balasan untuk pengirim (mis. sudah sesuai standar terbaru)..."
                        value={form.data.balasan}
                        onChange={(e) => form.setData('balasan', e.target.value)}
                    />
                    <Button type="submit" variant="outline" size="sm" disabled={form.processing} className="shrink-0">
                        <HugeiconsIcon icon={ReplyIcon} strokeWidth={1.5} className="size-4" />
                        Balas &amp; Tutup
                    </Button>
                    <ConfirmDialog
                        judul="Balas & Tutup?"
                        pesan={`Tutup masukan ${m.nomor} tanpa revisi dan kirim balasan ke pengirimnya?`}
                        tombolYa="Ya, kirim balasan"
                        buka={konfirmasi}
                        onUbahBuka={setKonfirmasi}
                        onKonfirmasi={kirim}
                    />
                </form>
            ) : null}
        </div>
    );
}
