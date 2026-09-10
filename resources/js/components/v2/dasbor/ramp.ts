/**
 * Ramp SATU HUE (`--chart-1`, merah PPA) tua→muda — SATU sumber untuk kedua
 * kartu bar bertumpuk di baris kelima dashboard.
 *
 * `PitaDepartemen` (PJO/MD/Admin) dan `SebaranJenisDept` (SH/DH) adalah kartu
 * yang sama bagi mata pemilik: satu-satunya bedanya sumbu datanya. Pemilik
 * menuntut keduanya identik "dari warna, jarak, font apapun itu"
 * (2026-09-07), dan dua salinan formula adalah cara paling gampang keduanya
 * menyimpang tanpa satu pun gerbang berbunyi — pergeseran warna tak pernah
 * membuat `tsc` maupun tes merah.
 *
 * Keputusan pemilik W-2 (TEMUAN-F8 F2a) MENCABUT §P5/§6 REDESAIN-UI-V2 untuk
 * kedua kartu ini: palet kategorikal wajib saat warna adalah SATU-SATUNYA
 * pembeda, sedangkan di sini legendanya menyebut nama tiap segmen di
 * sampingnya. `color-mix` dipakai sebab mekanismenya identik dengan donat
 * `KartuSebaran` (keputusan D2/F5) — tiga kartu bersebelahan karena itu
 * berbicara satu bahasa warna, dan nol hex diketik di TSX.
 *
 * Rentangnya TETAP 90%→30% berapa pun jumlah langkahnya, jadi kartu berenam
 * segmen dan kartu bertujuh segmen tetap terlihat sekeluarga.
 *
 * Langkahnya BERKUNCI URUTAN KUNCI, bukan peringkat jumlah: segmen pertama
 * selalu yang tergelap walau angkanya kebetulan nol, sehingga warnanya tak
 * berpindah tiap data berubah.
 */
export function rampMerah(indeks: number, jumlah: number): string {
    const persen = jumlah > 1 ? 90 - indeks * (60 / (jumlah - 1)) : 90;

    return `color-mix(in oklab, var(--chart-1) ${persen}%, var(--card))`;
}
