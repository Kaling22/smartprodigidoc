import { Link, router, useForm } from '@inertiajs/react';
import { id as lokalId } from 'date-fns/locale';
import {
    ArrowLeft01Icon, ArrowRight01Icon, CalendarAdd01Icon, InformationCircleIcon, UserCheck01Icon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useState } from 'react';

import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Button } from '@/components/ui-maia/button';
import { Calendar } from '@/components/ui-maia/calendar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { ScrollArea } from '@/components/ui-maia/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui-maia/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui-maia/dialog';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui-maia/radio-group';
import { cn } from '@/lib/utils';
import type { BarisMeja, KalenderOff, Off } from '@/types/dasbor';

/**
 * "Ketersediaan Saya" — turunan `components/dasbor/KartuKetersediaan.tsx`.
 *
 * Sampai Fase 2 ia SALINAN mekanis: yang berbeda cuma dari kit mana komponennya
 * diambil (`ui` → `ui-maia`) dan dari pustaka mana glifnya (`lucide-react` →
 * hugeicons). Fase 3 menambahkan SATU hal di bawah kalendernya — daftar agenda
 * bertab (D5, §5a) — dan tidak menyentuh apa pun di atasnya: ketiga perilaku
 * yang wajib bertahan di bawah ini tetap persis seperti di V1.
 *
 * Kartunya duduk di dashboard, bukan di halaman tersendiri: menyatakan diri off
 * adalah tindakan sepuluh detik yang dilakukan sambil lalu, bukan tujuan
 * kunjungan.
 *
 * TIGA hal yang wajib bertahan dari Blade lama:
 *
 *  1. **Ganti bulan lewat TAUTAN**, bukan JavaScript (`?bulan=YYYY-MM`).
 *     Kalender ini dibaca, bukan dimainkan; tautannya harus bisa dibuka di tab
 *     baru dan di-bookmark. Karena itu navigasi bawaan react-day-picker
 *     DIMATIKAN (`hideNavigation`) dan diganti dua `<Link>` Inertia di kepala
 *     kartu — bulannya tetap dihitung server, termasuk batas 12 bulan.
 *  2. **Klik tanggal → dialog Ajukan Off terisi tanggal itu.** Kalender
 *     berhenti jadi papan-baca dan menjadi pintu masuk.
 *  3. **Tanggal lewat dimatikan** — off kemarin ditolak server
 *     (`after_or_equal:today`), jadi membiarkannya bisa ditekan cuma
 *     menjanjikan jalan yang pasti buntu.
 *
 * Grid harinya digambar komponen `Calendar` registry (react-day-picker), bukan
 * kisi tulisan tangan. Yang dari server tinggal DAFTAR TANGGAL OFF-nya, dipakai
 * sebagai `modifiers` — server tetap satu-satunya yang tahu rentang cuti siapa.
 *
 * ── DAFTAR AGENDA BERTAB (D5 §5a) ──────────────────────────────────────────
 *
 * Di bawah kalender duduk `Tabs` + `ScrollArea` berisi agenda, tiga tab:
 * **Semua · Ditugaskan · Jadwal Saya**. Tak ada blok registry untuk "kalender +
 * tab agenda", jadi ia disusun dari primitif `ui-maia/{calendar,tabs,scroll-area}`
 * — alasan penyimpangannya ini (CLAUDE.md §13).
 *
 * NOL query baru. Keduanya prop yang sudah dikirim server dan sebagian belum
 * pernah digambar:
 *   • **Ditugaskan** — `menungguDiMeja[].sejak`, tanggal dokumen itu masuk ke
 *     meja saya, beserta umurnya. Paling banyak EMPAT butir; itu konsekuensi
 *     batas `limit(4)` di controller (§P1) dan ia jujur, karena keempatnya
 *     memang yang paling lama menunggu (`orderBy('submitted_at')`).
 *   • **Jadwal Saya** — `offSaya[].mulai`/`.sampai`.
 *
 * TITIK di bawah tanggal diturunkan DI KLIEN: `modifiers.agenda` mencocokkan
 * tanggal agenda dengan `kalenderOff.sel[].tanggal`, jadi hanya tanggal yang
 * memang tergambar di bulan ini yang bertitik. Nol perubahan server.
 *
 * Tanggal tetap string `'Y-m-d'` dan DIBANDINGKAN SEBAGAI STRING — jangan lewat
 * `new Date()`. Alasannya di `DashboardController::kalenderOff()`: penguraian
 * UTC menggeser hari di WITA. Yang boleh lewat `Date` cuma sel kalender, dan itu
 * pun lewat `tanggalLokal()` di kaki berkas ini.
 *
 * Ketiga `<Link>` nav bulan ber-`preserveState`: tanpanya kunjungan Inertia
 * memasang ulang komponen halaman, dan tab yang sedang dibuka melompat kembali
 * ke "Semua" setiap kali bulannya diganti.
 */
