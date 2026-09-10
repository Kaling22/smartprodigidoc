import { Cancel01Icon, Search01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Input } from '@/components/ui-maia/input';
import {
    InputGroup, InputGroupAddon, InputGroupInput,
} from '@/components/ui-maia/input-group';
import { Kbd } from '@/components/ui-maia/kbd';
import { Label } from '@/components/ui-maia/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import type { Departemen } from '@/types';

/**
 * Penyaring daftar — kini MASUK KE DALAM `CardHeader` tabelnya.
 *
 * Versi nova adalah `Card` tersendiri yang duduk DI ATAS kartu tabel: dua kotak
 * bertumpuk untuk satu daftar, dengan tepi, bayangan, dan jarak yang dibayar
 * dua kali. Komponen ini karena itu tidak lagi menggambar `Card`-nya sendiri —
 * ia sekadar `<form>`, dan halamanlah yang meletakkannya di kepala kartu.
 *
 * Yang TIDAK berubah, karena semuanya masih dipakai daftar di luar dokumen:
 *  • ketiga tipe field (`pilih` / `teks` / `tanggal`) — Audit Log menyaring
 *    nama aksi sebagai TEKS bebas, Log Pesan & Riwayat Pekerjaan menyaring
 *    rentang TANGGAL;
 *  • konvensi `'*'` untuk nilai kosong (Radix Select menolak string kosong);
 *  • tetap query string, bukan fetch — halaman hasil saringan masih bisa
 *    di-bookmark dan dibuka di tab baru, persis seperti form GET lama;
 *  • penyaringannya sendiri tetap dikerjakan SERVER. Daftarnya berhalaman, dan
 *    menyaring di klien hanya menyaring 15 baris yang kebetulan sedang tampil.
 */

/** Radix Select tak menerima nilai kosong sebagai item; `*` = "Semua". */
const SEMUA = '*';

export interface PilihanSaring {
    nama: string;
    label: string;
    tipe?: 'pilih' | 'teks' | 'tanggal';
    /** Hanya untuk `pilih`. `[nilai, label]`. */
    opsi?: [string, string][];
    placeholder?: string;
}

