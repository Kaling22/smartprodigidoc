/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/ImageUpload.tsx` (REDESAIN-UI-V2 T2).
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
import { Cancel01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useState } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Input } from '@/components/ui-maia/input';
import { unggahGambar } from '@/lib/unggah';
import type { BidangSchema } from '@/types/wizard';

import { useWizard } from './konteks';

/**
 * Kolom `image` — pengganti cabang `image` di
 * `documents/fields/_repeatable_group.blade.php`.
 *
 * Gambar diunggah LANGSUNG saat dipilih (AJAX) → jalurnya disimpan sebagai
 * nilai kolom. Dengan begitu Preview cukup memuat ulang iframe (tanpa reload
 * halaman) dan foto tak hilang saat pindah langkah.
 *
 * SENGAJA tidak memperkecil gambar lebih dulu, berbeda dengan `RichText`: yang
 * diunggah di sini adalah gambar flowchart/lampiran yang memang dipilih sadar
 * dari berkas, bukan tangkapan layar 4K yang ditempel sekilas. Batas 2MB milik
 * server ditegakkan apa adanya, dan pesannya sampai ke pengguna.
 */
export function ImageUpload({
    value,
    onChange,
    sectionKey,
    field,
    nama,
}: {
    value: string;
    onChange: (jalur: string) => void;
    sectionKey: string;
    field: BidangSchema;
    nama: string;
}) {
    const { documentId } = useWizard();

    const [mengunggah, setMengunggah] = useState(false);
    const [galat, setGalat] = useState('');

    const adaGambar = Boolean(value) && String(value).startsWith('lampiran/');

    const pilih = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const berkas = e.target.files?.[0];
        if (!berkas) return;

        setGalat('');
        setMengunggah(true);

        const hasil = await unggahGambar(route('documents.uploadAttachment', documentId), berkas, sectionKey);

        setMengunggah(false);
        e.target.value = '';

        if (hasil.path) onChange(hasil.path);
        else setGalat(hasil.galat ?? 'Gagal mengunggah gambar.');
    };

    return (
        <div>
            <input type="hidden" name={nama} value={value ?? ''} readOnly />

            {adaGambar ? (
                <div className="mb-2 flex items-start gap-2">
                    <img src={`/storage/${value}`} alt="" className="max-h-40 rounded-lg border" />
                    <Button type="button" variant="outline" size="sm" onClick={() => onChange('')}>
                        <HugeiconsIcon icon={Cancel01Icon} strokeWidth={1.5} />
                        Hapus gambar
                    </Button>
                </div>
            ) : null}

            <Input
                type="file"
                accept={field.image_accept ?? 'image/jpeg,image/png'}
                disabled={mengunggah}
                onChange={pilih}
            />

            {mengunggah ? (
                <p className="mt-1 text-xs text-primary">Mengunggah…</p>
            ) : (
                <p className="mt-1 text-xs text-muted-foreground">
                    Format JPG/PNG, maks {field.image_max_mb ?? 5}MB. Foto akan muncul di preview &amp; PDF.
                </p>
            )}
            {galat ? <p className="mt-1 text-xs text-destructive">{galat}</p> : null}
        </div>
    );
}
