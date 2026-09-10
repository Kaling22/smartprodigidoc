/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/UserPicker.tsx` (REDESAIN-UI-V2 T2).
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
import { Alert02Icon, Cancel01Icon, PlusSignIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Button } from '@/components/ui-maia/button';
import { Label } from '@/components/ui-maia/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui-maia/select';
import type { Kandidat, Ketersediaan } from '@/types/wizard';

import { PapanKetersediaan } from './PapanKetersediaan';
import { Bantuan, useWizard, type PropsField } from './konteks';

/** Radix menolak `value=""`, jadi "belum memilih" butuh nilai penanda. */
const KOSONG = '__kosong__';

/**
 * Pemilih pengguna — pengganti `documents/fields/_user_picker.blade.php`.
 *
 * Bercabang tiga, dan cabangnya ditentukan SERVER:
 *
 *  1. **Papan ketersediaan** — bila kunci seksinya ada di props `papan`
 *     (`ReviewerAvailability::PAPAN`). Berlaku untuk peninjau & penyetuju:
 *     jadwal orangnya mengubah keputusan, jadi jadwal itu harus terlihat.
 *  2. **Dropdown berulang** — `multiple`, hari ini hanya `pembuat_tambahan`.
 *  3. **Dropdown tunggal** — cabang cadangan bagi seksi user_picker lain yang
 *     kelak ditambahkan lewat schema.
 *
 * Kandidatnya datang JADI dari `DocumentWizard::userPickerCandidates()`. NOL
 * penyaringan di berkas ini (pakem P5): daftar yang tampil dan daftar yang boleh
 * tersimpan mustahil berbeda karena keduanya satu sumber.
 */
export function UserPicker(props: PropsField) {
    const { section } = props;
    const { candidates, ketersediaan, papan } = useWizard();

    const multiple = section.multiple ?? false;

    if (section.key in papan && !multiple) {
        return <PapanKetersediaan {...props} />;
    }

    const options = candidates[section.key] ?? [];
    const jadwal = ketersediaan[section.key] ?? {};

    return multiple ? (
        <Berulang {...props} options={options} jadwal={jadwal} />
    ) : (
        <Tunggal {...props} options={options} />
    );
}

/**
 * `pembuat_tambahan` — beberapa baris dropdown.
 *
 * Kandidat yang sedang cuti sudah DIBUANG dari daftar oleh
 * `DocumentWizard::coAuthorCandidates()`. Yang masih tersisa di sini hanyalah
 * orang yang SUDAH tercatat pada dokumen ini lalu mengajukan cuti — ia sengaja
 * ditahan supaya namanya tak lenyap dari halaman pengesahan. Keterangannya
 * ditulis agar GL tahu sebabnya, bukan mengira daftarnya keliru.
 */
function Berulang({
    section,
    value,
    onChange,
    options,
    jadwal,
}: PropsField & { options: Kandidat[]; jadwal: Record<number, Ketersediaan> }) {
    const required = section.required ?? false;
    const terpilih = Array.isArray(value) ? (value as (number | string)[]) : [];
    const items = terpilih.length ? terpilih : [''];

    const ubah = (i: number, id: string) => onChange(items.map((v, n) => (n === i ? (id === KOSONG ? '' : Number(id)) : v)));

    const hapus = (i: number) => {
        const sisa = items.filter((_, n) => n !== i);
        onChange(sisa.length ? sisa : ['']);
    };

    return (
        <div className="mb-6">
            <Label className="mb-1.5 font-semibold">
                {section.label ?? section.key}
                {required ? <span className="text-destructive"> *</span> : null}
            </Label>
            <Bantuan teks={section.hint} />

            {items.map((sel, i) => (
                // eslint-disable-next-line react/no-array-index-key
                <div key={i} className="mb-2 flex gap-2" data-pp-pilih-kotak>
                    <Select value={sel ? String(sel) : KOSONG} onValueChange={(v) => ubah(i, v)}>
                        <SelectTrigger className="h-8 flex-1">
                            <SelectValue placeholder="— Pilih —" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={KOSONG}>— Pilih —</SelectItem>
                            {options.map((o) => (
                                <SelectItem key={o.id} value={String(o.id)}>
                                    {labelKandidat(o, jadwal[o.id])}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {/* Pembuat tambahan TIDAK wajib → `data-optional`, agar tak
                        memicu "kolom belum diisi" saat dibiarkan kosong. */}
                    <input
                        type="hidden"
                        data-pp-pilih
                        {...(required ? {} : { 'data-optional': '' })}
                        name={`sections[${section.key}][]`}
                        value={sel ? String(sel) : ''}
                        readOnly
                    />
                    <Button type="button" variant="outline" size="icon" className="size-8 shrink-0" onClick={() => hapus(i)}>
                        <HugeiconsIcon icon={Cancel01Icon} strokeWidth={1.5} />
                    </Button>
                </div>
            ))}

            <Button type="button" variant="outline" size="sm" onClick={() => onChange([...items, ''])}>
                <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                Tambah Pembuat
            </Button>
        </div>
    );
}

function Tunggal({ section, value, onChange, options }: PropsField & { options: Kandidat[] }) {
    const required = section.required ?? false;
    const sel = value === null || value === undefined || value === '' ? '' : String(value);

    return (
        <div className="mb-6" data-pp-pilih-kotak>
            <Label className="mb-1.5 font-semibold">
                {section.label ?? section.key}
                {required ? <span className="text-destructive"> *</span> : null}
            </Label>
            <Bantuan teks={section.hint} />

            <Select value={sel || KOSONG} onValueChange={(v) => onChange(v === KOSONG ? '' : Number(v))}>
                <SelectTrigger className="h-8 w-full">
                    <SelectValue placeholder="— Pilih —" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={KOSONG}>— Pilih —</SelectItem>
                    {options.map((o) => (
                        <SelectItem key={o.id} value={String(o.id)}>
                            {labelKandidat(o)}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <input
                type="hidden"
                data-pp-pilih
                {...(required ? {} : { 'data-optional': '' })}
                name={`sections[${section.key}]`}
                value={sel}
                readOnly
            />

            {options.length === 0 ? (
                <p className="mt-1 flex items-center gap-1.5 text-xs text-chart-3">
                    <HugeiconsIcon icon={Alert02Icon} strokeWidth={1.5} className="size-3.5"  />
                    Belum ada kandidat untuk peran ini.
                </p>
            ) : null}
        </div>
    );
}

/** "Nama — NRP — DEPT · sedang cuti" — persis susunan Blade lama. */
function labelKandidat(o: Kandidat, a?: Ketersediaan): string {
    const off = a?.off ? a.jenis : null;

    return `${o.nama} — ${o.nrp} — ${o.dept}${off ? ` · sedang ${off.toLowerCase()}` : ''}`;
}
