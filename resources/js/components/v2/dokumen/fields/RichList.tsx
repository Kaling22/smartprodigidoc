/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/RichList.tsx` (REDESAIN-UI-V2 T2).
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
import { Cancel01Icon, PlusSignIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { CatatanItem } from '@/components/v2/CatatanItem';
import { Button } from '@/components/ui-maia/button';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';

import { Bantuan, type PropsField, useWizard } from './konteks';

/**
 * Daftar butir bernomor — pengganti `documents/fields/_rich_list.blade.php`.
 *
 * Melayani `rich_list` DAN `reference_picker`; bedanya cuma `suggestions`, yang
 * mengisi `<datalist>` bawaan peramban. Tak ada pustaka autocomplete: daftar
 * saran statis memang sudah jadi kemampuan HTML.
 *
 * Baris kosong TIDAK dibuang di sini — `DocumentWizard::cleanValue()` yang
 * membuangnya saat simpan. Membersihkan dua kali berarti dua aturan yang bisa
 * menyimpang, dan yang di server-lah yang menentukan isi basis data.
 */
export function RichList({ section, value, onChange }: PropsField) {
    const catatan = useWizard().catatanItem[section.key];
    const prefix = section.auto_number ?? '';
    const items = (Array.isArray(value) ? (value as string[]) : []).length
        ? (value as string[])
        : [''];
    const idDatalist = `dl-${section.key}`;

    const ubah = (i: number, teks: string) => onChange(items.map((v, n) => (n === i ? teks : v)));

    const hapus = (i: number) => {
        const sisa = items.filter((_, n) => n !== i);
        onChange(sisa.length ? sisa : ['']);
    };

    return (
        <div className="mb-6">
            <Label className="mb-1.5 font-semibold">{section.label ?? section.key}</Label>
            <Bantuan teks={section.help} />

            {section.suggestions ? (
                <datalist id={idDatalist}>
                    {section.suggestions.map((s) => (
                        <option key={s} value={s} />
                    ))}
                </datalist>
            ) : null}

            {items.map((item, i) => (
                // Kunci INDEKS di sini aman — berbeda dengan RepeatableGroup:
                // barisnya cuma satu <input> tanpa keadaan internal, jadi
                // pemakaian ulang DOM tak bisa membuat isinya tertukar.
                // eslint-disable-next-line react/no-array-index-key
                <div key={i} className="mb-2">
                    <div className="flex gap-2">
                        {prefix ? (
                            <span className="flex h-8 shrink-0 items-center rounded-lg border border-input bg-muted px-2.5 font-mono text-sm">
                                {prefix}
                                {i + 1}
                            </span>
                        ) : null}
                        <Input
                            className="flex-1"
                            name={`sections[${section.key}][]`}
                            value={item ?? ''}
                            list={section.suggestions ? idDatalist : undefined}
                            placeholder={section.suggestions ? 'Masukkan referensi...' : 'Masukkan poin...'}
                            onChange={(e) => ubah(i, e.target.value)}
                        />
                        <Button type="button" variant="outline" size="icon" className="size-8 shrink-0" onClick={() => hapus(i)} title="Hapus">
                            <HugeiconsIcon icon={Cancel01Icon} strokeWidth={1.5} />
                        </Button>
                    </div>

                    <CatatanItem daftar={catatan?.[String(i)]} nilaiKini={item ?? ''} />
                </div>
            ))}

            <Button type="button" variant="outline" size="sm" onClick={() => onChange([...items, ''])}>
                <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                Tambah Poin
            </Button>
        </div>
    );
}
