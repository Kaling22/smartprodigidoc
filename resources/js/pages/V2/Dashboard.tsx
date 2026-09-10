import { usePage } from '@inertiajs/react';
import { useState } from 'react';

import { AktivitasTerbaru } from '@/components/v2/dasbor/AktivitasTerbaru';
import { GrafikOverview } from '@/components/v2/dasbor/GrafikOverview';
import { KartuDistribusi } from '@/components/v2/dasbor/KartuDistribusi';
import { KartuKetersediaan } from '@/components/v2/dasbor/KartuKetersediaan';
import { KartuLog } from '@/components/v2/dasbor/KartuLog';
import { KartuMasukan } from '@/components/v2/dasbor/KartuMasukan';
import { KartuSambutan } from '@/components/v2/dasbor/KartuSambutan';
import { KartuSebaran } from '@/components/v2/dasbor/KartuSebaran';
import { KartuStatistik } from '@/components/v2/dasbor/KartuStatistik';
import { LacakStatus } from '@/components/v2/dasbor/LacakStatus';
import { MeterTertinjau } from '@/components/v2/dasbor/MeterTertinjau';
import { PerformaPic } from '@/components/v2/dasbor/PerformaPic';
import { PitaDepartemen } from '@/components/v2/dasbor/PitaDepartemen';
import { SebaranJenisDept } from '@/components/v2/dasbor/SebaranJenisDept';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { DashboardProps, KunciDurasi } from '@/types/dasbor';

/**
 * Dashboard V2 — BALIK PENUH ke tata letak V1 (DASBOR-V2-REVISI §3, keputusan
 * pemilik D2).
 *
 * Versi sebelumnya menyusun ulang dasbor jadi larik deskriptor
 * `{kunci, span, node}` di atas `grid-flow-row-dense`, dengan pita merek
 * menggantikan blok judul dan `ArusDokumen` menggantikan Overview+meter.
 * Pemilik menilai perombakan itu terlalu jauh: dari V2 yang dipertahankan
 * HANYA tema maia, ikon hugeicons, dan desain sidebar (D1). Susunan barisnya
 * karena itu kembali persis mengikuti `pages/Dashboard.tsx`, baris demi baris,
 * supaya kedua kerangka bisa dibandingkan berdampingan selama jendela
 * pratinjau dan yang berbeda benar-benar cuma GAYA-nya dan enam widget yang
 * memang diminta digambar ulang (D4, D8–D10).
 *
 * Konsekuensinya, dan semuanya disengaja:
 *
 *  • Judul halaman TIDAK digambar di badan — ia duduk di topbar, sebaris
 *    dengan `SidebarTrigger` (keputusan pemilik sesudah pemeriksaan mata Fase
 *    2, dan itu memang tempatnya di blok `dashboard-01`). Sapaan tetap milik
 *    `KartuSambutan`, jadi tak ada yang tergambar dua kali.
 *  • `PitaSambutan` dan `ArusDokumen` tak lagi diimpor siapa pun. Berkasnya
 *    sengaja MASIH ADA (K3, ditanyakan ulang tiap fase; pemilik tetap memilih
 *    menahan pada awal Fase 3): ia jadi pembanding selama jendela pratinjau,
 *    dan penghapusan berkas butuh konfirmasi eksplisit (CLAUDE.md §4).
 *    `PerluTindakan` menyusul ke daftar itu sejak `LacakStatus` berdiri di
 *    baris keenam — ia kini juga nol pengimpor.
 *  • Gerbang tampil `adaMeja` / `adaMasukan` / `duaKolom` disalin APA ADANYA
 *    dari `pages/Dashboard.tsx` — termasuk ganjal `<div hidden>`-nya. Ganjal
 *    itu dulu dibuang; ia dikembalikan karena tanpanya kolom 1/3 melompat ke
 *    kiri pada peran yang kehilangan kartu 2/3, dan dua kerangka jadi tak bisa
 *    dibandingkan.
 *  • Baris `PitaDepartemen | PerformaPic` (§5e, §5d) tak lagi sepasang.
 *    Sejak REVISI-UI-V3 gerbangnya berbeda — sebaran per departemen milik
 *    PJO/MD/Admin, Performa PIC milik semua kecuali GL & Non-Staff — jadi
 *    barisnya digambar per kartu dan SH/DH mendapat ganjal di kolom 2/3.
 *    Ini mencabut K1, yang dulu memberi GL kedua kartu itu.
 */
