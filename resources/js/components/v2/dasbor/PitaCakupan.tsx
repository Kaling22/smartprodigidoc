import { Progress } from '@/components/ui-maia/progress';
import { cn } from '@/lib/utils';
import type { Cakupan } from '@/types/dasbor';

/**
 * PITA CAKUPAN — kembaran maia; ambang warnanya SALIN PERSIS dari versi nova.
 *
 *   < 50%  merah   — belum sampai; perlu ditindaklanjuti
 *   50-79% kuning  — sedang berjalan
 *   >= 80% hijau   — sudah tersebar
 *
 * Merah → kuning → hijau naik searah "makin baik", jadi warnanya sendiri sudah
 * memberi tahu apakah angkanya cukup. Ambang yang berbeda antar halaman
 * membuat orang berdebat soal warna, bukan soal dokumennya — karena itu satu
 * komponen melayani tiga tempat, dan angkanya tak boleh menyimpang di sini.
 *
 * Angka disebutkan APA ADANYA di sebelah pita ("12 dari 34 orang"). Persen saja
 * menipu di departemen kecil: 1 dari 2 orang bukan "50% tersebar".
 *
 * `ringkas` → hanya pita + persen, untuk sel tabel sempit.
 */
export function PitaCakupan({ c, ringkas = false }: { c: Cakupan; ringkas?: boolean }) {
    const persen = c.sasaran > 0 ? c.persen : 0;

    // Warna ditumpangi dari luar lewat `[&>[data-slot=progress-indicator]]` —
    // komponen registry tak disunting (pakem P9, CLAUDE.md §4).
    const rona =
        c.sasaran === 0
            ? '[&>[data-slot=progress-indicator]]:bg-muted-foreground/40'
            : persen < 50
              ? '[&>[data-slot=progress-indicator]]:bg-destructive'
              : persen < 80
                ? '[&>[data-slot=progress-indicator]]:bg-chart-3'
                : '[&>[data-slot=progress-indicator]]:bg-chart-5';

    return (
        <div className="flex items-center gap-2">
            <Progress
                // Pembaca bisa MELEBIHI sasaran (mis. peninjau dari departemen
                // lain ikut membuka), dan pita yang meluber keluar kartunya
                // terlihat seperti bug.
                value={Math.min(100, persen)}
                className={cn('h-1.5 min-w-10 flex-1', rona)}
                aria-label={`Cakupan ${persen} persen, ${c.pembaca} dari ${c.sasaran} orang`}
            />
            {c.sasaran === 0 ? (
                <span className="text-muted-foreground shrink-0 text-xs whitespace-nowrap">tak ada sasaran</span>
            ) : (
                <span className="shrink-0 text-xs whitespace-nowrap tabular-nums">
                    <strong>{persen}%</strong>
                    {!ringkas && (
                        <span className="text-muted-foreground">
                            {' '}
                            · {c.pembaca} dari {c.sasaran} orang
                        </span>
                    )}
                </span>
            )}
        </div>
    );
}
