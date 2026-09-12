/**
 * Validasi isian wizard — pengganti `ppValidateRequired()` + `warnKosong()`
 * yang dulu hidup di `layouts/app.blade.php` dan `edit.blade.php`.
 *
 * SENGAJA memindai DOM, bukan menelusuri state React. Dua sebabnya:
 *
 *  1. Aturannya memang tentang apa yang TERLIHAT dan BISA DISENTUH pengguna —
 *     `[inert]` (bab opsional yang dimatikan), `offsetParent === null`
 *     (tersembunyi), `disabled`. Ketiganya properti DOM, dan menirunya di state
 *     berarti menyimpan salinan kedua yang pasti menyimpang.
 *  2. Penandanya (`data-optional`, `data-pp-warn`, `data-pp-warn-bab`) datang
 *     langsung dari schema JSON dan dipasang komponen field. Dengan begitu
 *     seksi baru yang ditambahkan lewat SQL ikut tervalidasi dengan benar tanpa
 *     satu baris pun disentuh (pakem P1).
 *
 * Yang BERUBAH dari versi Blade cuma cara mengabarkannya: dulu `Swal.fire` +
 * kelas `.is-invalid` Bootstrap, sekarang `aria-invalid` (yang sudah bergaya
 * sendiri di komponen registry) + pemanggil yang memutuskan cara memberitahu.
 */

/** Satu kolom yang boleh kosong tapi kekosongannya diingatkan. */
export interface Kosong {
    el: HTMLElement;
    label: string;
}

const TERISI = (el: HTMLInputElement | HTMLTextAreaElement) => el.type !== 'file' && String(el.value).trim() !== '';

/** Kolom yang dilewati validasi: mati, opsional, tersembunyi, atau di bab inert. */
function dilewati(el: HTMLElement & { disabled?: boolean }): boolean {
    return (
        Boolean(el.disabled) ||
        el.dataset.optional !== undefined ||
        el.offsetParent === null ||
        el.closest('[inert]') !== null
    );
}

/**
 * Semua kolom WAJIB terisi?
 *
 * Menandai yang kosong dengan `aria-invalid` (dan membersihkan yang sudah
 * terisi), lalu memulangkan elemen kosong PERTAMA supaya pemanggil bisa
 * mengantarkan pengguna ke sana. `null` = semuanya beres.
 */
export function validasiWajib(root: HTMLElement): HTMLElement | null {
    let pertama: HTMLElement | null = null;

    const tandai = (el: HTMLElement, kosong: boolean) => {
        if (kosong) {
            el.setAttribute('aria-invalid', 'true');
            if (!pertama) pertama = el;
        } else {
            el.removeAttribute('aria-invalid');
        }
    };

    root.querySelectorAll<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(
        'input:not([type=hidden]):not([type=file]):not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]), textarea, select',
    ).forEach((el) => {
        if (dilewati(el)) return;
        tandai(el, !String(el.value).trim());
    });

    /*
    | Grup RADIO wajib (papan ketersediaan peninjau — FITUR-BARU-v4 §6).
    |
    | Radio dikecualikan dari pemindaian di atas karena satu grup punya banyak
    | elemen dan hanya SATU yang perlu terisi. Anggota yang TERKUNCI dilewati,
    | dan konsekuensinya disengaja: grup yang SELURUH anggotanya terkunci (semua
    | peninjau sedang off) tak ikut divalidasi, sehingga dokumen tetap bisa
    | DISIMPAN — persis jalan keluar yang ditawarkan pesan buntu di papan.
    */
    const grup: Record<string, HTMLInputElement[]> = {};
    root.querySelectorAll<HTMLInputElement>('input[type=radio][required]').forEach((el) => {
        if (el.disabled || el.offsetParent === null) return;
        (grup[el.name] ??= []).push(el);
    });

    Object.values(grup).forEach((radios) => {
        const terpilih = radios.some((el) => el.checked);
        radios.forEach((el) => tandai(el, !terpilih));
        if (!terpilih && !pertama) pertama = radios[0];
    });

    /*
    | Kolom RICH TEXT (Deskripsi Aktivitas SOP/SP/IK).
    |
    | Nilainya dipegang input HIDDEN — hidden sengaja dikecualikan di atas
    | (kolom tersembunyi milik langkah lain tak boleh ikut dituntut). Tanpa
    | bagian ini, bab wajib yang dibiarkan kosong lolos diam-diam dan penyusun
    | baru mengetahuinya setelah dokumennya dikembalikan peninjau.
    |
    | Tanda merahnya dipasang di PEMBUNGKUS: input hidden tak punya rupa, dan
    | yang dilihat penyusun adalah kotak editornya.
    */
    /*
    | Pemilih pengguna (`Select` Radix).
    |
    | Radix menukar `<select>` asli dengan tombol ber-`role`, jadi nilainya
    | tak terjangkau pemindaian di atas. Komponen `UserPicker` menyertakan
    | input HIDDEN bercermin nilai; yang ditandai merah tetap tombolnya, karena
    | itulah yang dilihat penyusun.
    */
    root.querySelectorAll<HTMLInputElement>('input[type=hidden][data-pp-pilih]').forEach((el) => {
        const kotak = el.closest<HTMLElement>('[data-pp-pilih-kotak]');
        if (!kotak || el.dataset.optional !== undefined || kotak.offsetParent === null) return;

        const pemicu = kotak.querySelector<HTMLElement>('[data-slot=select-trigger]');
        if (pemicu) tandai(pemicu, !String(el.value).trim());
    });

    root.querySelectorAll<HTMLInputElement>('input[type=hidden][data-pp-rich]').forEach((el) => {
        const kotak = el.closest<HTMLElement>('.pp-rt');
        if (!kotak || el.dataset.optional !== undefined || kotak.offsetParent === null) return;

        const kosong = !String(el.value).trim();
        kotak.classList.toggle('pp-rt-invalid', kosong);
        // `[contenteditable=true]` — bukan kelas milik editor tertentu (Quill
        // dulu `.ql-editor`, sekarang Lexical): focus() bekerja seperti pada
        // <textarea> berkat atributnya, jadi editor mana pun boleh menggantikan
        // yang ini tanpa baris ini ikut disunting.
        if (kosong && !pertama) pertama = kotak.querySelector<HTMLElement>('[contenteditable=true]') ?? kotak;
    });

    return pertama;
}

