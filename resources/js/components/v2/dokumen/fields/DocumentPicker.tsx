/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/DocumentPicker.tsx` (REDESAIN-UI-V2 T2).
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
import { ArrowDown01Icon, ArrowUp01Icon, LeftToRightListNumberIcon, PencilEdit01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Input } from '@/components/ui-maia/input';
import { cn } from '@/lib/utils';
import type { DokumenPilihan } from '@/types/wizard';

import { useWizard } from './konteks';

/**
 * Pemilih dokumen lampiran (bab VII SOP) — pengganti cabang `document_picker`
 * di `documents/fields/_repeatable_group.blade.php`.
 *
 * SATU kolom yang bisa dipilih dari daftar dokumen Berlaku ATAU diketik
 * sendiri. Kolomnya sendiri yang menyimpan nilai, jadi ketikan bebas tak pernah
 * hilang — daftar hanya MENGISIKAN teks ke kolom itu.
 *
 * Yang tersimpan adalah TEKS, bukan id. Disengaja:
 * lampiran MENYEBUT dokumen sebagai keterangan cetak, bukan merelasikannya,
 * supaya barisnya tetap terbaca kalau dokumen rujukannya kelak dihapus.
 *
 * Bentuk teksnya ditentukan SCHEMA, bukan komponen ini (butir 6): tanpa kunci
 * `judul_ke` ia tetap satu baris gabungan ("PPA-ADRO-IK-ICTMD-02 — Instruksi
 * Kerja Perawatan"); dengan `judul_ke`, nomor tinggal di kolom ini dan judulnya
 * turun ke kolom saudara yang disebut kunci itu.
 *
 * Sengaja BUKAN blok `Combobox` registry (Command + Popover): blok itu hanya
 * bisa MEMILIH dari daftar tertutup dan memindahkan fokus ke dalam popover,
 * sementara kolom ini harus tetap menerima ketikan bebas. `<datalist>` bawaan
 * peramban juga sempat dipakai dan dilepas — ia tak bisa menampilkan lencana
 * jenis + nomor bergaya monospace, dan rupanya beda-beda tiap peramban.
 */
export function DocumentPicker({
    value,
    onChange,
    nama,
    opsional,
    peringatan,
    placeholder,
    onPilih,
}: {
    value: string;
    onChange: (teks: string) => void;
    nama: string;
    opsional: boolean;
    peringatan: string | null;
    placeholder?: string;
    /**
     * Dipasang HANYA bila schema kolomnya menyebut `judul_ke` (butir 6).
     * Ada → pemilihan dari daftar memecah nomor & judul ke dua kolom.
     * Tak ada → perilaku lama: satu baris teks gabungan.
     *
     * Ketikan bebas TIDAK ikut dipecah — hanya di daftar inilah nomor dan
     * judulnya memang sudah terpisah.
     */
    onPilih?: (nomor: string, judul: string) => void;
}) {
    const { dokumenBerlaku } = useWizard();

    const [buka, setBuka] = useState(false);
    const [sorot, setSorot] = useState(0);
    const kotak = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!buka) return;

        const tutup = (e: MouseEvent) => {
            if (kotak.current && !kotak.current.contains(e.target as Node)) setBuka(false);
        };

        document.addEventListener('mousedown', tutup);

        return () => document.removeEventListener('mousedown', tutup);
    }, [buka]);

    const label = (o: DokumenPilihan) => `${o.nomor} — ${o.judul}`;

    // Satu pintu pemilihan, dipakai klik MAUPUN Enter — kalau keduanya menulis
    // sendiri-sendiri, salah satunya pasti ketinggalan saat perilakunya berubah.
    const pilih = (o: DokumenPilihan) => {
        if (onPilih) onPilih(o.nomor, o.judul);
        else onChange(label(o));
        setBuka(false);
    };

    const cocok = (q: string) => {
        const s = (q ?? '').toLowerCase().trim();
        if (!s) return dokumenBerlaku;

        return dokumenBerlaku.filter((o) => `${o.jenis} ${o.nomor} ${o.judul}`.toLowerCase().includes(s));
    };

    const daftar = cocok(value);

    const tombol = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setBuka(true);
            setSorot((s) => Math.min(s + 1, daftar.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setSorot((s) => Math.max(s - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            // Enter memilih baris yang tersorot; kalau tak ada yang cocok,
            // ketikan pengguna dikembalikan apa adanya.
            const o = daftar[sorot];
            if (o) pilih(o);
            setBuka(false);
        } else if (e.key === 'Escape') {
            setBuka(false);
        }
    };

    return (
        <>
            <div className="relative" ref={kotak}>
                <div className="flex gap-2">
                    <Input
                        className="flex-1"
                        autoComplete="off"
                        role="combobox"
                        aria-expanded={buka}
                        aria-autocomplete="list"
                        name={nama}
                        value={value ?? ''}
                        placeholder={placeholder ?? ''}
                        {...(opsional ? { 'data-optional': '' } : {})}
                        {...(peringatan ? { 'data-pp-warn': peringatan } : {})}
                        onFocus={() => setBuka(true)}
                        onChange={(e) => {
                            onChange(e.target.value);
                            setBuka(true);
                            setSorot(0);
                        }}
                        onKeyDown={tombol}
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8 shrink-0"
                        tabIndex={-1}
                        aria-label={buka ? 'Tutup daftar' : 'Buka daftar dokumen'}
                        onClick={() => setBuka((b) => !b)}
                    >
                        {buka ? <HugeiconsIcon icon={ArrowUp01Icon} strokeWidth={1.5} /> : <HugeiconsIcon icon={ArrowDown01Icon} strokeWidth={1.5} />}
                    </Button>
                </div>

                {buka ? (
                    <div className="absolute inset-x-0 top-[calc(100%+0.3rem)] z-50 max-h-64 overflow-y-auto rounded-xl border bg-popover p-1 shadow-lg">
                        {daftar.length === 0 ? (
                            <p className="flex items-center gap-1.5 px-2 py-2 text-xs text-muted-foreground">
                                <HugeiconsIcon icon={PencilEdit01Icon} strokeWidth={1.5} className="size-3.5"  />
                                Tak ada dokumen yang cocok — teks yang Anda ketik dipakai apa adanya.
                            </p>
                        ) : (
                            daftar.map((o, oi) => (
                                <button
                                    key={o.nomor}
                                    type="button"
                                    className={cn(
                                        'flex w-full items-start gap-2 rounded-lg px-2 py-1.5 text-left leading-tight transition-colors',
                                        oi === sorot && 'bg-accent',
                                    )}
                                    onMouseEnter={() => setSorot(oi)}
                                    onClick={() => pilih(o)}
                                >
                                    <span className="mt-0.5 shrink-0 rounded-md border border-primary/40 bg-primary/10 px-1.5 py-0.5 text-[0.66rem] font-medium tracking-wide text-primary">
                                        {o.jenis}
                                    </span>
                                    <span className="min-w-0">
                                        <span className="block font-mono text-xs font-semibold break-words">{o.nomor}</span>
                                        <span className="block text-xs text-muted-foreground">{o.judul}</span>
                                    </span>
                                </button>
                            ))
                        )}
                    </div>
                ) : null}
            </div>

            <p className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                <HugeiconsIcon icon={LeftToRightListNumberIcon} strokeWidth={1.5} className="size-3.5"  />
                {dokumenBerlaku.length} dokumen Berlaku di departemen Anda. Bisa juga diketik sendiri bila belum ada di
                sistem.
            </p>
        </>
    );
}
