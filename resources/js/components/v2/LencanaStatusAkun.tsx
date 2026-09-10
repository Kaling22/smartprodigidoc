/**
 * KEMBARAN MAIA dari `resources/js/components/LencanaStatusAkun.tsx`
 * (PATOKAN-GAYA-V2 Fase 1).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser. Yang berubah hanya
 * dari kit mana komponennya diambil (`ui` → `ui-maia`). Berkas aslinya sengaja
 * TIDAK disentuh: halaman lama masih memakainya sebagai pembanding selama
 * jendela pratinjau. Fase 7 menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 */
import type { ComponentProps } from 'react';

import { Badge } from '@/components/ui-maia/badge';

/**
 * Status AKUN (`users.status`) — bukan status dokumen.
 *
 * Sengaja terpisah dari `StatusBadge`: yang itu membaca `Document::STATUS_META`
 * dari props dan tak boleh dicampuri, sementara kolom `users.status` cuma
 * mengenal tiga nilai dan tak punya peta di server sama sekali. Petanya ditulis
 * SEKALI di sini — dipakai daftar user & halaman Informasi Akun — supaya tak
 * lahir dua daftar warna yang suatu hari berbeda, persis kesalahan yang sudah
 * mahal dibayar `STATUS_META` dulu.
 *
 * Nilainya dicetak apa adanya (huruf awal besar). `rejected` di sini bermakna
 * GANDA — pendaftaran ditolak ATAU akun dinonaktifkan (lihat komentar
 * `UserManagementController::saklarStatus`) — jadi menamainya ulang di layar
 * akan salah pada salah satu dari keduanya.
 */
const VARIAN: Record<string, ComponentProps<typeof Badge>['variant']> = {
    active: 'default',
    pending: 'secondary',
    rejected: 'destructive',
};

export function LencanaStatusAkun({ status }: { status: string }) {
    return (
        <Badge variant={VARIAN[status] ?? 'outline'} className="capitalize">
            {status}
        </Badge>
    );
}