/**
 * Kolom TIDAK WAJIB yang dibiarkan kosong padahal barisnya terisi, plus bab tak
 * wajib yang dilewatkan bulat-bulat.
 *
 * Aturan "barisnya terisi" sengaja sama dengan `DocumentWizard::cleanValue()`
 * di server: baris yang seluruh kolomnya kosong memang dibuang saat disimpan,
 * jadi memperingatkan baris standby yang tak disentuh hanya akan mengganggu
 * tanpa sebab.
 */
export function warnKosong(root: HTMLElement): Kosong[] {
    // (a) Kolom kosong pada baris yang SEBAGIAN sudah terisi.
    const perKolom = Array.from(root.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('[data-pp-warn]'))
        .filter((el) => {
            if (String(el.value).trim() !== '') return false;

            const baris = el.closest('[data-pp-baris]');
            if (!baris) return false;

            return Array.from(baris.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('input, textarea')).some(
                (lain) => lain !== el && TERISI(lain),
            );
        })
        .map((el) => ({ el: el as HTMLElement, label: el.getAttribute('data-pp-warn') ?? '' }));

    // (b) Bab tak wajib yang dilewatkan BULAT-BULAT. Tanpa ini ia lolos tanpa
    //     peringatan: aturan (a) hanya menyala pada baris yang sudah tersentuh,
    //     dan bab yang kosong seluruhnya tak punya baris seperti itu.
    const perBab = Array.from(root.querySelectorAll<HTMLElement>('[data-pp-warn-bab]'))
        .filter(
            (bab) =>
                !Array.from(bab.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('input, textarea')).some(TERISI),
        )
        .map((bab) => ({
            el: bab.querySelector<HTMLElement>('input, textarea'),
            label: `${bab.getAttribute('data-pp-warn-bab')} (belum diisi sama sekali)`,
        }))
        // Bab yang sakelarnya DIMATIKAN tak diperingatkan: pembuatnya sudah
        // menyatakan tak memakainya, dan babnya memang tak akan tercetak.
        .filter((w): w is Kosong => w.el !== null && w.el.offsetParent !== null && !w.el.closest('[inert]'));

    return [...perBab, ...perKolom];
}

/** Antar pengguna ke kolom yang bermasalah. */
export function antarKe(el: HTMLElement): void {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => {
        try {
            el.focus({ preventScroll: true });
        } catch {
            /* elemen yang tak bisa difokus — cukup tergulir ke sana */
        }
    }, 250);
}
