import { MoreVerticalIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Children, type ReactNode } from 'react';

import { Button } from '@/components/ui-maia/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui-maia/dropdown-menu';

/**
 * Kolom Aksi: satu tombol yang membuka MENU AKSI menurun.
 *
 * Ada karena dua daftar sudah memuat enam tombol per baris dan tabelnya harus
 * digeser ke samping untuk mencapainya.
 *
 * Yang WAJIB dipertahankan, dan tak akan ketahuan dari test lain mana pun:
 * baris yang tak punya satu pun aksi (mis. SH/DH di Dokumen Berlaku, yang sejak
 * Fase C hanya membaca) TIDAK boleh mendapat tombol yang membuka menu kosong.
 * Diperiksa dari ISI-nya (`Children.toArray` membuang `null`/`false`), bukan
 * dengan mengulang seluruh syarat pemanggil di sini — dua daftar syarat yang
 * harus sepakat adalah dua daftar yang suatu hari tidak sepakat.
 *
 * Radix memindahkan menunya ke portal di `<body>`, jadi `overflow-x` tabel tak
 * bisa memotongnya dan pembalikan arah saat ruang bawah kurang sudah jadi
 * perilaku bawaan komponennya.
 */
export function StripAksi({ label = 'Aksi', children }: { label?: string; children: ReactNode }) {
    if (Children.toArray(children).length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                {/* Titik-titik saja, tanpa tulisan: kolomnya sudah berjudul
                    "Aksi", dan label yang diulang di setiap baris hanya
                    melebarkan kolom. Namanya tetap terbaca pembaca layar. */}
                <Button variant="ghost" size="icon" aria-label={`${label} untuk baris ini`}>
                    <HugeiconsIcon icon={MoreVerticalIcon} strokeWidth={1.5} className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                {children}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
