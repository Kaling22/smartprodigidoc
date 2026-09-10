import { Badge } from '@/components/ui-maia/badge';
import { Ikon } from '@/components/v2/Ikon';
import { cn } from '@/lib/utils';
import type { BarisDokumen } from '@/types/dokumen';

/**
 * Sel "No. Dokumen" — kembaran maia.
 *
 * Dua lencana yang sengaja dipisah, karena keduanya menjawab pertanyaan yang
 * berbeda: **Arsip** = dokumen ini berupa PDF unggahan, bukan susunan wizard —
 * tombol PDF-nya menyajikan berkas asli. **Nomor Lama** = nomornya di luar pola
 * `{PREFIX}-{JENIS}-{DEPT}-{NN}` dan itu memang diterima apa adanya. Dokumen
 * arsip bernomor sesuai pola hanya mendapat yang pertama; dokumen SmartPro
 * bernomor manual yang ganjil hanya mendapat yang kedua.
 *
 * `dicoret` dipakai halaman Tidak Berlaku: nomor yang sudah DILEPAS tetap
 * tertulis supaya arsipnya terbaca, tapi ia sudah kembali ke kolam — dua
 * dokumen boleh sah memegangnya, jadi ia tak lagi menunjuk dokumen ini.
 */
export function NomorDokumen({
    doc,
    dicoret = false,
}: {
    doc: Pick<BarisDokumen, 'nomor' | 'arsip' | 'nomor_luar_pola'>;
    dicoret?: boolean;
}) {
    return (
        <div className="flex flex-wrap items-center gap-1">
            <span className={cn('font-mono text-sm', dicoret && 'line-through')}>{doc.nomor}</span>
            {doc.arsip ? (
                <Badge variant="secondary" title="Dokumen lama — PDF diunggah apa adanya">
                    <Ikon nama="bi-archive" className="size-3" />
                    Arsip
                </Badge>
            ) : null}
            {doc.nomor_luar_pola ? (
                <Badge variant="outline" title="Nomor di luar pola resmi">
                    Nomor Lama
                </Badge>
            ) : null}
        </div>
    );
}
