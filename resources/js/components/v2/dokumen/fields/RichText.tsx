/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/RichText.tsx` (REDESAIN-UI-V2 T2).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser. Yang berubah hanya
 * dari kit mana komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya
 * (lucide → hugeicons, §5). Berkas aslinya sengaja TIDAK disentuh: 41 halaman
 * lama masih memakainya sebagai pembanding selama jendela pratinjau. Tranche 4
 * menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 */
import type Quill from 'quill';
import { EraserIcon, Image01Icon, LeftToRightListBulletIcon, LeftToRightListNumberIcon, QuoteDownIcon, TextBoldIcon, TextItalicIcon, TextUnderlineIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useEffect, useRef, useState } from 'react';

import { kecilkan, keBerkas, unggahGambar } from '@/lib/unggah';
import type { BidangSchema } from '@/types/wizard';

import { useWizard } from './konteks';

/**
 * Kolom BERFORMAT (Quill 2 "snow" bertema SmartPro) — pengganti
 * `documents/fields/_rich_text.blade.php` beserta komponen Alpine `richText`.
 *
 * Dipakai kolom "Deskripsi Aktivitas" pada SOP/SP/IK, dan sengaja dibuat
 * sebagai TIPE FIELD, bukan tipe section: perbedaan antar-jenis dokumen datang
 * dari schema (CLAUDE.md §7), jadi satu kunci schema `rich_text` menyalakan
 * editor ini di ketiga jenis sekaligus — tanpa komponen baru.
 *
 * TIGA hal yang wajib bertahan dari versi Alpine:
 *
 *  1. **Quill TIDAK memegang nilainya.** Sumber kebenarannya tetap nilai seksi
 *     di React, dan komponen ini hanya menyalin ke sana tiap kali isinya
 *     berubah. Karena itu kolom ini terkirim, ter-autosave, dan tervalidasi
 *     persis seperti `<textarea>` yang digantikannya — nol jalur penyimpanan
 *     kedua.
 *  2. **Input HIDDEN ber-`data-pp-rich`.** `validasiWajib()` memindainya;
 *     tanpa itu bab wajib yang kosong lolos diam-diam.
 *  3. **Foto tempelan ditukar jadi berkas sungguhan.** Quill menyisipkan
 *     Ctrl+V sebagai `data:image/png;base64,…`, dan `PembersihHtml` membuang
 *     src semacam itu — fotonya tampak baik di editor lalu lenyap di PDF.
 *
 * Toolbar-nya digambar React (ikon hugeicons, REDESAIN-UI-V2 §5) lalu
 * DISERAHKAN ke Quill sebagai container. Quill mengikat tombol lewat kelas
 * `ql-*` dan memasang `ql-active` sendiri, jadi tak ada satu pun keadaan
 * toolbar yang perlu diurus di sini — sekaligus menghapus tambalan
 * `Quill.import('ui/icons')` yang dulu menukar SVG bawaan satu per satu.
 *
 * Sependek ini dengan sengaja: tiap tombol WAJIB punya padanan di jalur cetak
 * DomPDF (`PembersihHtml` + `ActivityPrintLayout`). Warna, ukuran huruf, dan
 * tabel tak dipasang karena tak pernah sampai ke PDF.
 */
export function RichText({
    value,
    onChange,
    sectionKey,
    field,
    nama,
    opsional,
    peringatan,
}: {
    value: string;
    onChange: (html: string) => void;
    /** Kunci SEKSI (mis. `aktivitas`) — dipakai sebagai `section` saat unggah. */
    sectionKey: string;
    field: BidangSchema;
    nama: string;
    opsional: boolean;
    peringatan: string | null;
}) {
    const { documentId, editable } = useWizard();

    const kotakEditor = useRef<HTMLDivElement>(null);
    const kotakToolbar = useRef<HTMLDivElement>(null);
    const berkasRef = useRef<HTMLInputElement>(null);
    const quill = useRef<Quill | null>(null);
    const menyapu = useRef(false);

    const [mengunggah, setMengunggah] = useState(false);
    const [galat, setGalat] = useState('');

    // Nilai & penangan TERBARU disimpan di ref: Quill dipasang SEKALI, dan
    // efeknya tak boleh ikut dipasang ulang tiap ketikan — memasang ulang
    // berarti editor dibangun dari nol dan kursor melompat ke awal.
    const nilaiRef = useRef(value);
    const onChangeRef = useRef(onChange);
    nilaiRef.current = value;
    onChangeRef.current = onChange;

    const urlUnggah = route('documents.uploadAttachment', documentId);

    useEffect(() => {
        let dibuang = false;

        // `import()` DINAMIS, bukan import biasa di kepala berkas: Quill
        // menyentuh `document` saat dimuat, dan gerbang `smartpro:uji-render`
        // merender halaman ini di Node. `useEffect` tak pernah jalan di sana,
        // jadi Quill tak pernah ikut dimuat.
        void import('quill').then(({ default: Quill }) => {
            if (dibuang || !kotakEditor.current || !kotakToolbar.current) return;

            const q = new Quill(kotakEditor.current, {
                theme: 'snow',
                readOnly: !editable,
                placeholder: field.placeholder ?? '',
                modules: {
                    toolbar: {
                        container: kotakToolbar.current,
                        handlers: { image: () => berkasRef.current?.click() },
                    },
                },
            });

            quill.current = q;
            muat(q, nilaiRef.current);
            q.on('text-change', () => sinkron(q));
        });

        return () => {
            dibuang = true;
            quill.current = null;
        };
        // Sekali seumur hidup komponen. Barisnya dijaga kunci baris di
        // RepeatableGroup: menghapus baris ke-1 tak boleh membuat editor baris
        // ke-2 dipakai ulang, karena Quill memegang DOM-nya sendiri dan akan
        // menampilkan tulisan baris yang salah.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    /**
     * Salin isi editor ke nilai seksi.
     *
     * Editor yang dikosongkan menghasilkan `<p><br></p>`; itu dinolkan di sini
     * juga (bukan hanya di server) supaya penanda wajib-diisi ikut menyala
     * seketika.
     */
    const sinkron = (q: Quill) => {
        const kosong = q.getText().trim() === '' && !q.root.querySelector('img');
        onChangeRef.current(kosong ? '' : q.root.innerHTML);
        void sapuTempelan(q);
    };

    /**
     * Foto yang DITEMPEL (Ctrl+V) atau diseret ke dalam editor.
     *
     * Quill menyisipkannya sebagai `data:image/png;base64,…` — bukan berkas.
     * Kalau dibiarkan, fotonya HILANG dari PDF: sanitizer hanya menerima src
     * yang menunjuk `lampiran/{DEPT}/{JENIS}/`.
     *
     * Melonggarkan sanitizer BUKAN jawabannya: base64 akan ikut terkirim di
     * SETIAP autosave dan menggembungkan `value_json` berlipat-lipat — satu
     * tangkapan layar bisa 400KB, dikirim ulang tiap 1,2 detik selama penyusun
     * mengetik.
     *
     * Diambil SATU per satu, bukan sebagai daftar yang disnapshot di muka:
     * setiap penukaran menggeser indeks Quill DAN membuatnya menggambar ulang,
     * sehingga acuan `<img>` ke-2 dan seterusnya sudah basi. Batas 20 putaran
     * menjaga dari gelung tak berujung bila ada gambar yang tak bisa
     * disingkirkan.
     */
    const sapuTempelan = async (q: Quill) => {
        if (menyapu.current) return;
        if (!q.root.querySelector('img[src^="data:"]')) return;

        menyapu.current = true;

        try {
            let img: HTMLImageElement | null;
            let jaga = 0;

            while ((img = q.root.querySelector<HTMLImageElement>('img[src^="data:"]')) && jaga++ < 20) {
                const berkas = keBerkas(img.getAttribute('src'));
                const jalur = berkas ? await kirim(berkas) : null;

                // Ditukar lewat API Quill, bukan setAttribute: model internal
                // Quill masih memegang data URI-nya, dan suntingan berikutnya
                // akan menggambar ulang dari model itu — mengembalikan base64
                // yang barusan kita singkirkan.
                const blot = (await import('quill')).default.find(img);
                const di = blot && 'length' in blot ? q.getIndex(blot as never) : null;

                if (di === null) {
                    // Tak dikenali Quill (mis. tempelan HTML mentah) — DOM-nya
                    // saja yang disentuh.
                    if (jalur) img.setAttribute('src', '/storage/' + jalur);
                    else img.remove();
                    continue;
                }

                q.deleteText(di, 1, 'silent');
                if (jalur) sisipkan(q, '/storage/' + jalur, di, 'silent');
            }
        } finally {
            menyapu.current = false;
        }

        sinkron(q);
    };

    /** SATU-SATUNYA jalur unggah — dipakai tombol gambar maupun jaring tempelan. */
    const kirim = async (berkas: File): Promise<string | null> => {
        setGalat('');
        setMengunggah(true);

        const hasil = await unggahGambar(urlUnggah, await kecilkan(berkas), sectionKey);

        setMengunggah(false);
        if (hasil.galat) setGalat(hasil.galat);

        return hasil.path;
    };

    /** Tombol gambar di toolbar. */
    const pilihBerkas = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const berkas = e.target.files?.[0];
        e.target.value = '';
        if (!berkas || !quill.current) return;

        const jalur = await kirim(berkas);
        if (jalur) sisipkan(quill.current, '/storage/' + jalur);
    };

    return (
        <>
            <div className="pp-rt">
                {/* Pembawa nilai sesungguhnya. `data-pp-rich` dibaca
                    validasiWajib(); hidden dikecualikan dari pemindaian biasa,
                    jadi tanpa penanda ini kolom wajib yang kosong lolos. */}
                <input
                    type="hidden"
                    data-pp-rich
                    {...(opsional ? { 'data-optional': '' } : {})}
                    {...(peringatan ? { 'data-pp-warn': peringatan } : {})}
                    name={nama}
                    value={value || ''}
                    readOnly
                />

                <div ref={kotakToolbar} className="ql-toolbar ql-snow">
                    <span className="ql-formats">
                        <button type="button" className="ql-bold" title="Tebal">
                            <HugeiconsIcon icon={TextBoldIcon} strokeWidth={1.5} className="size-4"  />
                        </button>
                        <button type="button" className="ql-italic" title="Miring">
                            <HugeiconsIcon icon={TextItalicIcon} strokeWidth={1.5} className="size-4"  />
                        </button>
                        <button type="button" className="ql-underline" title="Garis bawah">
                            <HugeiconsIcon icon={TextUnderlineIcon} strokeWidth={1.5} className="size-4"  />
                        </button>
                    </span>
                    <span className="ql-formats">
                        <button type="button" className="ql-blockquote" title="Kutipan">
                            <HugeiconsIcon icon={QuoteDownIcon} strokeWidth={1.5} className="size-4"  />
                        </button>
                    </span>
                    <span className="ql-formats">
                        <button type="button" className="ql-list" value="ordered" title="Daftar bernomor">
                            <HugeiconsIcon icon={LeftToRightListNumberIcon} strokeWidth={1.5} className="size-4"  />
                        </button>
                        <button type="button" className="ql-list" value="bullet" title="Daftar berbutir">
                            <HugeiconsIcon icon={LeftToRightListBulletIcon} strokeWidth={1.5} className="size-4"  />
                        </button>
                    </span>
                    <span className="ql-formats">
                        <button type="button" className="ql-image" title="Gambar">
                            <HugeiconsIcon icon={Image01Icon} strokeWidth={1.5} className="size-4"  />
                        </button>
                        <button type="button" className="ql-clean" title="Bersihkan format">
                            <HugeiconsIcon icon={EraserIcon} strokeWidth={1.5} className="size-4"  />
                        </button>
                    </span>
                </div>

                {/* Quill MENGGANTI simpul ini dengan `.ql-container` miliknya,
                    jadi ia harus kosong dan tak diurus React sama sekali. */}
                <div ref={kotakEditor} />
            </div>

            {mengunggah ? <p className="mt-1 text-xs text-primary">Mengunggah gambar…</p> : null}
            {galat ? <p className="mt-1 text-xs text-destructive">{galat}</p> : null}

            {/* Berkas dipilih lewat input tersembunyi milik komponen, bukan
                lewat dialog bawaan Quill: unggahannya harus melewati
                documents.uploadAttachment supaya foto tersimpan sebagai BERKAS
                di lampiran/{DEPT}/{JENIS}/. */}
            <input
                ref={berkasRef}
                type="file"
                className="hidden"
                accept={field.image_accept ?? 'image/jpeg,image/png'}
                onChange={pilihBerkas}
            />
        </>
    );
}

/**
 * Isi awal.
 *
 * Dokumen LAMA tersimpan sebagai teks polos bernewline. Kalau ia ditempel
 * sebagai HTML, newline-nya lenyap dan seluruh langkah menyatu jadi satu
 * paragraf — tata letak SOP berjalan berubah hanya karena dokumennya dibuka.
 * `setText()` mempertahankan barisnya.
 */
function muat(q: Quill, nilai: string): void {
    if (!nilai) return;

    if (/<\/?(p|br|strong|em|u|b|i|ol|ul|li|img|blockquote)\b/i.test(nilai)) {
        q.clipboard.dangerouslyPasteHTML(nilai, 'silent');
    } else {
        q.setText(nilai, 'silent');
    }
}

/**
 * Sisipkan gambar sebagai BARISNYA SENDIRI.
 *
 * Blot `image` bawaan Quill bersifat INLINE — disisipkan begitu saja, ia duduk
 * berdampingan dengan kalimat di sekitarnya, dan di dalam tabel AKTIVITAS yang
 * sempit hasilnya berantakan. Permintaan pemilik tegas: gambar selalu di bawah
 * tulisan, tak pernah di sampingnya. Jalur cetak sudah sejalan —
 * `PembersihHtml::blok()` menjadikan tiap gambar satu baris tabel tersendiri.
 *
 * Format BLOK baris ikut dibawa, dan inilah yang dulu salah: di Quill, format
 * blok (list/blockquote/indent) TIDAK menempel pada barisnya melainkan pada
 * karakter `\n` PENUTUP baris itu. `\n` polos yang disisipkan sesudah gambar
 * karena itu menjadi penutup baris si gambar — tanpa format — dan butir daftar
 * tempat foto ditempel kehilangan nomornya. Format sebaris (tebal/miring)
 * sengaja disaring keluar; yang diwariskan hanya bentuk barisnya.
 */
function sisipkan(q: Quill, src: string, di: number | null = null, sumber: 'user' | 'silent' = 'user'): void {
    let at = di === null ? (q.getSelection(true) ?? { index: q.getLength() - 1 }).index : di;

    const semua = q.getFormat(at) as Record<string, unknown>;
    const blok: Record<string, unknown> = {};
    (['list', 'indent', 'blockquote', 'header', 'align', 'direction'] as const).forEach((k) => {
        if (semua[k] !== undefined) blok[k] = semua[k];
    });

    if (at > 0 && q.getText(at - 1, 1) !== '\n') {
        q.insertText(at, '\n', blok, sumber);
        at += 1;
    }

    q.insertEmbed(at, 'image', src, sumber);
    q.insertText(at + 1, '\n', blok, sumber);
    q.setSelection(at + 2, 0);
}
