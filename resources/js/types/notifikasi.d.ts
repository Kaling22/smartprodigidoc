/**
 * Kontrak props halaman `V2/Notifications/Index` (F7 DASBOR-V4).
 *
 * Terpisah dari `dasbor.d.ts` — halaman ini bukan bagian dashboard, dan
 * bentuknya (`kategori`, `dibaca`) tak dipakai kartu dashboard mana pun.
 */

import type { Paginator } from '@/components/v2/DataTable';

/** Satu baris — bentuknya SUDAH DIRATAKAN di server (§P4), bukan model. */
export interface NotifikasiBaris {
    id: string;
    /** Label kategori, dari `DocumentNotification::KATEGORI`. */
    judul: string;
    pesan: string;
    /** Kunci `bi-*`, dipetakan lucide lewat `v2/Ikon`. */
    ikon: string;
    kategori: string;
    nomor: string | null;
    tautan: string;
    /** Sudah `diffForHumans` — TSX tak memformat ulang waktu. */
    waktu: string | null;
    dibaca: boolean;
}

export interface NotificationsIndexProps {
    notifikasi: Paginator<NotifikasiBaris>;
    filters: { status?: string; kategori?: string; q?: string };
    kategoriOpsi: string[];
    belumDibaca: number;
}
