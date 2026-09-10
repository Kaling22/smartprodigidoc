/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/RepeatableGroup.tsx` (REDESAIN-UI-V2 T2).
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
import { Delete02Icon, PlusSignIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useRef } from 'react';

import { CatatanItem } from '@/components/v2/CatatanItem';
import { Button } from '@/components/ui-maia/button';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { Switch } from '@/components/ui-maia/switch';
import { Textarea } from '@/components/ui-maia/textarea';
import { cn } from '@/lib/utils';
import type { BidangSchema } from '@/types/wizard';

import { DocumentPicker } from './DocumentPicker';
import { ImageUpload } from './ImageUpload';
import { RichText } from './RichText';
import { Bantuan, useWizard, type PropsField } from './konteks';

type Baris = Record<string, string>;

const barisKosong = (fields: BidangSchema[]): Baris =>
    Object.fromEntries(fields.map((f) => [f.key, '']));

/**
 * Blok isian BERULANG — pengganti
 * `documents/fields/_repeatable_group.blade.php` (11 KB, partial terbesar).
 *
 * Melayani Aktivitas (sub_judul + deskripsi + PIC), Flowchart (judul + gambar +
 * keterangan), dan Lampiran (dokumen + keterangan). Kolomnya datang dari
 * `group_fields` di schema — komponen ini tak tahu satu pun nama kolom, dan
 * itulah pakem P1: kolom baru yang ditambahkan lewat SQL muncul sendiri.
 *
 * DUA hal yang mudah hilang saat dipindah dan wajib bertahan:
 *
 *  1. **Bab bersakelar yang DIMATIKAN tetap dirender**, hanya diredupkan dan
 *     dibuat `inert`. Sengaja BUKAN `disabled`: kolom disabled tak ikut
 *     terkirim, jadi sekali disimpan isian yang sudah diketik akan hilang.
 *     `inert` tetap mengirimkannya — tak bisa diklik, tak bisa di-tab, tak
 *     dibaca pembaca layar, tapi isinya utuh.
 *  2. **Kunci baris harus STABIL, bukan indeks.** Menghapus baris ke-1 dengan
 *     kunci indeks membuat React memakai ulang DOM baris berikutnya; untuk
 *     `<input>` biasa itu tak apa-apa, tapi Quill memegang DOM-nya sendiri dan
 *     instansnya akan tetap menampilkan tulisan baris yang salah — penyusun
 *     kehilangan tulisannya TANPA peringatan.
 */
export function RepeatableGroup({ section, value, onChange }: PropsField) {
    const { labelMati, babOn, setBabOn, catatanItem } = useWizard();
    const catatan = catatanItem[section.key];

    const fields = section.group_fields ?? section.fields ?? [];
    const minItems = section.min_groups ?? section.min_items ?? 1;
    const tersimpan = (Array.isArray(value) ? (value as Baris[]) : []).filter((r) => r && typeof r === 'object');
    /*
    | Selalu ada minimal satu baris selama `min_groups > 0`.
    |
    | Komponen Alpine `repeatable` menyediakannya HANYA saat halaman dimuat,
    | sehingga menghapus baris terakhir menyisakan bab tanpa satu pun kolom.
    | Ketiga saudaranya (`richList`, `userPicker`, `jsaAnalysis`) justru
    | mengembalikan baris kosong pada `remove()` — jadi yang ditiru di sini
    | adalah yang tiga, bukan yang satu. Nol akibat pada data: baris yang
    | seluruh kolomnya kosong tetap dibuang `DocumentWizard::cleanValue()`.
    */
    const rows: Baris[] = tersimpan.length ? tersimpan : minItems > 0 ? [barisKosong(fields)] : [];

    // Kunci stabil per baris. Hidup di komponen saja dan tak pernah masuk ke
    // nilai seksi, jadi tak ada nilai asing yang ikut tersimpan ke value_json.
    const uids = useRef<number[]>([]);
    const penghitung = useRef(0);
    while (uids.current.length < rows.length) uids.current.push(++penghitung.current);
    if (uids.current.length > rows.length) uids.current.length = rows.length;

    /*
    | Bab yang TIDAK WAJIB (`required: false`, mis. FLOWCHART & LAMPIRAN pada
    | SOP). Seluruh kolomnya diberi `data-optional` supaya validasi wajib
    | melewatinya — kalau tidak, baris STANDBY yang sengaja disediakan kosong
    | justru MENGHALANGI tombol Kirim, dan penyusun terpaksa mengisi bab yang
    | memang tak dipakainya. Kekosongannya tetap diingatkan saat Kirim lewat
    | `warn_if_empty`; peringatan, bukan penghalang.
    */
    const wajib = section.required ?? true;

    /*
    | BAB OPSIONAL (`toggle_key`, mis. FLOWCHART) & dua varian nomornya.
    |
    | `sakelar` → bab INI punya sakelarnya sendiri.
    | `babLain` → label/prefix bab ini saat bab opsional dimatikan. Berlaku juga
    |             untuk bab yang IKUT bergeser (AKTIVITAS, LAMPIRAN), bukan cuma
    |             bab yang bersakelar.
    |
    | Nomornya datang dari PHP, sudah jadi, dua-duanya. React hanya MEMILIH —
    | tak ada satu pun angka bab yang dihitung di peramban.
    */
    const sakelar = section.toggle_key ?? null;
    const babLain = labelMati[section.key] ?? null;
    const labelMatiBab = babLain?.label ?? null;
    const prefix = section.auto_number ?? '';
    const prefixMati = babLain?.auto_number ?? prefix;
    const labelBeda = labelMatiBab !== null && labelMatiBab !== (section.label ?? null);

    const babMati = Boolean(sakelar) && !babOn;
    const nomor = (i: number) => (babOn ? prefix : prefixMati) + (i + 1);

    /*
    | Menulis BEBERAPA kolom satu baris sekaligus.
    |
    | Wajib satu panggilan, bukan dua `ubahBaris` berturut-turut: keduanya
    | menutup `rows` yang SAMA, jadi yang kedua menimpa hasil yang pertama dan
    | satu kolomnya diam-diam kosong. Dipakai `judul_ke` (butir 6), yang mengisi
    | nomor dan judul ke dua kolom berbeda dalam satu pemilihan.
    */
    const tulisBaris = (i: number, tambalan: Baris) =>
        onChange(rows.map((r, n) => (n === i ? { ...r, ...tambalan } : r)));

    const ubahBaris = (i: number, fkey: string, teks: string) => tulisBaris(i, { [fkey]: teks });

    const hapus = (i: number) => {
        uids.current.splice(i, 1);
        onChange(rows.filter((_, n) => n !== i));
    };

    return (
        <div className="mb-6">
            {/* Kepala bab: judul di kiri, sakelar di kanan. Sakelarnya sengaja
                DI LUAR pembungkus isian di bawah — pembungkus itu dimatikan saat
                sakelarnya mati, dan sakelar yang ikut mati tak bisa dinyalakan
                lagi. */}
            <div className="mb-2 flex items-center justify-between gap-3">
                <Label className="font-semibold">
                    {labelBeda && !babOn ? labelMatiBab : (section.label ?? section.key)}
                </Label>

                {sakelar ? (
                    <div className="flex shrink-0 items-center gap-2">
                        <Switch id={`sw-${section.key}`} checked={babOn} onCheckedChange={setBabOn} />
                        <Label htmlFor={`sw-${section.key}`} className="text-sm">
                            {section.toggle_label ?? 'Gunakan bab ini'}
                        </Label>
                        {/* Nilainya ikut dikirim dari state Edit.tsx; input ini
                            ada supaya bentuk DOM-nya tetap sama dengan Blade —
                            berguna saat menelusuri kiriman lewat DevTools. */}
                        <input type="hidden" name={`sections[${sakelar}]`} value={babOn ? '1' : '0'} readOnly />
                    </div>
                ) : null}
            </div>

            <Bantuan teks={section.help} />

            {/* `data-pp-warn-bab` = bab tak wajib yang kekosongannya SELURUHNYA
                tetap diingatkan saat Kirim. Peringatan per-kolom di dalamnya
                hanya menyala bila barisnya sudah terisi sebagian, jadi tanpa
                penanda ini bab yang dilewatkan bulat-bulat akan lolos tanpa
                sepatah pun peringatan. */}
            <div
                className={cn('transition-[opacity,filter]', babMati && 'cursor-not-allowed opacity-50 grayscale')}
                inert={babMati || undefined}
                {...(!wajib && section.warn_if_empty
                    ? { 'data-pp-warn-bab': section.label ?? section.key }
                    : {})}
            >
                {rows.map((row, i) => (
                    <div key={uids.current[i]} data-pp-baris className="relative mb-3 rounded-lg border bg-muted/40 p-3">
                        <div className="mb-2 flex items-center justify-between">
                            {prefix ? (
                                <span className="rounded-md bg-primary px-2 py-0.5 font-mono text-xs text-primary-foreground">
                                    {nomor(i)}
                                </span>
                            ) : (
                                <span />
                            )}
                            <Button type="button" variant="outline" size="sm" onClick={() => hapus(i)}>
                                <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} />
                                Hapus
                            </Button>
                        </div>

                        {fields.map((f) => (
                            <Kolom
                                key={f.key}
                                field={f}
                                sectionKey={section.key}
                                nama={`sections[${section.key}][${i}][${f.key}]`}
                                opsional={!wajib}
                                peringatan={f.warn_if_empty ? (f.label ?? f.key) : null}
                                value={row[f.key] ?? ''}
                                onChange={(teks) => ubahBaris(i, f.key, teks)}
                                onPilih={
                                    f.judul_ke
                                        ? (nomor, judul) => tulisBaris(i, { [f.key]: nomor, [f.judul_ke!]: judul })
                                        : undefined
                                }
                            />
                        ))}

                        {/* Seluruh kolom baris digabung sebagai pembanding: server
                            mengutip kolom PERTAMA yang terisi, dan baris yang
                            kolom pertamanya baru dikosongkan bukan berarti
                            barisnya bergeser. */}
                        <CatatanItem daftar={catatan?.[String(i)]} nilaiKini={Object.values(row).join(' ')} />
                    </div>
                ))}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onChange([...rows, barisKosong(fields)])}
                >
                    <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                    {section.add_button_label ?? '+ Tambah'}
                </Button>
            </div>
        </div>
    );
}

/** Satu kolom di dalam satu baris. Tipenya datang dari schema, bukan dari sini. */
function Kolom({
    field,
    sectionKey,
    nama,
    opsional,
    peringatan,
    value,
    onChange,
    onPilih,
}: {
    field: BidangSchema;
    sectionKey: string;
    nama: string;
    opsional: boolean;
    peringatan: string | null;
    value: string;
    onChange: (teks: string) => void;
    /** Hanya terisi untuk `document_picker` ber-`judul_ke` — lihat DocumentPicker. */
    onPilih?: (nomor: string, judul: string) => void;
}) {
    const tanda = {
        ...(opsional ? { 'data-optional': '' } : {}),
        ...(peringatan ? { 'data-pp-warn': peringatan } : {}),
    };

    return (
        <div className="mb-2">
            <Label className="mb-1 text-sm font-semibold">{field.label ?? field.key}</Label>

            {field.type === 'textarea' ? (
                <Textarea
                    rows={3}
                    {...tanda}
                    name={nama}
                    value={value}
                    placeholder={field.placeholder ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            ) : field.type === 'document_picker' ? (
                <DocumentPicker
                    value={value}
                    onChange={onChange}
                    nama={nama}
                    opsional={opsional}
                    peringatan={peringatan}
                    placeholder={field.placeholder}
                    onPilih={onPilih}
                />
            ) : field.type === 'image' ? (
                <ImageUpload value={value} onChange={onChange} sectionKey={sectionKey} field={field} nama={nama} />
            ) : field.type === 'rich_text' ? (
                <RichText
                    value={value}
                    onChange={onChange}
                    sectionKey={sectionKey}
                    field={field}
                    nama={nama}
                    opsional={opsional}
                    peringatan={peringatan}
                />
            ) : (
                <Input
                    {...tanda}
                    name={nama}
                    value={value}
                    placeholder={field.placeholder ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}
        </div>
    );
}
