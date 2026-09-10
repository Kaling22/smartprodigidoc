/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/Text.tsx` (REDESAIN-UI-V2 T2).
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
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { Textarea } from '@/components/ui-maia/textarea';

import { Bantuan, type PropsField } from './konteks';

/**
 * Seksi bernilai TUNGGAL — pengganti `documents/fields/_text.blade.php`.
 *
 * Melayani tiga tipe schema sekaligus: `text`, `textarea`, dan `date`. Tanggal
 * WAJIB memakai widget tanggal peramban, bukan diketik — itu ketetapan yang
 * sudah ada sejak Blade, dan `<input type="date">` sudah menyediakannya tanpa
 * satu pun pustaka.
 *
 * Tak ada penanda `data-optional` di sini, persis seperti Blade lama: seksi
 * bernilai tunggal memang selalu dituntut terisi.
 */
export function Text({ section, value, onChange }: PropsField) {
    // Nilai: isi tersimpan; jika kosong pakai `default` schema (mis. metadata
    // form JSA).
    const val = typeof value === 'string' && value !== '' ? value : (section.default ?? '');
    const nama = `sections[${section.key}]`;
    const tipe = section.type ?? 'text';

    return (
        <div className="mb-6">
            <Label className="mb-1.5 font-semibold" htmlFor={nama}>
                {section.label ?? section.key}
            </Label>
            <Bantuan teks={section.help} />

            {tipe === 'textarea' ? (
                <Textarea
                    id={nama}
                    name={nama}
                    rows={4}
                    value={val}
                    placeholder={section.placeholder ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            ) : (
                <Input
                    id={nama}
                    name={nama}
                    type={tipe === 'date' ? 'date' : 'text'}
                    value={val}
                    placeholder={section.placeholder ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}
        </div>
    );
}
