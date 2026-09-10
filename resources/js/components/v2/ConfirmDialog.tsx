import { useState, type ReactNode } from 'react';

import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui-maia/alert-dialog';
import { buttonVariants } from '@/components/ui-maia/button';
import { cn } from '@/lib/utils';

/**
 * Konfirmasi sebelum aksi — kembaran maia dari `components/ConfirmDialog.tsx`,
 * logikanya SALIN PERSIS. Yang berubah cuma dari kit mana komponennya diambil.
 *
 * DUA cara memakainya, dan keduanya wajib bertahan:
 *
 *   1. **Berpemicu** — oper `pemicu`; komponen ini yang memegang buka-tutupnya.
 *   2. **Dikendalikan** — oper `buka` + `onUbahBuka`, TANPA `pemicu`. Dua
 *      keadaan memaksanya ada, dan keduanya sudah pernah jadi bug:
 *      (a) dialog yang dipicu dari dalam `StripAksi` — Radix MELEPAS isi menu
 *          begitu item dipilih, jadi dialog yang dirender di dalamnya lenyap
 *          sebelum sempat terlihat; ia harus dirender di LUAR menunya dan cuma
 *          dinyalakan dari sana;
 *      (b) "formulir diisi dulu, konfirmasi menyusul saat submit" — tombolnya
 *          tetap `type="submit"` supaya `required`/`minLength` peramban tetap
 *          hidup, dan `onSubmit` yang membuka jendela ini.
 *
 * `destruktif` hanya menukar warna tombol konfirmasi. Sengaja bukan varian
 * terpisah: yang membedakan Hapus dari Kirim adalah akibatnya, bukan
 * susunannya — dan susunan yang sama membuat orang tak perlu membaca ulang
 * letak tombol tiap kali.
 */
export function ConfirmDialog({
    pemicu,
    judul = 'Konfirmasi',
    pesan,
    tombolYa = 'Ya, lanjutkan',
    tombolBatal = 'Batal',
    destruktif = false,
    buka,
    onUbahBuka,
    onKonfirmasi,
}: {
    /** Tombol pembuka. Kosongkan bila jendelanya dikendalikan dari luar. */
    pemicu?: ReactNode;
    judul?: string;
    pesan: ReactNode;
    tombolYa?: string;
    tombolBatal?: string;
    destruktif?: boolean;
    /** Kendali dari luar; bila diisi, `onUbahBuka` wajib menyertainya. */
    buka?: boolean;
    onUbahBuka?: (buka: boolean) => void;
    onKonfirmasi: () => void;
}) {
    const [bukaSendiri, setBukaSendiri] = useState(false);
    const dikendalikan = buka !== undefined;

    return (
        <AlertDialog
            open={dikendalikan ? buka : bukaSendiri}
            onOpenChange={dikendalikan ? onUbahBuka : setBukaSendiri}
        >
            {pemicu ? <AlertDialogTrigger asChild>{pemicu}</AlertDialogTrigger> : null}
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{judul}</AlertDialogTitle>
                    <AlertDialogDescription>{pesan}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{tombolBatal}</AlertDialogCancel>
                    <AlertDialogAction
                        className={cn(destruktif && buttonVariants({ variant: 'destructive' }))}
                        onClick={() => onKonfirmasi()}
                    >
                        {tombolYa}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
