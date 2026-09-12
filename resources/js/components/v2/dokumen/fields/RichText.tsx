/**
 * Kolom BERFORMAT (Lexical, sejak migrasi dari Quill 2) — pengganti
 * `documents/fields/_rich_text.blade.php` beserta komponen Alpine `richText`.
 *
 * Dipakai kolom "Deskripsi Aktivitas" pada SOP/SP/IK, dan sengaja dibuat
 * sebagai TIPE FIELD, bukan tipe section: perbedaan antar-jenis dokumen datang
 * dari schema (CLAUDE.md §7), jadi satu kunci schema `rich_text` menyalakan
 * editor ini di ketiga jenis sekaligus — tanpa komponen baru.
 *
 * KENAPA GANTI DARI QUILL: dua percobaan menambal perilaku daftar bernomor
 * Quill berakhir buruk — percobaan kedua ("Lanjutkan Penomoran") justru
 * mematikan editornya sama sekali di peramban sungguhan, karena sesi yang
 * menulisnya tak punya akses browser utk mengujinya langsung. Lexical
 * dipilih justru karena bisa diuji SUNGGUHAN tanpa peramban lewat
 * `@lexical/headless` (dipakai memverifikasi bentuk HTML sebelum berkas ini
 * ditulis — lihat riwayat percakapan, bukan tertinggal di sini).
 *
 * `import()` DINAMIS utk SELURUH paket `lexical`/`@lexical/*` — persis alasan
 * Quill dulu (komentar `muat()` versi lama): Lexical menyentuh `document` saat
 * dimuat, dan gerbang `smartpro:uji-render` merender halaman wizard di Node
 * TANPA DOM. `useEffect` tak pernah jalan di sana, jadi paketnya tak pernah
 * ikut dimuat — dan sebagai bonus, ~280KB Lexical tak lagi ikut terbawa ke
 * SETIAP pemuatan halaman Edit, cuma yang benar-benar memakai kolom ini.
 *
 * KONTRAK YANG BERTAHAN dari versi Quill (WAJIB, diperiksa `PembersihHtml`):
 *
 *  1. **Editor TIDAK memegang nilainya.** Sumber kebenarannya tetap nilai
 *     seksi di React; komponen ini hanya menyalin ke sana tiap kali isinya
 *     berubah (`registerUpdateListener` → `$generateHtmlFromNodes`).
 *  2. **Input HIDDEN ber-`data-pp-rich`.** `validasiWajib()` memindainya.
 *  3. **Foto tempelan/seret jadi berkas sungguhan LANGSUNG** — Lexical
 *     memberi kendali penuh di event `paste`/`drop`, jadi tak perlu lagi
 *     trik "biarkan masuk sbg data: URI lalu disapu belakangan" spt Quill
 *     dulu: berkasnya diunggah SEBELUM node gambar disisipkan sama sekali.
 *  4. **`PembersihHtml::IZIN`** (`p, br, strong, em, u, ol, ul, li, img,
 *     blockquote`) — SATU-SATUNYA tag yang boleh keluar dari editor ini.
 *     Lexical membungkus teks berformat dgn tag ganda yg redundan (mis.
 *     `<b><strong>x</strong></b>`) — TERVERIFIKASI aman: `PembersihHtml`
 *     menyaringnya jadi `<strong><strong>x</strong></strong>`, tetap benar
 *     secara semantik, cuma tak seindah mungkin.
 *  5. **Gambar SELALU baris sendiri**, tak pernah sebaris dgn kalimat —
 *     `ImageNode` di bawah blok (`isInline()` false), otomatis tanpa trik
 *     `\n` manual spt Quill dulu. `PembersihHtml::pecahBlok()` diberi cabang
 *     baru utk gambar BERDIRI SENDIRI (bukan di dlm `<p>`, beda dari bentuk
 *     Quill lama) — lihat perubahan di sana.
 */