export function PenyaringDokumen({
    url,
    filters,
    prefix = '',
    pilihan = [],
    /** Ikut dikirim tanpa tampil — mis. departemen yang dipilih dari sub-menu. */
    tersembunyi = {},
    contoh,
    labelCari = 'Cari',
    placeholderCari,
}: {
    url: string;
    filters: Record<string, string | undefined>;
    /** `Pengaturan::prefix()`. Boleh kosong bila `contoh` diisi. */
    prefix?: string;
    pilihan?: PilihanSaring[];
    tersembunyi?: Record<string, string | undefined>;
    /** Contoh pencarian untuk daftar yang barisnya BUKAN dokumen bernomor. */
    contoh?: string;
    labelCari?: string;
    placeholderCari?: string;
}) {
    const [nilai, setNilai] = useState<Record<string, string>>(() => {
        const awal: Record<string, string> = { q: filters.q ?? '' };
        pilihan.forEach((p) => (awal[p.nama] = filters[p.nama] ?? ''));

        return awal;
    });

    const kotak = useRef<HTMLInputElement>(null);

    /*
     | Tekan "/" untuk melompat ke kotak cari — jalan pintas yang sudah jadi
     | kebiasaan di daftar panjang. Diabaikan saat fokus sedang di kolom isian
     | mana pun, kalau tidak mengetik garis miring di dalam judul jadi mustahil.
     */
    useEffect(() => {
        const tekan = (e: KeyboardEvent) => {
            const t = e.target as HTMLElement | null;
            const sedangMengetik =
                t instanceof HTMLInputElement ||
                t instanceof HTMLTextAreaElement ||
                t?.isContentEditable;

            if (e.key === '/' && !sedangMengetik) {
                e.preventDefault();
                kotak.current?.focus();
            }
        };

        document.addEventListener('keydown', tekan);

        return () => document.removeEventListener('keydown', tekan);
    }, []);

    const adaIsi = Object.values(nilai).some((v) => v !== '');

    /*
     | LIVE FILTERING — tak ada lagi tombol "Filter".
     |
     | Tiap perubahan menembak ulang kunjungan Inertia sesudah diam 300 ms.
     | Empat bendera di bawah ini yang membuatnya tak menyiksa dipakai, dan
     | keempatnya wajib:
     |
     |  • `preserveState` — tanpa ini komponennya dibangun ulang tiap ketikan
     |    dan kursor keluar dari kotak cari di huruf kedua.
     |  • `preserveScroll` — daftar panjang tak melompat ke atas tiap ketikan.
     |  • `replace` — satu penyaringan = satu entri riwayat, bukan satu per
     |    huruf; tombol Back peramban tetap berarti.
     |  • `only` TIDAK dipakai: halaman ini dilayani banyak controller dengan
     |    nama prop berbeda-beda, dan menebaknya di komponen bersama adalah
     |    cara paling rapi untuk membuat satu halaman berhenti menyegarkan
     |    diam-diam.
     |
     | Penyaringannya sendiri TETAP di server dan TETAP lewat query string —
     | hasilnya masih bisa di-bookmark. Yang hilang cuma kliknya.
     |
     | Suapan pertama dilewati (`pertama`): saat halaman baru dibuka, `nilai`
     | sudah sama dengan `filters`, jadi menembak ulang cuma memanggil ulang
     | halaman yang sama persis.
     */
    const pertama = useRef(true);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        const jeda = setTimeout(() => {
            router.get(
                url,
                { ...tersembunyi, ...nilai },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(jeda);
        // `url` & `tersembunyi` tetap sepanjang umur halaman; yang memicu
        // hanyalah isian penyaringnya.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [nilai]);

    function saring(e: FormEvent) {
        e.preventDefault();
        router.get(url, { ...tersembunyi, ...nilai }, { preserveState: true });
    }

    return (
        <form onSubmit={saring} className="flex flex-wrap items-end gap-3">
            <div className="min-w-56 flex-1">
                <Label htmlFor="cari" className="text-muted-foreground mb-1 text-xs">
                    {labelCari}
                </Label>
                <InputGroup>
                    <InputGroupAddon>
                        <HugeiconsIcon icon={Search01Icon} strokeWidth={1.5} className="size-4" />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="cari"
                        ref={kotak}
                        value={nilai.q}
                        onChange={(e) => setNilai({ ...nilai, q: e.target.value })}
                        placeholder={placeholderCari ?? `mis. ${contoh ?? `${prefix}-SOP`} atau judul…`}
                    />
                    <InputGroupAddon align="inline-end">
                        <Kbd>/</Kbd>
                    </InputGroupAddon>
                </InputGroup>
            </div>

            {pilihan.map((p) => (
                <div key={p.nama} className="min-w-36">
                    <Label htmlFor={`saring-${p.nama}`} className="text-muted-foreground mb-1 text-xs">
                        {p.label}
                    </Label>
                    {p.tipe === 'teks' || p.tipe === 'tanggal' ? (
                        <Input
                            id={`saring-${p.nama}`}
                            type={p.tipe === 'tanggal' ? 'date' : 'text'}
                            value={nilai[p.nama]}
                            placeholder={p.placeholder}
                            onChange={(e) => setNilai({ ...nilai, [p.nama]: e.target.value })}
                        />
                    ) : (
                        <Select
                            value={nilai[p.nama] || SEMUA}
                            onValueChange={(v) => setNilai({ ...nilai, [p.nama]: v === SEMUA ? '' : v })}
                        >
                            <SelectTrigger id={`saring-${p.nama}`} className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {(p.opsi ?? []).map(([v, label]) => (
                                    <SelectItem key={v || SEMUA} value={v || SEMUA}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                </div>
            ))}

            <div className="flex gap-1">
                {/* Reset hanya muncul bila ada yang bisa direset — tombol yang
                    tak mengubah apa pun cuma mengundang orang mengujinya. */}
                {adaIsi ? (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Reset penyaring"
                        onClick={() => router.get(url, tersembunyi)}
                    >
                        <HugeiconsIcon icon={Cancel01Icon} strokeWidth={1.5} className="size-4" />
                    </Button>
                ) : null}
            </div>
        </form>
    );
}

/** Daftar departemen → pilihan saring, dengan "Semua" di depan. */
export function saringDepartemen(departments: Departemen[]): PilihanSaring {
    return {
        nama: 'department_id',
        label: 'Departemen',
        opsi: [['', 'Semua'], ...departments.map((d): [string, string] => [String(d.id), d.code])],
    };
}

/** Daftar kode jenis → pilihan saring. */
export function saringJenis(types: string[]): PilihanSaring {
    return {
        nama: 'type',
        label: 'Jenis',
        opsi: [['', 'Semua'], ...types.map((t): [string, string] => [t, t])],
    };
}

/**
 * Status → pilihan saring. Kunci mana yang boleh disaring datang dari props
 * `statusOpsi` (keputusan server), labelnya dari props global `statusLabels`
 * (`Document::STATUS_LABELS`) — tak satu pun diketik di sini (pakem P3).
 */
export function saringStatus(opsi: string[], labels: Record<string, string>): PilihanSaring {
    return {
        nama: 'status',
        label: 'Status',
        opsi: [['', 'Semua'], ...opsi.map((s): [string, string] => [s, labels[s] ?? s])],
    };
}