export default function Dashboard() {
    const { props } = usePage<PageProps & DashboardProps>();
    const {
        user,
        greeting,
        hero,
        tiles,
        matrix,
        menungguDiMeja,
        masukanWidget,
        masukanTotal,
        distribusiWidget,
        sebaranDepartemen,
        sebaranJenisDept,
        performaPic,
        aktivitasTabel,
        aktivitasFilters,
        aktivitasDepartemen,
        aktivitasStatusOpsi,
        kalenderOff,
        offSaya,
        offAktif,
        isCreator,
        isPjo,
        isDeptHead,
        isMd,
        isNonStaff,
        activities,
        tren,
        sebaran,
        jenisList,
        jenisOff,
        urlOffStore,
    } = props;

    // Bulanan = keadaan awal, sama dengan V1 (`$awal = $tren['bulan']`).
    // Overview dan meter Tertinjau berbagi SATU keadaan durasi, jadi mustahil
    // ada dua angka di satu baris yang berbicara tentang rentang berbeda.
    const [durasi, setDurasi] = useState<KunciDurasi>('bulan');
    const deret = tren[durasi];

    // Kartu kiri baris 6 & 7 — syaratnya disalin apa adanya dari V1. Kartunya
    // dirender walau koleksinya kosong: tanpa itu dashboard sebuah peran
    // berganti bentuk dari hari ke hari, tergantung ada tidaknya isi.
    const adaMeja = menungguDiMeja.length > 0 || isCreator || isDeptHead || isPjo || isMd;
    const adaMasukan = masukanWidget.length > 0 || isNonStaff || isDeptHead || isPjo || isCreator || isMd;
    const duaKolom = adaMeja || adaMasukan;

    return (
        <AppLayout judul="Dashboard">
            <KartuSambutan greeting={greeting} nama={user.name} hero={hero} />

            <div className="@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6">
                <div className="@5xl/main:col-span-2">
                    <GrafikOverview deret={deret} durasi={durasi} onDurasi={setDurasi} />
                </div>
                <MeterTertinjau deret={deret} />
            </div>

            <KartuStatistik tiles={tiles} />

            <div className="@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6">
                {distribusiWidget && (
                    <div className="@5xl/main:col-span-2">
                        <KartuDistribusi widget={distribusiWidget} />
                    </div>
                )}
                <div className={distribusiWidget ? '' : '@5xl/main:col-span-3'}>
                    <KartuSebaran sebaran={sebaran} />
                </div>
            </div>

            {/*
              | Gerbang kedua kartu ini SUDAH TIDAK SAMA sejak REVISI-UI-V3
              | §4.1/§4.2, jadi barisnya digambar PER KARTU:
              |
              |   GL · Non-Staff   -> barisnya lenyap utuh
              |   SH · DH          -> [ SebaranJenisDept 2/3 ] [ PerformaPic 1/3 ]  (F4a)
              |   PJO · MD · Admin -> [ PitaDepartemen 2/3 ] [ PerformaPic 1/3 ]
              |
              | Ganjal `<div hidden>` mengikuti konvensi baris `LacakStatus` &
              | `KartuMasukan` di bawah: tanpanya kolom 1/3 melompat ke kiri pada
              | peran yang kehilangan kartu 2/3-nya, dan dua kerangka jadi tak
              | bisa dibandingkan berdampingan.
              |
              | Keputusan perannya TETAP milik server — yang diuji di sini cuma
              | `!== null` atas prop yang dikirimnya, nol jabatan yang dibaca di
              | TSX (CLAUDE.md §4).
              */}
            {(sebaranDepartemen || performaPic) && (
                <div className="@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6">
                    {sebaranDepartemen ? (
                        <div className="@5xl/main:col-span-2">
                            <PitaDepartemen sebaran={sebaranDepartemen} />
                        </div>
                    ) : sebaranJenisDept ? (
                        <div className="@5xl/main:col-span-2">
                            <SebaranJenisDept sebaran={sebaranJenisDept} />
                        </div>
                    ) : (
                        <div className="@5xl/main:col-span-2 @5xl/main:block hidden" />
                    )}
                    {performaPic && <PerformaPic data={performaPic} />}
                </div>
            )}

            {/* F6 DASBOR-V4 — HANYA PJO/MD/Admin (`document.view_all`). Satu-
                satunya kartu selebar layar penuh, persis bentuk referensinya. */}
            {aktivitasTabel && (
                <AktivitasTerbaru
                    tabel={aktivitasTabel}
                    filters={aktivitasFilters ?? {}}
                    departemen={aktivitasDepartemen}
                    statusOpsi={aktivitasStatusOpsi}
                    jenisList={jenisList}
                />
            )}

            <div className="@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6">
                {adaMeja ? (
                    <div className="@5xl/main:col-span-2">
                        <LacakStatus
                            baris={menungguDiMeja}
                            matrix={matrix}
                            urlSemua={route('documents.index')}
                        />
                    </div>
                ) : duaKolom ? (
                    <div className="@5xl/main:col-span-2 @5xl/main:block hidden" />
                ) : null}
                <div className={duaKolom ? '' : '@5xl/main:col-span-3'}>
                    <KartuKetersediaan
                        kalender={kalenderOff}
                        offSaya={offSaya}
                        offAktif={offAktif}
                        jenisOff={jenisOff}
                        urlOffStore={urlOffStore}
                        meja={menungguDiMeja}
                    />
                </div>
            </div>

            <div className="@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6">
                {adaMasukan ? (
                    <div className="@5xl/main:col-span-2">
                        <KartuMasukan
                            masukan={masukanWidget}
                            total={masukanTotal}
                            nonStaff={isNonStaff}
                            urlLogMasukan={route('log.masukan')}
                            urlBerlaku={route('documents.published')}
                        />
                    </div>
                ) : duaKolom ? (
                    <div className="@5xl/main:col-span-2 @5xl/main:block hidden" />
                ) : null}
                <div className={duaKolom ? '' : '@5xl/main:col-span-3'}>
                    <KartuLog activities={activities} />
                </div>
            </div>
        </AppLayout>
    );
}
