/**
 * KEMBARAN MAIA dari `resources/js/components/tinjau/DialogAlihkan.tsx` (REDESAIN-UI-V2 T2).
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
import { useForm } from '@inertiajs/react';
import { Exchange01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useState } from 'react';

import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { PapanPilihPeninjau } from '@/components/v2/dokumen/fields/PapanKetersediaan';
import { Button } from '@/components/ui-maia/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui-maia/dialog';
import { Label } from '@/components/ui-maia/label';
import { Textarea } from '@/components/ui-maia/textarea';
import type { Kandidat, Ketersediaan } from '@/types/wizard';

/**
 * Alihkan Tugas Peninjauan JSA — pengganti `review/_modal-alihkan.blade.php`
 * (PLAN-REVISI-v6 butir 3).
 *
 * GL SHE yang mejanya penuh menyerahkan satu JSA ke peninjau lain yang
 * sama-sama berwenang. Dokumennya LANGSUNG berpindah (keputusan pemilik B1) —
 * tak ada persetujuan yang perlu ditunggu, jadi tak ada pula status baru.
 *
 * Pemilihannya memakai PAPAN KETERSEDIAAN yang sama dengan wizard, bukan
 * dropdown: yang dicari GL di sini justru "siapa yang paling lapang", dan itu
 * pertanyaan pembandingan — persis yang tak bisa dijawab dropdown. Efek
 * sampingnya gratis: peninjau yang sedang off ikut terkunci tanpa satu baris
 * aturan tambahan.
 *
 * Konfirmasi MENYUSUL sesudah formulirnya sah (pola `ConfirmDialog`
 * dikendalikan sejak Fase 7): tombol Alihkan tetap `type="submit"` sehingga
 * `required` peramban tetap bekerja pada kotak alasan.
 */
export function DialogAlihkan({
    buka,
    onUbahBuka,
    nomor,
    url,
    kandidat,
    ketersediaan,
    ambang,
}: {
    buka: boolean;
    onUbahBuka: (buka: boolean) => void;
    nomor: string;
    url: string;
    kandidat: Kandidat[];
    ketersediaan: Record<number, Ketersediaan>;
    ambang: { padat: number; sibuk: number; jenis: string[] };
}) {
    const [konfirmasi, setKonfirmasi] = useState(false);
    const form = useForm<{ tujuan: number | null; alasan: string }>({ tujuan: null, alasan: '' });

    const kirim = () =>
        form.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                onUbahBuka(false);
                form.reset();
            },
        });

    return (
        <>
            <Dialog open={buka} onOpenChange={onUbahBuka}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-1.5">
                            <HugeiconsIcon icon={Exchange01Icon} strokeWidth={1.5} className="size-4"  />
                            Alihkan Peninjauan — <span className="font-mono">{nomor}</span>
                        </DialogTitle>
                        <DialogDescription>
                            Peninjauan langsung berpindah ke peninjau yang dipilih dan tidak lagi muncul
                            di antrian Anda.
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        className="max-h-[65vh] overflow-y-auto px-1"
                        onSubmit={(e) => {
                            e.preventDefault();
                            setKonfirmasi(true);
                        }}
                        id="form-alihkan"
                    >
                        <PapanPilihPeninjau
                            section={{
                                key: 'tujuan',
                                type: 'user_picker',
                                label: 'Alihkan kepada',
                                required: true,
                                hint: 'Kandidat yang sama dengan saat dokumen ini ditugaskan. Angka di kanan = dokumen yang sedang ia tinjau.',
                            }}
                            value={form.data.tujuan}
                            onChange={(v) => form.setData('tujuan', Number(v))}
                            nama="tujuan"
                            options={kandidat}
                            jadwal={ketersediaan}
                            memblokir
                            ambang={ambang}
                        />
                        {form.errors.tujuan ? (
                            <p className="text-destructive mb-3 text-sm">{form.errors.tujuan}</p>
                        ) : null}

                        <Label htmlFor="alasan-alih" className="mb-1.5 font-semibold">
                            Alasan pengalihan <span className="text-destructive">*</span>
                        </Label>
                        <Textarea
                            id="alasan-alih"
                            rows={3}
                            maxLength={2000}
                            required
                            aria-invalid={Boolean(form.errors.alasan)}
                            placeholder="mis. Sedang menangani 6 JSA lain minggu ini."
                            value={form.data.alasan}
                            onChange={(e) => form.setData('alasan', e.target.value)}
                        />
                        {form.errors.alasan ? (
                            <p className="text-destructive text-sm">{form.errors.alasan}</p>
                        ) : null}
                        <p className="text-muted-foreground mt-1 text-xs">
                            Dikirim ke peninjau tujuan dan tercatat di audit log.
                        </p>
                    </form>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onUbahBuka(false)}>
                            Batal
                        </Button>
                        <Button type="submit" form="form-alihkan" disabled={form.processing}>
                            <HugeiconsIcon icon={Exchange01Icon} strokeWidth={1.5} />
                            Alihkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                buka={konfirmasi}
                onUbahBuka={setKonfirmasi}
                judul="Alihkan peninjauan?"
                pesan={`Peninjauan ${nomor} langsung berpindah ke peninjau yang dipilih dan tidak lagi muncul di antrian Anda.`}
                tombolYa="Ya, alihkan"
                onKonfirmasi={kirim}
            />
        </>
    );
}