export function KartuKetersediaan({
    kalender,
    offSaya,
    offAktif,
    jenisOff,
    urlOffStore,
    meja,
}: {
    kalender: KalenderOff;
    offSaya: Off[];
    offAktif: { id: number; urlBatal: string } | null;
    jenisOff: Record<string, string>;
    urlOffStore: string;
    /** Dokumen yang menunggu tindakan saya — pemasok tab "Ditugaskan". */
    meja: BarisMeja[];
}) {
    const [buka, setBuka] = useState(false);
    const [tab, setTab] = useState('semua');

    const form = useForm({
        jenis: Object.keys(jenisOff)[0] ?? 'cuti',
        mulai: hariIniIso(),
        sampai: hariIniIso(),
        catatan: '',
    });

    // Tanggal dibaca dari string 'Y-m-d' lewat konstruktor lokal (bukan
    // `new Date('2026-08-28')`, yang diurai sebagai UTC dan mundur sehari di
    // WITA). Sel `null` adalah pendahulu kolom hari — react-day-picker
    // menghitungnya sendiri, jadi di sini ia disaring.
    const tanggalOff = kalender.sel
        .filter((s): s is NonNullable<typeof s> => s !== null && s.off !== null)
        .map((s) => tanggalLokal(s.tanggal));

    // Bulan yang ditampilkan datang dari server, bukan dari keadaan klien:
    // itulah yang membuat `?bulan=` tetap jadi sumber kebenarannya.
    const selPertama = kalender.sel.find((s) => s !== null);
    const bulanTampil = selPertama ? tanggalLokal(selPertama.tanggal) : new Date();

    // Agenda disusun dari DUA prop yang sudah ada, dan tanggalnya tetap string
    // 'Y-m-d' sepanjang jalan — dibandingkan dan diurutkan sebagai string, tak
    // pernah lewat `new Date()`.
    const tugas: Agenda[] = meja
        .filter((b) => b.sejak !== null)
        .map((b) => ({
            kunci: `tugas-${b.dokumen.id}`,
            tanggal: b.sejak as string,
            judul: b.judul,
            ket: `${b.nomor} · ${b.umur < 1 ? 'masuk hari ini' : `${b.umur} hari menunggu`}`,
            tautan: b.tautan,
            tugas: true,
        }));

    const jadwal: Agenda[] = offSaya.map((o) => ({
        kunci: `off-${o.id}`,
        tanggal: o.mulai,
        judul: o.jenis,
        // `rentang` LABEL siap cetak dari server — jangan disusun ulang di sini.
        ket: o.catatan ? `${o.rentang} · ${o.catatan}` : o.rentang,
        tautan: null,
        tugas: false,
    }));

    const isiTab: Record<string, Agenda[]> = {
        semua: [...tugas, ...jadwal].sort((a, b) => a.tanggal.localeCompare(b.tanggal)),
        tugas,
        jadwal,
    };

    // Titik di bawah tanggal: hanya untuk tanggal agenda yang MEMANG tergambar
    // di bulan yang sedang tampil. `off` sudah punya penandanya sendiri, jadi
    // yang bertitik di sini cuma agenda yang bukan off.
    const tanggalAgendaBulanIni = new Set(isiTab.semua.map((a) => a.tanggal));
    const tanggalAgenda = kalender.sel
        .filter((s): s is NonNullable<typeof s> => s !== null && tanggalAgendaBulanIni.has(s.tanggal))
        .map((s) => tanggalLokal(s.tanggal));

    const bukaDengan = (tgl: Date) => {
        const iso = isoLokal(tgl);
        // `sampai` ikut disamakan supaya off sehari — kasus paling sering —
        // langsung sah tanpa menyentuh kolom kedua.
        form.setData((d) => ({ ...d, mulai: iso, sampai: iso }));
        setBuka(true);
    };

    const ajukan = () => form.post(urlOffStore, { onSuccess: () => setBuka(false), preserveScroll: true });

    const batalkan = (url: string) => router.delete(url, { preserveScroll: true });

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Ketersediaan Saya</CardTitle>
            </CardHeader>

            <CardContent className="flex min-h-0 flex-1 flex-col">
                <div className="mb-2 flex items-center justify-between">
                    <Button
                        asChild={kalender.bisaMundur}
                        variant="ghost"
                        size="icon"
                        className={cn('size-8', !kalender.bisaMundur && 'invisible')}
                        aria-label="Bulan sebelumnya"
                    >
                        {kalender.bisaMundur ? (
                            <Link href={kalender.urlSebelum} preserveScroll preserveState>
                                <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={1.5} className="size-4" />
                            </Link>
                        ) : (
                            <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={1.5} className="size-4" />
                        )}
                    </Button>

                    <span className="text-sm font-semibold">
                        {kalender.judul}
                        {!kalender.iniBulanIni && (
                            <Link
                                href={kalender.urlHariIni}
                                preserveScroll
                                preserveState
                                className="ml-2 text-xs font-normal text-primary underline underline-offset-2"
                            >
                                hari ini
                            </Link>
                        )}
                    </span>

                    <Button
                        asChild={kalender.bisaMaju}
                        variant="ghost"
                        size="icon"
                        className={cn('size-8', !kalender.bisaMaju && 'invisible')}
                        aria-label="Bulan berikutnya"
                    >
                        {kalender.bisaMaju ? (
                            <Link href={kalender.urlSesudah} preserveScroll preserveState>
                                <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} className="size-4" />
                            </Link>
                        ) : (
                            <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} className="size-4" />
                        )}
                    </Button>
                </div>

                <Calendar
                    month={bulanTampil}
                    hideNavigation
                    showOutsideDays={false}
                    locale={lokalId}
                    disabled={{ before: new Date(new Date().setHours(0, 0, 0, 0)) }}
                    modifiers={{ off: tanggalOff, agenda: tanggalAgenda }}
                    // ANGKANYA yang berwarna, bukan latarnya yang pekat dengan
                    // angka putih: angka putih di atas blok warna berhenti
                    // terbaca sebagai TANGGAL dan berubah jadi blok warna.
                    //
                    // EMPAT KEADAAN, EMPAT RUPA — dan keempatnya harus tetap
                    // bisa dibedakan sekaligus (REVISI-UI-V3 §4.4):
                    //   tanggal biasa → angka polos, terbaca
                    //   hari ini      → latar `muted` + cincin, penanda POSISI
                    //   agenda        → TITIK saja, angkanya tak diwarnai
                    //   cuti/off      → satu-satunya yang berlatar --primary
                    modifiersClassNames={{
                        off: 'bg-primary/20 text-primary font-bold rounded-md',
                        // Titik agenda digantung DI BAWAH angkanya lewat
                        // `::after`, bukan lewat elemen tambahan: sel kalender
                        // digambar react-day-picker, dan menyisipkan anak ke
                        // dalamnya berarti menyalin `components` bawaannya.
                        agenda: "relative after:bg-primary after:absolute after:bottom-1 after:left-1/2 after:size-1 after:-translate-x-1/2 after:rounded-full after:content-['']",
                    }}
                    onDayClick={(hari, pengubah) => {
                        if (!pengubah.disabled) bukaDengan(hari);
                    }}
                    // Sel kalender ELASTIS, memenuhi lebar kartu (CLAUDE.md §6):
                    // lebar tetap-lah yang dulu menjepit nama hari sekaligus
                    // menyisakan ruang kosong di sisi kanan.
                    className="w-full p-0 [--cell-size:--spacing(9)]"
                    /*
                     | `disabled` dan `today` DITIMPA di sini, dan itu bukan
                     | selera melainkan perbaikan keterbacaan.
                     |
                     | Seluruh tanggal LAMPAU kena `disabled` (lihat prop di
                     | atas), dan `ui-maia/calendar` menatanya
                     | `text-muted-foreground opacity-50` — sehingga separuh
                     | bulan berjalan nyaris tak terbaca dan kalendernya
                     | kehilangan gunanya sebagai kalender. Opasitasnya
                     | dikembalikan penuh; yang menyatakan "tak bisa ditekan"
                     | tetap ada dan tetap benar untuk pembaca layar —
                     | `aria-disabled` dari react-day-picker dan kursor yang tak
                     | berubah — jadi nol makna yang hilang.
                     |
                     | `today` diberi CINCIN, bukan sekadar latar: latar `muted`
                     | saja terlalu mirip dengan latar `--primary/20` milik
                     | cuti, dan dua penanda yang nyaris sama artinya nol
                     | penanda.
                     |
                     | Ditulis di TEMPAT PEMANGGILAN karena `Calendar` menggabung
                     | `{ ...bawaan, ...classNames }` — kunci yang dikirim
                     | MENGGANTI seluruh nilai bawaannya, bukan menambah — dan
                     | karena berkas di `ui-maia/` tak boleh disunting (§4).
                     */
                    classNames={{
                        month_caption: 'hidden',
                        months: 'w-full',
                        disabled: 'text-foreground/75',
                        today: 'rounded-md ring-1 ring-primary/40 bg-muted font-semibold data-[selected=true]:rounded-none',
                    }}
                />

                {/* Tiga penanda, tiga keterangan. "Hari ini" ikut disebut
                    sejak ia diberi cincin: penanda yang tak dijelaskan cuma
                    membuat orang menebak. */}
                <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span>
                        <span className="mr-1.5 inline-block size-2.5 rounded-full bg-primary/40 align-middle" />
                        cuti/off
                    </span>
                    <span>
                        <span className="ring-primary/40 bg-muted mr-1.5 inline-block size-2.5 rounded-full align-middle ring-1" />
                        hari ini
                    </span>
                    <span>
                        <span className="mr-1.5 inline-block size-1 rounded-full bg-primary align-middle" />
                        ada agenda
                    </span>
                </div>

                {/* Tab agenda (D5). Keadaannya dipegang di sini dan nav bulan
                    ber-`preserveState`, jadi mengganti bulan TIDAK melempar
                    pembacanya kembali ke tab "Semua". */}
                <Tabs value={tab} onValueChange={setTab} className="mt-4 min-h-0 flex-1 gap-2">
                    <TabsList className="w-full">
                        <TabsTrigger value="semua">Semua</TabsTrigger>
                        <TabsTrigger value="tugas">Ditugaskan</TabsTrigger>
                        <TabsTrigger value="jadwal">Jadwal Saya</TabsTrigger>
                    </TabsList>

                    {/* Ketiga tab MENGISI sisa kartu, bukan dipatok 10rem.
                        `h-40` yang dulu dipakai selalu salah pada salah satu
                        sisinya: kartu ini berbagi baris dengan `LacakStatus`
                        yang jauh lebih tinggi, jadi tingginya tak pernah 10rem —
                        agenda terpotong pada butir ketiga sementara sisa
                        kartunya menganggur kosong.

                        Kotak absolutnya WAJIB `div` biasa, bukan `ScrollArea`
                        yang langsung diberi `absolute`:
                        `ui-maia/scroll-area.tsx` merakit kelasnya sebagai
                        `cn("relative", className)`, dan dua kelas `position`
                        pada satu elemen dimenangkan urutan stylesheet — di
                        Tailwind v4 `relative` menang, `inset-0` mati, dan
                        gulirnya tak pernah aktif. */}
                    {Object.entries(isiTab).map(([kunci, daftar]) => (
                        <TabsContent key={kunci} value={kunci} className="relative min-h-[10rem] flex-1">
                            <div className="absolute inset-0">
                            <ScrollArea className="h-full">
                                {daftar.length === 0 ? (
                                    <p className="text-muted-foreground py-6 text-center text-xs">
                                        {kunci === 'jadwal'
                                            ? 'Belum ada cuti atau off yang tercatat.'
                                            : 'Tak ada yang menunggu Anda.'}
                                    </p>
                                ) : (
                                    <ul className="space-y-2 pr-3">
                                        {daftar.map((a) => (
                                            <li key={a.kunci} className="flex items-start gap-2">
                                                <span
                                                    aria-hidden="true"
                                                    className={cn(
                                                        'mt-1.5 size-2 shrink-0 rounded-full',
                                                        a.tugas ? 'bg-primary' : 'bg-primary/40',
                                                    )}
                                                />
                                                <div className="min-w-0 flex-1">
                                                    {a.tautan ? (
                                                        <Link
                                                            href={a.tautan}
                                                            className="hover:text-primary block truncate text-xs font-medium"
                                                        >
                                                            {a.judul}
                                                        </Link>
                                                    ) : (
                                                        <p className="truncate text-xs font-medium">{a.judul}</p>
                                                    )}
                                                    <p className="text-muted-foreground truncate text-xs">
                                                        {a.ket}
                                                    </p>
                                                </div>
                                                <span className="text-muted-foreground shrink-0 font-mono text-[0.6875rem]">
                                                    {a.tanggal}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </ScrollArea>
                            </div>
                        </TabsContent>
                    ))}
                </Tabs>

                {/* Tindakan duduk di BAWAH KANAN, bukan di kepala kartu:
                    kalendernya yang dibaca lebih dulu, tombolnya baru dipakai
                    sesudah itu. "On Site" hanya muncul saat off SEDANG berjalan —
                    ia membatalkan off itu (saya sudah kembali). */}
                <div className="mt-auto flex justify-end gap-1 pt-3">
                    {offAktif && (
                        <ConfirmDialog
                            pemicu={
                                <Button variant="ghost" size="sm">
                                    <HugeiconsIcon icon={UserCheck01Icon} strokeWidth={1.5} className="size-4" />
                                    On Site
                                </Button>
                            }
                            judul="Batalkan off?"
                            pesan="Anda langsung muncul lagi sebagai peninjau yang bisa dipilih."
                            tombolYa="Ya, batalkan"
                            onKonfirmasi={() => batalkan(offAktif.urlBatal)}
                        />
                    )}
                    <Button variant="ghost" size="sm" onClick={() => setBuka(true)}>
                        <HugeiconsIcon icon={CalendarAdd01Icon} strokeWidth={1.5} className="size-4" />
                        Ajukan Off
                    </Button>
                </div>
            </CardContent>

            <Dialog open={buka} onOpenChange={setBuka}>
                <DialogContent className="sm:max-w-lg">
                    {/* Pengiriman SELALU lewat ConfirmDialog di kaki dialog
                        (CLAUDE.md §13). Submit bawaan form karena itu ditahan:
                        kalau tidak, menekan Enter di kolom tanggal akan
                        melewati konfirmasinya. */}
                    <form onSubmit={(e) => e.preventDefault()}>
                        <DialogHeader>
                            <DialogTitle>Ajukan Off</DialogTitle>
                            <DialogDescription>
                                Selama rentang ini Anda tidak muncul sebagai peninjau yang bisa dipilih.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label>Jenis</Label>
                                <RadioGroup
                                    value={form.data.jenis}
                                    onValueChange={(v) => form.setData('jenis', v)}
                                    className="flex flex-wrap gap-4"
                                >
                                    {Object.entries(jenisOff).map(([nilai, label]) => (
                                        <div key={nilai} className="flex items-center gap-2">
                                            <RadioGroupItem value={nilai} id={`jenis-${nilai}`} />
                                            <Label htmlFor={`jenis-${nilai}`} className="font-normal">
                                                {label}
                                            </Label>
                                        </div>
                                    ))}
                                </RadioGroup>
                                {form.errors.jenis && <p className="text-sm text-destructive">{form.errors.jenis}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label htmlFor="offMulai">Mulai</Label>
                                    {/* Widget tanggal peramban, bukan ketik manual (CLAUDE.md §7). */}
                                    <Input
                                        id="offMulai"
                                        type="date"
                                        min={hariIniIso()}
                                        value={form.data.mulai}
                                        onChange={(e) => form.setData('mulai', e.target.value)}
                                        required
                                    />
                                    {form.errors.mulai && <p className="text-sm text-destructive">{form.errors.mulai}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="offSampai">Sampai</Label>
                                    <Input
                                        id="offSampai"
                                        type="date"
                                        min={hariIniIso()}
                                        max={isoLokal(new Date(Date.now() + 60 * 86400000))}
                                        value={form.data.sampai}
                                        onChange={(e) => form.setData('sampai', e.target.value)}
                                        required
                                    />
                                    {form.errors.sampai && <p className="text-sm text-destructive">{form.errors.sampai}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="offCatatan">Catatan (opsional)</Label>
                                <Input
                                    id="offCatatan"
                                    maxLength={255}
                                    placeholder="mis. cuti tahunan"
                                    value={form.data.catatan}
                                    onChange={(e) => form.setData('catatan', e.target.value)}
                                />
                            </div>

                            <Alert>
                                <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                                <AlertDescription>
                                    Dokumen yang <strong>sedang</strong> Anda tinjau tetap menjadi tanggung jawab Anda.
                                </AlertDescription>
                            </Alert>

                            {/* Daftar off duduk DI DALAM dialog, bukan di muka kartu:
                                di sini ia mengurus off yang AKAN DATANG; yang sedang
                                berjalan hari ini punya jalan pintasnya sendiri —
                                tombol "On Site". */}
                            {offSaya.length > 0 && (
                                <div className="space-y-1">
                                    <p className="text-sm font-semibold">Off yang tercatat</p>
                                    {offSaya.map((off) => (
                                        <div
                                            key={off.id}
                                            className="flex items-center justify-between rounded-md border px-2 py-1.5 text-sm"
                                        >
                                            <span>
                                                <span className="font-semibold">{off.jenis}</span>{' '}
                                                <span className="text-muted-foreground">· {off.rentang}</span>
                                                {off.catatan && (
                                                    <span className="block text-xs text-muted-foreground">{off.catatan}</span>
                                                )}
                                            </span>
                                            <ConfirmDialog
                                                pemicu={
                                                    <Button type="button" variant="link" size="sm" className="text-destructive">
                                                        Batalkan
                                                    </Button>
                                                }
                                                judul="Batalkan off?"
                                                pesan={`Batalkan ${off.jenis} ${off.rentang}? Anda langsung muncul lagi sebagai peninjau yang bisa dipilih.`}
                                                tombolYa="Ya, batalkan"
                                                destruktif
                                                onKonfirmasi={() => batalkan(off.urlBatal)}
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setBuka(false)}>
                                Batal
                            </Button>
                            <ConfirmDialog
                                pemicu={
                                    <Button type="button" disabled={form.processing}>
                                        <HugeiconsIcon icon={CalendarAdd01Icon} strokeWidth={1.5} className="size-4" />
                                        Ajukan Off
                                    </Button>
                                }
                                judul="Ajukan off?"
                                pesan="Anda tidak akan bisa dipilih sebagai peninjau selama rentang ini. Dokumen yang sedang Anda tinjau tetap menjadi tanggung jawab Anda."
                                tombolYa="Ya, ajukan"
                                onKonfirmasi={ajukan}
                            />
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

/**
 * Satu butir agenda — bentuk BERSAMA untuk dokumen yang menunggu saya dan cuti
 * saya, supaya tab "Semua" bisa mengurutkan keduanya dalam satu larik tanpa
 * cabang. `tugas` cuma menentukan warna titiknya; ia bukan izin dan bukan
 * aturan, jadi tak ada yang dipindahkan dari server ke sini.
 */
interface Agenda {
    kunci: string;
    /** `Y-m-d`. Diurutkan sebagai STRING. */
    tanggal: string;
    judul: string;
    ket: string;
    tautan: string | null;
    tugas: boolean;
}

/**
 * 'Y-m-d' → `Date` di zona LOKAL peramban.
 *
 * `new Date('2026-08-01')` diurai sebagai tengah malam UTC, yang di WITA
 * (UTC+8) tetap tanggal 1 — tapi di zona barat mundur jadi 31 Juli, dan sel
 * kalender ikut bergeser sehari. Konstruktor tiga-argumen tak punya jebakan itu.
 */
function tanggalLokal(iso: string): Date {
    const [t, b, h] = iso.split('-').map(Number);

    return new Date(t, b - 1, h);
}

/** Kebalikannya — `Date` lokal → 'Y-m-d', tanpa lewat UTC. */
function isoLokal(d: Date): string {
    return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
}

function hariIniIso(): string {
    return isoLokal(new Date());
}