import {
    EraserIcon, Image01Icon, LeftToRightListBulletIcon, LeftToRightListNumberIcon,
    QuoteDownIcon, TextBoldIcon, TextItalicIcon, TextUnderlineIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
// Cuma TIPE — dihapus sepenuhnya saat dikompilasi (`import type`), jadi tak
// membawa modul `lexical` sungguhan ke dalam bundel statis.
import type {
    DOMConversionMap, DOMExportOutput, EditorState, LexicalEditor, NodeKey, SerializedLexicalNode,
} from 'lexical';
import { useCallback, useEffect, useRef, useState, type JSX } from 'react';

import { Button } from '@/components/ui-maia/button';
import { kecilkan, unggahGambar } from '@/lib/unggah';
import type { BidangSchema } from '@/types/wizard';

import { useWizard } from './konteks';

/** Hasil `Promise.all` seluruh `import()` dinamis — dimuat SEKALI per komponen. */
type ModulLexical = Awaited<ReturnType<typeof muatModul>>;

async function muatModul() {
    const [
        lexicalCore, list, richText, selection, utils, html,
        mComposer, mComposerCtx, mContentEditable, mErrorBoundary, mHistory, mListPlugin, mRichTextPlugin,
    ] = await Promise.all([
        import('lexical'),
        import('@lexical/list'),
        import('@lexical/rich-text'),
        import('@lexical/selection'),
        import('@lexical/utils'),
        import('@lexical/html'),
        import('@lexical/react/LexicalComposer'),
        import('@lexical/react/LexicalComposerContext'),
        import('@lexical/react/LexicalContentEditable'),
        import('@lexical/react/LexicalErrorBoundary'),
        import('@lexical/react/LexicalHistoryPlugin'),
        import('@lexical/react/LexicalListPlugin'),
        import('@lexical/react/LexicalRichTextPlugin'),
    ]);

    /**
     * Gambar sebagai node BLOK (bukan bawaan Lexical — beda dari Quill yang
     * punya blot gambar siap pakai). Kelasnya WAJIB dibuat di sini (bukan di
     * cakupan modul): `DecoratorNode` adalah NILAI dari paket `lexical` yang
     * baru saja dimuat, bukan tipe — `extends`-nya butuh paketnya sudah ada.
     *
     * `exportDOM()` menulis `<img src="...">` POLOS, bentuk PERSIS yang
     * diterima `PembersihHtml::jalurGambar()`; `importDOM()` mengenalinya
     * kembali SAAT DIMUAT — baik dari dokumen Lexical baru maupun dokumen
     * Quill LAMA yang tersimpan (dua-duanya sama bentuknya sesudah
     * tersanitasi).
     */
    type SerializedImageNode = SerializedLexicalNode & { src: string };

    class ImageNode extends lexicalCore.DecoratorNode<JSX.Element> {
        __src: string;

        static getType(): string {
            return 'image';
        }

        static clone(node: ImageNode): ImageNode {
            return new ImageNode(node.__src, node.__key);
        }

        constructor(src: string, key?: NodeKey) {
            super(key);
            this.__src = src;
        }

        isInline(): boolean {
            return false;
        }

        createDOM(): HTMLElement {
            const div = document.createElement('div');
            div.style.margin = '0.45rem 0';

            return div;
        }

        updateDOM(): boolean {
            return false;
        }

        decorate(): JSX.Element {
            return <img src={this.__src} alt="" className="block h-auto max-w-full rounded-md" />;
        }

        exportDOM(): DOMExportOutput {
            const img = document.createElement('img');
            img.setAttribute('src', this.__src);

            return { element: img };
        }

        static importDOM(): DOMConversionMap | null {
            return {
                img: (node: HTMLElement) => {
                    if (node.tagName !== 'IMG') return null;

                    return {
                        conversion: () => {
                            const src = node.getAttribute('src');

                            return src ? { node: new ImageNode(src) } : null;
                        },
                        priority: 0,
                    };
                },
            };
        }

        exportJSON(): SerializedImageNode {
            return { type: 'image', version: 1, src: this.__src };
        }

        static importJSON(json: SerializedImageNode): ImageNode {
            return new ImageNode(json.src);
        }
    }

    return { lexicalCore, list, richText, selection, utils, html, mComposer, mComposerCtx, mContentEditable, mErrorBoundary, mHistory, mListPlugin, mRichTextPlugin, ImageNode };
}

/**
 * Isi awal, dijalankan SEKALI oleh `LexicalComposer` sendiri
 * (`initialConfig.editorState`, hanya berjalan selama root masih kosong) —
 * tak perlu lagi `useEffect` + ref manual spt Quill dulu.
 *
 * Dokumen LAMA tersimpan sebagai teks polos bernewline. Kalau ia diurai sbg
 * HTML, newline-nya lenyap dan seluruh langkah menyatu jadi satu paragraf —
 * tata letak SOP berjalan berubah hanya karena editornya diganti. Satu
 * `ParagraphNode` per baris mempertahankan bentuknya, sama spt `q.setText()`
 * versi Quill.
 */
function buatMuatAwal(m: ModulLexical) {
    return (editor: LexicalEditor, nilai: string): void => {
        const root = m.lexicalCore.$getRoot();
        if (!nilai) return;

        if (/<\/?(p|br|strong|em|u|b|i|ol|ul|li|img|blockquote)\b/i.test(nilai)) {
            const dom = new DOMParser().parseFromString(nilai, 'text/html');
            const nodes = m.html.$generateNodesFromDOM(editor, dom);
            root.append(...nodes);

            return;
        }

        nilai.split(/\r\n|\r|\n/).forEach((baris) => {
            const p = m.lexicalCore.$createParagraphNode();
            if (baris !== '') p.append(m.lexicalCore.$createTextNode(baris));
            root.append(p);
        });
    };
}

/* ================================ Toolbar ================================ */

type StatusFormat = {
    bold: boolean;
    italic: boolean;
    underline: boolean;
    quote: boolean;
    ol: boolean;
    ul: boolean;
};

const STATUS_KOSONG: StatusFormat = {
    bold: false, italic: false, underline: false, quote: false, ol: false, ul: false,
};

function TombolFormat({
    aktif,
    onKlik,
    judul,
    ikon,
}: {
    aktif: boolean;
    onKlik: () => void;
    judul: string;
    ikon: typeof TextBoldIcon;
}) {
    return (
        <Button
            type="button"
            variant={aktif ? 'secondary' : 'ghost'}
            size="icon"
            className="size-7"
            title={judul}
            aria-pressed={aktif}
            onMouseDown={(e) => e.preventDefault()}
            onClick={onKlik}
        >
            <HugeiconsIcon icon={ikon} strokeWidth={1.5} className="size-4" />
        </Button>
    );
}

/* ============================================================================= */

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
    const [modul, setModul] = useState<ModulLexical | null>(null);

    // Nilai TERBARU disimpan di ref: dipakai `initialConfig.editorState`
    // (dibaca sekali begitu modulnya siap, bukan tiap render) — pola yang
    // sama dgn `nilaiRef` versi Quill dulu.
    const nilaiAwalRef = useRef(value);
    nilaiAwalRef.current = value;

    useEffect(() => {
        let dibuang = false;
        void muatModul().then((m) => {
            if (!dibuang) setModul(m);
        });

        return () => {
            dibuang = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- sekali seumur hidup komponen.
    }, []);

    return (
        <>
            <div className="pp-rt rounded-lg border border-input bg-background">
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

                {modul ? (
                    <Editor
                        modul={modul}
                        nilaiAwal={nilaiAwalRef.current}
                        onChange={onChange}
                        sectionKey={sectionKey}
                        field={field}
                    />
                ) : (
                    // Kerangka sementara — bentuknya SAMA dgn editor sungguhan
                    // (toolbar + kotak isi) supaya tata letak tak melompat
                    // begitu paketnya selesai dimuat.
                    <div className="min-h-36 animate-pulse bg-muted/30" />
                )}
            </div>
        </>
    );
}

/**
 * Editor sungguhan — dipasang hanya sesudah `muatModul()` selesai. Dipecah
 * dari `RichText` supaya `useState<ModulLexical | null>` di atas cukup DISATU
 * TEMPAT (bukan diulang di tiap anak) sebagai penjaga "belum siap".
 */
function Editor({
    modul: m,
    nilaiAwal,
    onChange,
    sectionKey,
    field,
}: {
    modul: ModulLexical;
    nilaiAwal: string;
    onChange: (html: string) => void;
    sectionKey: string;
    field: BidangSchema;
}) {
    const { documentId, editable } = useWizard();
    const urlUnggah = route('documents.uploadAttachment', documentId);

    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    const berkasRef = useRef<HTMLInputElement>(null);
    const editorRef = useRef<LexicalEditor | null>(null);

    const [mengunggah, setMengunggah] = useState(false);
    const [galat, setGalat] = useState('');
    const [status, setStatus] = useState<StatusFormat>(STATUS_KOSONG);

    const muatAwal = useRef(buatMuatAwal(m)).current;

    /** SATU-SATUNYA jalur unggah — dipakai tombol gambar, tempelan, maupun seretan. */
    const kirim = useCallback(async (berkas: File): Promise<string | null> => {
        setGalat('');
        setMengunggah(true);

        const hasil = await unggahGambar(urlUnggah, await kecilkan(berkas), sectionKey);

        setMengunggah(false);
        if (hasil.galat) setGalat(hasil.galat);

        return hasil.path;
    }, [urlUnggah, sectionKey]);

    const sisipkanGambar = useCallback((jalur: string) => {
        const editor = editorRef.current;
        if (!editor) return;

        editor.update(() => {
            const selection = m.lexicalCore.$getSelection();
            const node = new m.ImageNode('/storage/' + jalur);

            if (m.lexicalCore.$isRangeSelection(selection)) {
                m.lexicalCore.$insertNodes([node]);
            } else {
                m.lexicalCore.$getRoot().append(node);
            }
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps -- `m` stabil seumur komponen.
    }, []);

    /** Tombol gambar di toolbar — dialog bawaan sengaja TIDAK dipakai, sama spt Quill dulu. */
    const pilihBerkas = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const berkas = e.target.files?.[0];
        e.target.value = '';
        if (!berkas) return;

        const jalur = await kirim(berkas);
        if (jalur) sisipkanGambar(jalur);
    };

    return (
        <>
            <m.mComposer.LexicalComposer
                initialConfig={{
                    namespace: `rich-text-${sectionKey}`,
                    editable,
                    nodes: [m.richText.QuoteNode, m.list.ListNode, m.list.ListItemNode, m.ImageNode],
                    onError: (e: Error) => console.error(e),
                    editorState: (editor: LexicalEditor) => muatAwal(editor, nilaiAwal),
                }}
            >
                <Toolbar
                    m={m}
                    status={status}
                    mengunggah={mengunggah}
                    onGambar={() => berkasRef.current?.click()}
                />
                <m.mRichTextPlugin.RichTextPlugin
                    contentEditable={
                        <m.mContentEditable.ContentEditable
                            className="min-h-28 px-3 py-2 text-sm outline-none"
                            aria-placeholder={field.placeholder ?? ''}
                            placeholder={
                                <p className="pointer-events-none absolute top-2 left-3 text-sm text-muted-foreground">
                                    {field.placeholder ?? ''}
                                </p>
                            }
                        />
                    }
                    ErrorBoundary={m.mErrorBoundary.LexicalErrorBoundary}
                />
                <m.mHistory.HistoryPlugin />
                <m.mListPlugin.ListPlugin />
                <Sinkron
                    m={m}
                    editorRef={editorRef}
                    onChangeRef={onChangeRef}
                    onStatus={setStatus}
                    kirim={kirim}
                    sisipkanGambar={sisipkanGambar}
                />
            </m.mComposer.LexicalComposer>

            {galat ? <p className="mt-1 text-xs text-destructive">{galat}</p> : null}

            {/* Berkas dipilih lewat input tersembunyi milik komponen, bukan
                lewat dialog bawaan — unggahannya harus melewati
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
 * Toolbar — digambar React biasa (`ui-maia/button` + Hugeicons), BUKAN
 * meniru konvensi kelas `ql-*` Quill: Lexical tak mendikte markup toolbar
 * sama sekali, jadi tak ada CSS override khusus yang perlu dijaga lagi.
 */
function Toolbar({
    m,
    status,
    mengunggah,
    onGambar,
}: {
    m: ModulLexical;
    status: StatusFormat;
    mengunggah: boolean;
    onGambar: () => void;
}) {
    const [editor] = m.mComposerCtx.useLexicalComposerContext();

    const format = (jenis: 'bold' | 'italic' | 'underline') =>
        editor.dispatchCommand(m.lexicalCore.FORMAT_TEXT_COMMAND, jenis);

    const kutipan = () => {
        editor.update(() => {
            const selection = m.lexicalCore.$getSelection();
            if (!m.lexicalCore.$isRangeSelection(selection)) return;
            m.selection.$setBlocksType(selection, () =>
                (status.quote ? m.lexicalCore.$createParagraphNode() : m.richText.$createQuoteNode()));
        });
    };

    const daftar = (jenis: 'ordered' | 'bullet') => {
        if ((jenis === 'ordered' && status.ol) || (jenis === 'bullet' && status.ul)) {
            editor.dispatchCommand(m.list.REMOVE_LIST_COMMAND, undefined);

            return;
        }
        editor.dispatchCommand(
            jenis === 'ordered' ? m.list.INSERT_ORDERED_LIST_COMMAND : m.list.INSERT_UNORDERED_LIST_COMMAND,
            undefined,
        );
    };

    /** Lepas SELURUH format sebaris + balik blok ke paragraf biasa — padanan tombol "Bersihkan format" Quill. */
    const bersihkan = () => {
        editor.update(() => {
            const selection = m.lexicalCore.$getSelection();
            if (!m.lexicalCore.$isRangeSelection(selection)) return;

            (['bold', 'italic', 'underline'] as const).forEach((f) => {
                if (selection.hasFormat(f)) editor.dispatchCommand(m.lexicalCore.FORMAT_TEXT_COMMAND, f);
            });

            if (status.quote) m.selection.$setBlocksType(selection, () => m.lexicalCore.$createParagraphNode());
        });
        if (status.ol || status.ul) editor.dispatchCommand(m.list.REMOVE_LIST_COMMAND, undefined);
    };

    return (
        <div className="flex flex-wrap items-center gap-0.5 rounded-t-lg border-b border-input bg-muted/40 p-1">
            <TombolFormat judul="Tebal" ikon={TextBoldIcon} aktif={status.bold} onKlik={() => format('bold')} />
            <TombolFormat judul="Miring" ikon={TextItalicIcon} aktif={status.italic} onKlik={() => format('italic')} />
            <TombolFormat judul="Garis bawah" ikon={TextUnderlineIcon} aktif={status.underline} onKlik={() => format('underline')} />
            <span className="mx-1 h-5 w-px bg-border" />
            <TombolFormat judul="Kutipan" ikon={QuoteDownIcon} aktif={status.quote} onKlik={kutipan} />
            <span className="mx-1 h-5 w-px bg-border" />
            <TombolFormat judul="Daftar bernomor" ikon={LeftToRightListNumberIcon} aktif={status.ol} onKlik={() => daftar('ordered')} />
            <TombolFormat judul="Daftar berbutir" ikon={LeftToRightListBulletIcon} aktif={status.ul} onKlik={() => daftar('bullet')} />
            <span className="mx-1 h-5 w-px bg-border" />
            <TombolFormat judul="Gambar" ikon={Image01Icon} aktif={false} onKlik={onGambar} />
            <TombolFormat judul="Bersihkan format" ikon={EraserIcon} aktif={false} onKlik={bersihkan} />
            {mengunggah ? <span className="ml-2 text-xs text-primary">Mengunggah gambar…</span> : null}
        </div>
    );
}

/**
 * Bukan komponen tampilan — cuma tempat efek yang butuh `editor` dari
 * konteks (`useLexicalComposerContext` HANYA bisa dipanggil dari dalam
 * `LexicalComposer`, jadi berkas ini tak bisa mengaksesnya langsung).
 *
 * TIGA tanggung jawab:
 *  1. Sinkron nilai keluar (pengganti `sinkron()` Quill).
 *  2. Status tombol toolbar (aktif/tidak) — dulu otomatis di Quill lewat
 *     kelas `ql-active`; di sini dibaca manual dari seleksi.
 *  3. Tempelan (Ctrl+V) & seretan gambar → diunggah LANGSUNG (nol data: URI
 *     yang sempat tersimpan, beda dari `sapuTempelan()` Quill dulu).
 */
function Sinkron({
    m,
    editorRef,
    onChangeRef,
    onStatus,
    kirim,
    sisipkanGambar,
}: {
    m: ModulLexical;
    editorRef: React.MutableRefObject<LexicalEditor | null>;
    onChangeRef: React.MutableRefObject<(html: string) => void>;
    onStatus: (s: StatusFormat) => void;
    kirim: (berkas: File) => Promise<string | null>;
    sisipkanGambar: (jalur: string) => void;
}) {
    const [editor] = m.mComposerCtx.useLexicalComposerContext();
    editorRef.current = editor;

    useEffect(() => {
        return m.utils.mergeRegister(
            editor.registerUpdateListener(({ editorState }: { editorState: EditorState }) => {
                editorState.read(() => {
                    const root = m.lexicalCore.$getRoot();
                    const kosong = root.getTextContent().trim() === ''
                        && !root.getChildren().some((n) => n instanceof m.ImageNode);
                    onChangeRef.current(kosong ? '' : m.html.$generateHtmlFromNodes(editor, null));

                    const selection = m.lexicalCore.$getSelection();
                    if (!m.lexicalCore.$isRangeSelection(selection)) {
                        onStatus(STATUS_KOSONG);

                        return;
                    }

                    const anchor = selection.anchor.getNode();
                    const elemen = m.lexicalCore.$isElementNode(anchor) ? anchor : anchor.getParentOrThrow();
                    const list = m.utils.$getNearestNodeOfType(anchor, m.list.ListNode);
                    const dalamListItem = m.utils.$findMatchingParent(anchor, m.list.$isListItemNode) !== null;

                    onStatus({
                        bold: selection.hasFormat('bold'),
                        italic: selection.hasFormat('italic'),
                        underline: selection.hasFormat('underline'),
                        quote: m.richText.$isQuoteNode(elemen) || m.richText.$isQuoteNode(elemen.getParent()),
                        ol: dalamListItem && list?.getListType() === 'number',
                        ul: dalamListItem && list?.getListType() === 'bullet',
                    });
                });
            }),
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps -- editor & m stabil seumur komposer.
    }, [editor]);

    /**
     * Tempelan & seretan gambar — diambil dari `clipboardData`/`dataTransfer`
     * SEBAGAI BERKAS langsung (bukan dibiarkan Lexical menyisipkannya sbg
     * `data:` URI dulu spt Quill lama), jadi tak pernah ada base64 yang
     * sempat ikut tersimpan sebelum sempat disapu.
     */
    useEffect(() => {
        const root = editor.getRootElement();
        if (!root) return;

        const berkasGambar = (list?: FileList | null): File | null => {
            if (!list) return null;

            return Array.from(list).find((f) => f.type.startsWith('image/')) ?? null;
        };

        const tempel = (e: ClipboardEvent) => {
            const berkas = berkasGambar(e.clipboardData?.files);
            if (!berkas) return;

            e.preventDefault();
            void kirim(berkas).then((jalur) => { if (jalur) sisipkanGambar(jalur); });
        };

        const seret = (e: DragEvent) => {
            const berkas = berkasGambar(e.dataTransfer?.files);
            if (!berkas) return;

            e.preventDefault();
            void kirim(berkas).then((jalur) => { if (jalur) sisipkanGambar(jalur); });
        };

        root.addEventListener('paste', tempel);
        root.addEventListener('drop', seret);

        return () => {
            root.removeEventListener('paste', tempel);
            root.removeEventListener('drop', seret);
        };
    }, [editor, kirim, sisipkanGambar]);

    return null;
}
