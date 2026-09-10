import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui-maia/hover-card';
import { Avatar } from '@/components/v2/Avatar';

/**
 * Tumpukan wajah pembaca — empat wajah, sisanya jadi "+N".
 *
 * Tiap wajah dibatasi cincin sewarna kartu supaya batas antar-wajah tetap
 * terbaca di terang maupun gelap. Memakai `v2/Avatar` yang sama dengan topbar
 * dan kolom Pembuat, jadi seseorang tampak serupa di mana pun ia muncul — dan
 * yang belum berfoto dapat glif orang, tak pernah inisial.
 *
 * Beda dari versi nova: NAMANYA bisa dibaca. Dulu deretan wajah tanpa foto
 * semuanya glif orang yang identik, sehingga "siapa saja yang sudah membaca"
 * — satu-satunya pertanyaan yang dijawab kolom ini — justru tak terjawab.
 * `HoverCard` menampung seluruh daftar, termasuk yang tersembunyi di balik
 * "+N", jadi tak ada nama yang hanya bisa dilihat di halaman lain.
 *
 * DUA prop opsional ditambahkan untuk kartu Performa PIC (§5d) — dipakai ulang
 * alih-alih digambar ulang, dan keduanya berdefault ke perilaku lama sehingga
 * seluruh pemanggil yang ada tak berubah satu karakter pun:
 *
 *  • `satuan` — kata benda di kepala HoverCard ("5 pembaca" / "12 PIC").
 *  • `ket` per orang — keterangan kanan di daftarnya (mis. jumlah dokumen yang
 *    ia gerakkan). Kosong = barisnya cuma nama, persis seperti dulu.
 */
export function TumpukanWajah({
    pembaca,
    satuan = 'pembaca',
}: {
    pembaca: { nama: string; foto: string | null; ket?: string }[];
    satuan?: string;
}) {
    if (pembaca.length === 0) {
        return <span className="text-muted-foreground text-xs">belum ada</span>;
    }

    const sisa = pembaca.length - 4;

    return (
        <HoverCard openDelay={120}>
            <HoverCardTrigger asChild>
                <div className="flex w-fit items-center" tabIndex={0}>
                    {pembaca.slice(0, 4).map((p, i) => (
                        <Avatar
                            key={`${p.nama}-${i}`}
                            nama={p.nama}
                            foto={p.foto}
                            className="ring-card -ml-2 size-7 ring-2 first:ml-0"
                        />
                    ))}
                    {sisa > 0 && (
                        <span className="bg-muted ring-card -ml-2 flex size-7 items-center justify-center rounded-full text-[0.625rem] font-semibold ring-2">
                            +{sisa}
                        </span>
                    )}
                </div>
            </HoverCardTrigger>
            <HoverCardContent align="start" className="w-56">
                <p className="mb-2 text-xs font-semibold">
                    {pembaca.length} {satuan}
                </p>
                <ul className="space-y-1.5">
                    {pembaca.map((p, i) => (
                        <li key={`${p.nama}-${i}`} className="flex items-center gap-2 text-sm">
                            <Avatar nama={p.nama} foto={p.foto} className="size-6" />
                            <span className="truncate">{p.nama}</span>
                            {p.ket ? (
                                <span className="text-muted-foreground ml-auto shrink-0 text-xs tabular-nums">
                                    {p.ket}
                                </span>
                            ) : null}
                        </li>
                    ))}
                </ul>
            </HoverCardContent>
        </HoverCard>
    );
}
