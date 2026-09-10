/**
 * Catatan yang menempel pada SATU item dokumen, digambar tepat di bawah isiannya.
 *
 * Presentasional murni — nol context, nol permintaan. Datanya diambil pemanggil
 * dari `useWizard().catatanItem[section.key]?.[ref]`, sebab dua pemakainya
 * (komponen field wizard) sudah berada di dalam penyedia yang berbeda-beda
 * kedalamannya, dan komponen yang membaca context-nya sendiri akan mengunci
 * berkas ini pada satu layar saja.
 *
 * Rupanya sengaja seirama dengan `CatatanLama` di layar tinjau
 * (`components/v2/tinjau/SeksiTinjau.tsx`): peninjau menulis catatannya di kotak
 * yang letaknya persis di titik ini, jadi pembuat menemukannya di tempat yang
 * sama saat merevisi.
 */
import { Message01Icon, UserMultipleIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Badge } from '@/components/ui-maia/badge';
import type { CatatanBaris } from '@/types/wizard';

const SEJAWAT = 'sejawat';

/**
 * Apakah teks yang hidup sekarang masih item YANG SAMA dengan yang dikomentari?
 *
 * `item_ref` berbasis POSISI, dan pembuat bebas menyisipkan atau menghapus baris
 * selagi merevisi — catatan atas baris ke-3 lalu menempel di baris ke-3 yang
 * BARU. Kutipan dari server adalah satu-satunya cara ketidakcocokan itu terlihat.
 *
 * Perbandingannya sengaja longgar (`includes`, bukan sama persis): kutipan
 * dipotong 80 karakter di server, dan pembuat yang baru menambah satu kata di
 * ujung kalimat tak sedang melihat baris yang keliru.
 */
function bergeser(kutipan: string | null, nilaiKini?: string): boolean {
    if (!kutipan || nilaiKini === undefined) return false;

    const bersih = nilaiKini.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

    return bersih !== '' && !bersih.includes(kutipan.replace(/\.\.\.$/, '').trim());
}

export function CatatanItem({ daftar, nilaiKini }: { daftar?: CatatanBaris[]; nilaiKini?: string }) {
    if (!daftar?.length) return null;

    return (
        <div className="border-destructive/30 bg-destructive/5 mt-1.5 space-y-1.5 rounded-lg border border-dashed px-2.5 py-2">
            {daftar.map((c, i) => {
                const sejawat = c.sumber === SEJAWAT;

                return (
                    // Kunci indeks aman: daftar ini hanya dibaca, tak pernah
                    // disunting atau diurut ulang di peramban.
                    // eslint-disable-next-line react/no-array-index-key
                    <div key={i} className="text-xs">
                        <div className="flex flex-wrap items-center gap-1.5">
                            <Badge variant={sejawat ? 'secondary' : 'destructive'}>
                                <HugeiconsIcon icon={sejawat ? UserMultipleIcon : Message01Icon} strokeWidth={1.5} />
                                {sejawat ? 'Sejawat' : 'Peninjau'}
                            </Badge>
                            <span className="text-muted-foreground">{c.oleh}</span>
                        </div>

                        <p className="mt-1 whitespace-pre-line">{c.komentar}</p>

                        {bergeser(c.kutipan, nilaiKini) ? (
                            <p className="text-muted-foreground mt-0.5 italic">
                                Saat dikomentari, isinya: “{c.kutipan}”
                            </p>
                        ) : null}
                    </div>
                );
            })}
        </div>
    );
}
