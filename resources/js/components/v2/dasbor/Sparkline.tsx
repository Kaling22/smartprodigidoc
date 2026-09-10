/**
 * Sparkline delapan titik bulanan — SVG telanjang, bukan recharts.
 *
 * PENYIMPANGAN dari registry, dan alasannya (CLAUDE.md §13): blok
 * `section-cards.tsx` tak punya sparkline sama sekali, jadi tak ada susunan
 * resmi untuk disalin. Yang tersedia di proyek adalah `ui-maia/chart` +
 * recharts, dipakai `GrafikOverview` — tapi memasangnya di sini berarti empat
 * `ResponsiveContainer` yang masing-masing memasang `ResizeObserver` sendiri,
 * demi delapan angka tanpa sumbu, tanpa tooltip, dan tanpa legenda. Satu
 * `<polyline>` mengerjakan hal yang sama dalam belasan baris dan nol pengamat.
 *
 * `preserveAspectRatio="none"` membuatnya melar mengikuti lebar kartu (sel
 * ELASTIS, CLAUDE.md §6); `vector-effect="non-scaling-stroke"` menjaga garisnya
 * tetap setebal satu piksel meski kotaknya diregangkan.
 *
 * Deret RATA (semua nilai sama, termasuk semua nol) digambar sebagai garis
 * tengah, bukan dibagi nol: bulan tanpa aktivitas adalah informasi, dan
 * kartunya tetap harus punya bentuk.
 */
export function Sparkline({ deret, className }: { deret: number[]; className?: string }) {
    if (deret.length < 2) {
        return null;
    }

    const min = Math.min(...deret);
    const max = Math.max(...deret);
    const rentang = max - min;
    const x = (i: number) => (i / (deret.length - 1)) * 100;
    const y = (n: number) => (rentang === 0 ? 16 : 30 - ((n - min) / rentang) * 28);

    const titik = deret.map((n, i) => `${x(i)},${y(n)}`).join(' ');

    return (
        <svg
            viewBox="0 0 100 32"
            preserveAspectRatio="none"
            aria-hidden="true"
            className={className}
        >
            {/* Bidang di bawah garis ditutup ke DASAR kotak, bukan ke garis
                terendah: bidang yang mengambang tanpa alas terbaca seperti pita,
                bukan seperti luasan. */}
            <polygon points={`0,32 ${titik} 100,32`} className="fill-primary/15" />
            <polyline
                points={titik}
                fill="none"
                strokeWidth={1.5}
                vectorEffect="non-scaling-stroke"
                strokeLinejoin="round"
                strokeLinecap="round"
                className="stroke-primary"
            />
        </svg>
    );
}
