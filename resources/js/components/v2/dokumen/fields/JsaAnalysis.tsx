/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/JsaAnalysis.tsx` (REDESAIN-UI-V2 T2).
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
import { Alert02Icon, Cancel01Icon, Delete02Icon, MinusSignIcon, PlusSignIcon, ShieldCheckIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader } from '@/components/ui-maia/card';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { Textarea } from '@/components/ui-maia/textarea';
import { CatatanItem } from '@/components/v2/CatatanItem';

import { type PropsField, useWizard } from './konteks';

/** Satu tingkat di dalam analisa JSA. */
interface Bahaya {
    risiko: string;
    pengendalian: string[];
}

interface Langkah {
    langkah: string;
    bahaya: Bahaya[];
}

const bahayaBaru = (): Bahaya => ({ risiko: '', pengendalian: [''] });
const langkahBaru = (): Langkah => ({ langkah: '', bahaya: [bahayaBaru()] });

/**
 * Analisa bahaya JSA — pengganti `documents/fields/_jsa_analysis.blade.php`.
 *
 * Bersarang TIGA lapis: Langkah Kerja → Bahaya & Risiko → Tindakan
 * Pengendalian. Bentuk itu bukan pilihan tampilan melainkan bentuk formulir JSA
 * yang sesungguhnya, dan jalur cetak (`JsaPrintLayout`) membacanya persis
 * begitu.
 *
 * Baris kosong TIDAK dibuang di sini — `DocumentWizard::cleanValue()` punya
 * aturan tersendiri untuk `jsa_analysis` (bahaya tanpa risiko DAN tanpa kendali
 * dibuang; langkah tanpa uraian DAN tanpa bahaya dibuang). Menirunya di
 * peramban berarti dua aturan yang bisa menyimpang.
 */
export function JsaAnalysis({ section, value, onChange }: PropsField) {
    /*
    | Catatan JSA menempel di TIGA tingkat, dan bentuk refnya (`L0`, `L0-B1`,
    | `L0-B1-P2`) sama persis dengan yang ditulis layar tinjau & aplikasi HP —
    | lihat `ReviewDecision::refPengendalian()`. Jangan menyusun ref dengan pola
    | lain di sini: catatan yang ditulis dari HP menunjuk item yang sama.
    */
    const catatan = useWizard().catatanItem[section.key];
    const mentah = Array.isArray(value) ? (value as Partial<Langkah>[]) : [];

    // Normalisasi persis seperti komponen Alpine `jsaAnalysis`: tiap tingkat
    // dijamin punya minimal satu anak, supaya formulirnya tak pernah tampil
    // sebagai kotak kosong tanpa tombol.
    const steps: Langkah[] = mentah.length
        ? mentah.map((s) => ({
              langkah: s.langkah ?? '',
              bahaya: (Array.isArray(s.bahaya) && s.bahaya.length ? s.bahaya : [bahayaBaru()]).map((b) => ({
                  risiko: b.risiko ?? '',
                  pengendalian: Array.isArray(b.pengendalian) && b.pengendalian.length ? b.pengendalian : [''],
              })),
          }))
        : [langkahBaru()];

    const ubahLangkah = (li: number, patch: Partial<Langkah>) =>
        onChange(steps.map((s, n) => (n === li ? { ...s, ...patch } : s)));

    const ubahBahaya = (li: number, bi: number, patch: Partial<Bahaya>) =>
        ubahLangkah(li, { bahaya: steps[li].bahaya.map((b, n) => (n === bi ? { ...b, ...patch } : b)) });

    const nama = (bagian: string) => 'sections[' + section.key + ']' + bagian;

    return (
        <div className="mb-6">
            <Label className="mb-1.5 font-semibold">{section.label ?? section.key}</Label>
            {section.help ? (
                <p className="mb-3 rounded-lg bg-muted px-3 py-2 text-xs text-muted-foreground">{section.help}</p>
            ) : null}

            {steps.map((step, li) => (
                // eslint-disable-next-line react/no-array-index-key
                <Card key={li} className="mb-3 gap-0 py-0">
                    <CardHeader className="flex items-center justify-between border-b px-4 py-3">
                        <span className="font-semibold">Langkah Kerja #{li + 1}</span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                const sisa = steps.filter((_, n) => n !== li);
                                onChange(sisa.length ? sisa : [langkahBaru()]);
                            }}
                        >
                            <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} />
                            Hapus Langkah
                        </Button>
                    </CardHeader>

                    <CardContent className="p-4">
                        <Label className="mb-1.5 text-sm font-semibold">Uraian Langkah Pekerjaan</Label>
                        <Textarea
                            className="mb-3"
                            rows={2}
                            name={nama('[' + li + '][langkah]')}
                            value={step.langkah}
                            placeholder="mis. Persiapan alat dan pengecekan area kerja"
                            onChange={(e) => ubahLangkah(li, { langkah: e.target.value })}
                        />

                        <CatatanItem daftar={catatan?.['L' + li]} nilaiKini={step.langkah} />

                        {step.bahaya.map((bahaya, bi) => (
                            // eslint-disable-next-line react/no-array-index-key
                            <div key={bi} className="mb-2 rounded-lg border bg-muted/40 p-3">
                                <Label className="mb-1 flex items-center gap-1.5 text-sm font-semibold text-destructive">
                                    <HugeiconsIcon icon={Alert02Icon} strokeWidth={1.5} className="size-3.5"  />
                                    Bahaya &amp; Risiko
                                </Label>
                                <div className="mb-2 flex gap-2">
                                    <Input
                                        className="flex-1"
                                        name={nama('[' + li + '][bahaya][' + bi + '][risiko]')}
                                        value={bahaya.risiko}
                                        placeholder="Potensi bahaya / risiko yang mungkin timbul"
                                        onChange={(e) => ubahBahaya(li, bi, { risiko: e.target.value })}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="size-8 shrink-0"
                                        title="Hapus bahaya ini"
                                        onClick={() => {
                                            const sisa = step.bahaya.filter((_, n) => n !== bi);
                                            ubahLangkah(li, { bahaya: sisa.length ? sisa : [bahayaBaru()] });
                                        }}
                                    >
                                        <HugeiconsIcon icon={Cancel01Icon} strokeWidth={1.5} />
                                    </Button>
                                </div>

                                <CatatanItem daftar={catatan?.['L' + li + '-B' + bi]} nilaiKini={bahaya.risiko} />

                                <Label className="mb-1 flex items-center gap-1.5 text-sm font-semibold text-chart-2">
                                    <HugeiconsIcon icon={ShieldCheckIcon} strokeWidth={1.5} className="size-3.5"  />
                                    Tindakan Pengendalian
                                </Label>
                                {bahaya.pengendalian.map((kendali, pi) => (
                                    // eslint-disable-next-line react/no-array-index-key
                                    <div key={pi} className="mb-1">
                                        <div className="flex gap-2">
                                            <Input
                                                className="h-7 flex-1"
                                                name={nama('[' + li + '][bahaya][' + bi + '][pengendalian][' + pi + ']')}
                                                value={kendali ?? ''}
                                                placeholder="Langkah pengendalian bahaya"
                                                onChange={(e) =>
                                                    ubahBahaya(li, bi, {
                                                        pengendalian: bahaya.pengendalian.map((v, n) =>
                                                            n === pi ? e.target.value : v,
                                                        ),
                                                    })
                                                }
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                className="size-7 shrink-0"
                                                title="Tambah pengendalian"
                                                onClick={() =>
                                                    ubahBahaya(li, bi, { pengendalian: [...bahaya.pengendalian, ''] })
                                                }
                                            >
                                                <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                                            </Button>
                                            {bahaya.pengendalian.length > 1 ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    className="size-7 shrink-0"
                                                    title="Hapus"
                                                    onClick={() => {
                                                        const sisa = bahaya.pengendalian.filter((_, n) => n !== pi);
                                                        ubahBahaya(li, bi, { pengendalian: sisa.length ? sisa : [''] });
                                                    }}
                                                >
                                                    <HugeiconsIcon icon={MinusSignIcon} strokeWidth={1.5} />
                                                </Button>
                                            ) : null}
                                        </div>

                                        <CatatanItem
                                            daftar={catatan?.['L' + li + '-B' + bi + '-P' + pi]}
                                            nilaiKini={kendali ?? ''}
                                        />
                                    </div>
                                ))}
                            </div>
                        ))}

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="mt-1"
                            onClick={() => ubahLangkah(li, { bahaya: [...step.bahaya, bahayaBaru()] })}
                        >
                            <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                            Tambah Bahaya
                        </Button>
                    </CardContent>
                </Card>
            ))}

            <Button type="button" className="w-full" onClick={() => onChange([...steps, langkahBaru()])}>
                <HugeiconsIcon icon={PlusSignIcon} strokeWidth={1.5} />
                Tambah Langkah Kerja Baru
            </Button>
        </div>
    );
}
