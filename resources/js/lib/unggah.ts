/**
 * Unggah foto lampiran — SATU jalur, dipakai kolom `image` maupun editor
 * `rich_text`.
 *
 * Di Blade jalur ini tersalin dua kali (`up()` di `_repeatable_group` dan
 * `kirim()` di komponen `richText`), dan keduanya sudah sempat berbeda:
 * yang satu memperkecil gambar lebih dulu, yang satu tidak. Di sini
 * pengecilannya jadi langkah TERPISAH yang dipanggil sadar — bukan diam-diam
 * berlaku pada semua orang.
 *
 * Tujuannya tetap `documents.uploadAttachment`, yang menyimpan berkas ke
 * `storage/app/public/lampiran/{DEPT}/{JENIS}/` dan mencatatnya di tabel
 * `attachments`. Yang dipulangkan hanya JALURNYA; jalur itulah yang tersimpan
 * di `value_json`, bukan base64 — base64 ikut terkirim di SETIAP autosave dan
 * menggembungkan isi dokumen berlipat-lipat.
 */

export interface HasilUnggah {
    path: string | null;
    galat: string | null;
}

/**
 * Token diambil dari cookie `XSRF-TOKEN`, BUKAN dari <meta>: meta dirender
 * sekali saat muat penuh dan tak pernah disegarkan di SPA, sementara
 * LoginController memutar token sesi saat login. Cookie-nya ditulis ulang
 * Laravel di SETIAP respons, jadi ia satu-satunya sumber yang selalu segar.
 */
export function xsrf(): string {
    const m = /(?:^|;\s*)XSRF-TOKEN=([^;]*)/.exec(document.cookie);
    return m ? decodeURIComponent(m[1]) : '';
}

export async function unggahGambar(url: string, berkas: File | Blob, sectionKey: string): Promise<HasilUnggah> {
    const fd = new FormData();
    fd.append('image', berkas);
    fd.append('section', sectionKey);

    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': xsrf(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: fd,
        });
        const j = await r.json();

        if (r.ok && j.path) return { path: j.path, galat: null };

        return { path: null, galat: j.message ?? 'Gagal mengunggah gambar.' };
    } catch {
        return { path: null, galat: 'Gagal mengunggah gambar.' };
    }
}

/**
 * Perkecil sebelum dikirim.
 *
 * Servernya menerima JPG/PNG maksimal 5MB, dan tangkapan layar 4K menembus
 * batas itu dengan mudah — tanpa langkah ini penyusun cuma melihat "Gagal
 * mengunggah gambar" tanpa tahu sebabnya. Gambar yang sudah kecil DIBIARKAN apa
 * adanya supaya PNG tajam (tangkapan layar berteks) tak dirusak kompresi JPEG
 * sia-sia.
 *
 * 1600px cukup: di PDF gambarnya toh dibatasi `.akt-img` (maks 220pt tinggi,
 * selebar kolom AKTIVITAS), jadi piksel di atas itu tak pernah terlihat.
 */
export function kecilkan(berkas: File): Promise<File | Blob> {
    const MAKS = 1600;
    const sudahBenar = /^image\/(png|jpe?g)$/i.test(berkas.type);

    return new Promise((selesai) => {
        const url = URL.createObjectURL(berkas);
        const img = new Image();

        img.onload = () => {
            URL.revokeObjectURL(url);

            if (sudahBenar && img.width <= MAKS && berkas.size <= 1_500_000) {
                return selesai(berkas);
            }

            const skala = Math.min(1, MAKS / img.width);
            const c = document.createElement('canvas');
            c.width = Math.round(img.width * skala);
            c.height = Math.round(img.height * skala);

            const ctx = c.getContext('2d');
            if (!ctx) return selesai(berkas);

            // Latar putih: PNG transparan yang jadi JPEG akan hitam pekat.
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, c.width, c.height);
            ctx.drawImage(img, 0, 0, c.width, c.height);

            c.toBlob((b) => selesai(b ? new File([b], 'gambar.jpg', { type: 'image/jpeg' }) : berkas), 'image/jpeg', 0.9);
        };

        // Berkas yang tak bisa dibaca peramban dikirim apa adanya — biarkan
        // server yang menolak dengan pesannya sendiri.
        img.onerror = () => {
            URL.revokeObjectURL(url);
            selesai(berkas);
        };

        img.src = url;
    });
}

/** data URI → File, supaya tempelan bisa diunggah seperti berkas pilihan biasa. */
export function keBerkas(src: string | null): File | null {
    const m = /^data:(image\/(?:png|jpe?g|gif|webp|bmp));base64,([\s\S]+)$/i.exec(src ?? '');
    if (!m) return null;

    try {
        const bin = atob(m[2]);
        const buf = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);

        return new File([buf], 'tempelan', { type: m[1] });
    } catch {
        return null;
    }
}
