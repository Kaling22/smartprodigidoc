import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { UserMultipleIcon, Message01Icon, Delete02Icon, Alert02Icon, Cancel01Icon, ShieldCheckIcon, PlusSignIcon, MinusSignIcon, ArrowUp01Icon, ArrowDown01Icon, PencilEdit01Icon, LeftToRightListNumberIcon, TextBoldIcon, TextItalicIcon, TextUnderlineIcon, QuoteDownIcon, LeftToRightListBulletIcon, Image01Icon, EraserIcon, LockIcon, File01Icon, ArrowRight01Icon, CloudSavingDone01Icon, ArrowLeft01Icon, ViewIcon, RefreshCwIcon, FloppyDiskIcon, SentIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, router } from "@inertiajs/react";
import { useState, useRef, useEffect, useCallback } from "react";
import { toast } from "sonner";
import { u as useWizard, a as unggahGambar, k as kecilkan, B as Bantuan, P as PapanKetersediaan, b as PenyediaWizard, x as xsrf } from "./PapanKetersediaan-CW9q2Zfe.js";
import { A as AlertDialog, a as AlertDialogContent, b as AlertDialogHeader, c as AlertDialogTitle, d as AlertDialogDescription, e as AlertDialogFooter, f as AlertDialogCancel, g as AlertDialogAction, C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { B as Badge, A as AppLayout } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { L as Label } from "./label-emiz6fAI.js";
import { T as Textarea } from "./textarea-CFf6776c.js";
import { S as Switch } from "./switch-3nvfOMMe.js";
import { c as cn } from "../uji-render.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import "clsx";
import { S as StatusBadge } from "./StatusBadge-BV-SY8b9.js";
import { b as barisLogBaru, R as RevisionLog, a as barisLogBerisi } from "./RevisionLog-ptk712C6.js";
import "radix-ui";
import "class-variance-authority";
import "next-themes";
import "react-dom/server";
import "tailwind-merge";
const SEJAWAT = "sejawat";
function bergeser(kutipan, nilaiKini) {
  if (!kutipan || nilaiKini === void 0) return false;
  const bersih = nilaiKini.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
  return bersih !== "" && !bersih.includes(kutipan.replace(/\.\.\.$/, "").trim());
}
function CatatanItem({ daftar, nilaiKini }) {
  if (!daftar?.length) return null;
  return /* @__PURE__ */ jsx("div", { className: "border-destructive/30 bg-destructive/5 mt-1.5 space-y-1.5 rounded-lg border border-dashed px-2.5 py-2", children: daftar.map((c, i) => {
    const sejawat = c.sumber === SEJAWAT;
    return (
      // Kunci indeks aman: daftar ini hanya dibaca, tak pernah
      // disunting atau diurut ulang di peramban.
      // eslint-disable-next-line react/no-array-index-key
      /* @__PURE__ */ jsxs("div", { className: "text-xs", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-1.5", children: [
          /* @__PURE__ */ jsxs(Badge, { variant: sejawat ? "secondary" : "destructive", children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: sejawat ? UserMultipleIcon : Message01Icon, strokeWidth: 1.5 }),
            sejawat ? "Sejawat" : "Peninjau"
          ] }),
          /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: c.oleh })
        ] }),
        /* @__PURE__ */ jsx("p", { className: "mt-1 whitespace-pre-line", children: c.komentar }),
        bergeser(c.kutipan, nilaiKini) ? /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground mt-0.5 italic", children: [
          "Saat dikomentari, isinya: “",
          c.kutipan,
          "”"
        ] }) : null
      ] }, i)
    );
  }) });
}
const bahayaBaru = () => ({ risiko: "", pengendalian: [""] });
const langkahBaru = () => ({ langkah: "", bahaya: [bahayaBaru()] });
function JsaAnalysis({ section, value, onChange }) {
  const catatan = useWizard().catatanItem[section.key];
  const mentah = Array.isArray(value) ? value : [];
  const steps = mentah.length ? mentah.map((s) => ({
    langkah: s.langkah ?? "",
    bahaya: (Array.isArray(s.bahaya) && s.bahaya.length ? s.bahaya : [bahayaBaru()]).map((b) => ({
      risiko: b.risiko ?? "",
      pengendalian: Array.isArray(b.pengendalian) && b.pengendalian.length ? b.pengendalian : [""]
    }))
  })) : [langkahBaru()];
  const ubahLangkah = (li, patch) => onChange(steps.map((s, n) => n === li ? { ...s, ...patch } : s));
  const ubahBahaya = (li, bi, patch) => ubahLangkah(li, { bahaya: steps[li].bahaya.map((b, n) => n === bi ? { ...b, ...patch } : b) });
  const nama = (bagian) => "sections[" + section.key + "]" + bagian;
  return /* @__PURE__ */ jsxs("div", { className: "mb-6", children: [
    /* @__PURE__ */ jsx(Label, { className: "mb-1.5 font-semibold", children: section.label ?? section.key }),
    section.help ? /* @__PURE__ */ jsx("p", { className: "mb-3 rounded-lg bg-muted px-3 py-2 text-xs text-muted-foreground", children: section.help }) : null,
    steps.map((step, li) => (
      // eslint-disable-next-line react/no-array-index-key
      /* @__PURE__ */ jsxs(Card, { className: "mb-3 gap-0 py-0", children: [
        /* @__PURE__ */ jsxs(CardHeader, { className: "flex items-center justify-between border-b px-4 py-3", children: [
          /* @__PURE__ */ jsxs("span", { className: "font-semibold", children: [
            "Langkah Kerja #",
            li + 1
          ] }),
          /* @__PURE__ */ jsxs(
            Button,
            {
              type: "button",
              variant: "outline",
              size: "sm",
              onClick: () => {
                const sisa = steps.filter((_, n) => n !== li);
                onChange(sisa.length ? sisa : [langkahBaru()]);
              },
              children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5 }),
                "Hapus Langkah"
              ]
            }
          )
        ] }),
        /* @__PURE__ */ jsxs(CardContent, { className: "p-4", children: [
          /* @__PURE__ */ jsx(Label, { className: "mb-1.5 text-sm font-semibold", children: "Uraian Langkah Pekerjaan" }),
          /* @__PURE__ */ jsx(
            Textarea,
            {
              className: "mb-3",
              rows: 2,
              name: nama("[" + li + "][langkah]"),
              value: step.langkah,
              placeholder: "mis. Persiapan alat dan pengecekan area kerja",
              onChange: (e) => ubahLangkah(li, { langkah: e.target.value })
            }
          ),
          /* @__PURE__ */ jsx(CatatanItem, { daftar: catatan?.["L" + li], nilaiKini: step.langkah }),
          step.bahaya.map((bahaya, bi) => (
            // eslint-disable-next-line react/no-array-index-key
            /* @__PURE__ */ jsxs("div", { className: "mb-2 rounded-lg border bg-muted/40 p-3", children: [
              /* @__PURE__ */ jsxs(Label, { className: "mb-1 flex items-center gap-1.5 text-sm font-semibold text-destructive", children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Alert02Icon, strokeWidth: 1.5, className: "size-3.5" }),
                "Bahaya & Risiko"
              ] }),
              /* @__PURE__ */ jsxs("div", { className: "mb-2 flex gap-2", children: [
                /* @__PURE__ */ jsx(
                  Input,
                  {
                    className: "flex-1",
                    name: nama("[" + li + "][bahaya][" + bi + "][risiko]"),
                    value: bahaya.risiko,
                    placeholder: "Potensi bahaya / risiko yang mungkin timbul",
                    onChange: (e) => ubahBahaya(li, bi, { risiko: e.target.value })
                  }
                ),
                /* @__PURE__ */ jsx(
                  Button,
                  {
                    type: "button",
                    variant: "outline",
                    size: "icon",
                    className: "size-8 shrink-0",
                    title: "Hapus bahaya ini",
                    onClick: () => {
                      const sisa = step.bahaya.filter((_, n) => n !== bi);
                      ubahLangkah(li, { bahaya: sisa.length ? sisa : [bahayaBaru()] });
                    },
                    children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 1.5 })
                  }
                )
              ] }),
              /* @__PURE__ */ jsx(CatatanItem, { daftar: catatan?.["L" + li + "-B" + bi], nilaiKini: bahaya.risiko }),
              /* @__PURE__ */ jsxs(Label, { className: "mb-1 flex items-center gap-1.5 text-sm font-semibold text-chart-2", children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ShieldCheckIcon, strokeWidth: 1.5, className: "size-3.5" }),
                "Tindakan Pengendalian"
              ] }),
              bahaya.pengendalian.map((kendali, pi) => (
                // eslint-disable-next-line react/no-array-index-key
                /* @__PURE__ */ jsxs("div", { className: "mb-1", children: [
                  /* @__PURE__ */ jsxs("div", { className: "flex gap-2", children: [
                    /* @__PURE__ */ jsx(
                      Input,
                      {
                        className: "h-7 flex-1",
                        name: nama("[" + li + "][bahaya][" + bi + "][pengendalian][" + pi + "]"),
                        value: kendali ?? "",
                        placeholder: "Langkah pengendalian bahaya",
                        onChange: (e) => ubahBahaya(li, bi, {
                          pengendalian: bahaya.pengendalian.map(
                            (v, n) => n === pi ? e.target.value : v
                          )
                        })
                      }
                    ),
                    /* @__PURE__ */ jsx(
                      Button,
                      {
                        type: "button",
                        variant: "outline",
                        size: "icon",
                        className: "size-7 shrink-0",
                        title: "Tambah pengendalian",
                        onClick: () => ubahBahaya(li, bi, { pengendalian: [...bahaya.pengendalian, ""] }),
                        children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 })
                      }
                    ),
                    bahaya.pengendalian.length > 1 ? /* @__PURE__ */ jsx(
                      Button,
                      {
                        type: "button",
                        variant: "outline",
                        size: "icon",
                        className: "size-7 shrink-0",
                        title: "Hapus",
                        onClick: () => {
                          const sisa = bahaya.pengendalian.filter((_, n) => n !== pi);
                          ubahBahaya(li, bi, { pengendalian: sisa.length ? sisa : [""] });
                        },
                        children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: MinusSignIcon, strokeWidth: 1.5 })
                      }
                    ) : null
                  ] }),
                  /* @__PURE__ */ jsx(
                    CatatanItem,
                    {
                      daftar: catatan?.["L" + li + "-B" + bi + "-P" + pi],
                      nilaiKini: kendali ?? ""
                    }
                  )
                ] }, pi)
              ))
            ] }, bi)
          )),
          /* @__PURE__ */ jsxs(
            Button,
            {
              type: "button",
              variant: "outline",
              size: "sm",
              className: "mt-1",
              onClick: () => ubahLangkah(li, { bahaya: [...step.bahaya, bahayaBaru()] }),
              children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 }),
                "Tambah Bahaya"
              ]
            }
          )
        ] })
      ] }, li)
    )),
    /* @__PURE__ */ jsxs(Button, { type: "button", className: "w-full", onClick: () => onChange([...steps, langkahBaru()]), children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 }),
      "Tambah Langkah Kerja Baru"
    ] })
  ] });
}
function DocumentPicker({
  value,
  onChange,
  nama,
  opsional,
  peringatan,
  placeholder,
  onPilih
}) {
  const { dokumenBerlaku } = useWizard();
  const [buka, setBuka] = useState(false);
  const [sorot, setSorot] = useState(0);
  const kotak = useRef(null);
  useEffect(() => {
    if (!buka) return;
    const tutup = (e) => {
      if (kotak.current && !kotak.current.contains(e.target)) setBuka(false);
    };
    document.addEventListener("mousedown", tutup);
    return () => document.removeEventListener("mousedown", tutup);
  }, [buka]);
  const label = (o) => `${o.nomor} — ${o.judul}`;
  const pilih = (o) => {
    if (onPilih) onPilih(o.nomor, o.judul);
    else onChange(label(o));
    setBuka(false);
  };
  const cocok = (q) => {
    const s = (q ?? "").toLowerCase().trim();
    if (!s) return dokumenBerlaku;
    return dokumenBerlaku.filter((o) => `${o.jenis} ${o.nomor} ${o.judul}`.toLowerCase().includes(s));
  };
  const daftar = cocok(value);
  const tombol = (e) => {
    if (e.key === "ArrowDown") {
      e.preventDefault();
      setBuka(true);
      setSorot((s) => Math.min(s + 1, daftar.length - 1));
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      setSorot((s) => Math.max(s - 1, 0));
    } else if (e.key === "Enter") {
      e.preventDefault();
      const o = daftar[sorot];
      if (o) pilih(o);
      setBuka(false);
    } else if (e.key === "Escape") {
      setBuka(false);
    }
  };
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsxs("div", { className: "relative", ref: kotak, children: [
      /* @__PURE__ */ jsxs("div", { className: "flex gap-2", children: [
        /* @__PURE__ */ jsx(
          Input,
          {
            className: "flex-1",
            autoComplete: "off",
            role: "combobox",
            "aria-expanded": buka,
            "aria-autocomplete": "list",
            name: nama,
            value: value ?? "",
            placeholder: placeholder ?? "",
            ...opsional ? { "data-optional": "" } : {},
            ...peringatan ? { "data-pp-warn": peringatan } : {},
            onFocus: () => setBuka(true),
            onChange: (e) => {
              onChange(e.target.value);
              setBuka(true);
              setSorot(0);
            },
            onKeyDown: tombol
          }
        ),
        /* @__PURE__ */ jsx(
          Button,
          {
            type: "button",
            variant: "outline",
            size: "icon",
            className: "size-8 shrink-0",
            tabIndex: -1,
            "aria-label": buka ? "Tutup daftar" : "Buka daftar dokumen",
            onClick: () => setBuka((b) => !b),
            children: buka ? /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowUp01Icon, strokeWidth: 1.5 }) : /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowDown01Icon, strokeWidth: 1.5 })
          }
        )
      ] }),
      buka ? /* @__PURE__ */ jsx("div", { className: "absolute inset-x-0 top-[calc(100%+0.3rem)] z-50 max-h-64 overflow-y-auto rounded-xl border bg-popover p-1 shadow-lg", children: daftar.length === 0 ? /* @__PURE__ */ jsxs("p", { className: "flex items-center gap-1.5 px-2 py-2 text-xs text-muted-foreground", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PencilEdit01Icon, strokeWidth: 1.5, className: "size-3.5" }),
        "Tak ada dokumen yang cocok — teks yang Anda ketik dipakai apa adanya."
      ] }) : daftar.map((o, oi) => /* @__PURE__ */ jsxs(
        "button",
        {
          type: "button",
          className: cn(
            "flex w-full items-start gap-2 rounded-lg px-2 py-1.5 text-left leading-tight transition-colors",
            oi === sorot && "bg-accent"
          ),
          onMouseEnter: () => setSorot(oi),
          onClick: () => pilih(o),
          children: [
            /* @__PURE__ */ jsx("span", { className: "mt-0.5 shrink-0 rounded-md border border-primary/40 bg-primary/10 px-1.5 py-0.5 text-[0.66rem] font-medium tracking-wide text-primary", children: o.jenis }),
            /* @__PURE__ */ jsxs("span", { className: "min-w-0", children: [
              /* @__PURE__ */ jsx("span", { className: "block font-mono text-xs font-semibold break-words", children: o.nomor }),
              /* @__PURE__ */ jsx("span", { className: "block text-xs text-muted-foreground", children: o.judul })
            ] })
          ]
        },
        o.nomor
      )) }) : null
    ] }),
    /* @__PURE__ */ jsxs("p", { className: "mt-1 flex items-center gap-1.5 text-xs text-muted-foreground", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: LeftToRightListNumberIcon, strokeWidth: 1.5, className: "size-3.5" }),
      dokumenBerlaku.length,
      " dokumen Berlaku di departemen Anda. Bisa juga diketik sendiri bila belum ada di sistem."
    ] })
  ] });
}
function ImageUpload({
  value,
  onChange,
  sectionKey,
  field,
  nama
}) {
  const { documentId } = useWizard();
  const [mengunggah, setMengunggah] = useState(false);
  const [galat, setGalat] = useState("");
  const adaGambar = Boolean(value) && String(value).startsWith("lampiran/");
  const pilih = async (e) => {
    const berkas = e.target.files?.[0];
    if (!berkas) return;
    setGalat("");
    setMengunggah(true);
    const hasil = await unggahGambar(route("documents.uploadAttachment", documentId), berkas, sectionKey);
    setMengunggah(false);
    e.target.value = "";
    if (hasil.path) onChange(hasil.path);
    else setGalat(hasil.galat ?? "Gagal mengunggah gambar.");
  };
  return /* @__PURE__ */ jsxs("div", { children: [
    /* @__PURE__ */ jsx("input", { type: "hidden", name: nama, value: value ?? "", readOnly: true }),
    adaGambar ? /* @__PURE__ */ jsxs("div", { className: "mb-2 flex items-start gap-2", children: [
      /* @__PURE__ */ jsx("img", { src: `/storage/${value}`, alt: "", className: "max-h-40 rounded-lg border" }),
      /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", size: "sm", onClick: () => onChange(""), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 1.5 }),
        "Hapus gambar"
      ] })
    ] }) : null,
    /* @__PURE__ */ jsx(
      Input,
      {
        type: "file",
        accept: field.image_accept ?? "image/jpeg,image/png",
        disabled: mengunggah,
        onChange: pilih
      }
    ),
    mengunggah ? /* @__PURE__ */ jsx("p", { className: "mt-1 text-xs text-primary", children: "Mengunggah…" }) : /* @__PURE__ */ jsxs("p", { className: "mt-1 text-xs text-muted-foreground", children: [
      "Format JPG/PNG, maks ",
      field.image_max_mb ?? 5,
      "MB. Foto akan muncul di preview & PDF."
    ] }),
    galat ? /* @__PURE__ */ jsx("p", { className: "mt-1 text-xs text-destructive", children: galat }) : null
  ] });
}
async function muatModul() {
  const [
    lexicalCore,
    list,
    richText,
    selection,
    utils,
    html,
    mComposer,
    mComposerCtx,
    mContentEditable,
    mErrorBoundary,
    mHistory,
    mListPlugin,
    mRichTextPlugin
  ] = await Promise.all([
    import("lexical"),
    import("@lexical/list"),
    import("@lexical/rich-text"),
    import("@lexical/selection"),
    import("@lexical/utils"),
    import("@lexical/html"),
    import("@lexical/react/LexicalComposer"),
    import("@lexical/react/LexicalComposerContext"),
    import("@lexical/react/LexicalContentEditable"),
    import("@lexical/react/LexicalErrorBoundary"),
    import("@lexical/react/LexicalHistoryPlugin"),
    import("@lexical/react/LexicalListPlugin"),
    import("@lexical/react/LexicalRichTextPlugin")
  ]);
  class ImageNode extends lexicalCore.DecoratorNode {
    __src;
    static getType() {
      return "image";
    }
    static clone(node) {
      return new ImageNode(node.__src, node.__key);
    }
    constructor(src, key) {
      super(key);
      this.__src = src;
    }
    isInline() {
      return false;
    }
    createDOM() {
      const div = document.createElement("div");
      div.style.margin = "0.45rem 0";
      return div;
    }
    updateDOM() {
      return false;
    }
    decorate() {
      return /* @__PURE__ */ jsx("img", { src: this.__src, alt: "", className: "block h-auto max-w-full rounded-md" });
    }
    exportDOM() {
      const img = document.createElement("img");
      img.setAttribute("src", this.__src);
      return { element: img };
    }
    static importDOM() {
      return {
        img: (node) => {
          if (node.tagName !== "IMG") return null;
          return {
            conversion: () => {
              const src = node.getAttribute("src");
              return src ? { node: new ImageNode(src) } : null;
            },
            priority: 0
          };
        }
      };
    }
    exportJSON() {
      return { type: "image", version: 1, src: this.__src };
    }
    static importJSON(json) {
      return new ImageNode(json.src);
    }
  }
  return { lexicalCore, list, richText, selection, utils, html, mComposer, mComposerCtx, mContentEditable, mErrorBoundary, mHistory, mListPlugin, mRichTextPlugin, ImageNode };
}
function buatMuatAwal(m) {
  return (editor, nilai) => {
    const root = m.lexicalCore.$getRoot();
    if (!nilai) return;
    if (/<\/?(p|br|strong|em|u|b|i|ol|ul|li|img|blockquote)\b/i.test(nilai)) {
      const dom = new DOMParser().parseFromString(nilai, "text/html");
      const nodes = m.html.$generateNodesFromDOM(editor, dom);
      root.append(...nodes);
      return;
    }
    nilai.split(/\r\n|\r|\n/).forEach((baris) => {
      const p = m.lexicalCore.$createParagraphNode();
      if (baris !== "") p.append(m.lexicalCore.$createTextNode(baris));
      root.append(p);
    });
  };
}
const STATUS_KOSONG = {
  bold: false,
  italic: false,
  underline: false,
  quote: false,
  ol: false,
  ul: false
};
function TombolFormat({
  aktif,
  onKlik,
  judul,
  ikon
}) {
  return /* @__PURE__ */ jsx(
    Button,
    {
      type: "button",
      variant: aktif ? "secondary" : "ghost",
      size: "icon",
      className: "size-7",
      title: judul,
      "aria-pressed": aktif,
      onMouseDown: (e) => e.preventDefault(),
      onClick: onKlik,
      children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ikon, strokeWidth: 1.5, className: "size-4" })
    }
  );
}
function RichText({
  value,
  onChange,
  sectionKey,
  field,
  nama,
  opsional,
  peringatan
}) {
  const [modul, setModul] = useState(null);
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
  }, []);
  return /* @__PURE__ */ jsx(Fragment, { children: /* @__PURE__ */ jsxs("div", { className: "pp-rt rounded-lg border border-input bg-background", children: [
    /* @__PURE__ */ jsx(
      "input",
      {
        type: "hidden",
        "data-pp-rich": true,
        ...opsional ? { "data-optional": "" } : {},
        ...peringatan ? { "data-pp-warn": peringatan } : {},
        name: nama,
        value: value || "",
        readOnly: true
      }
    ),
    modul ? /* @__PURE__ */ jsx(
      Editor,
      {
        modul,
        nilaiAwal: nilaiAwalRef.current,
        onChange,
        sectionKey,
        field
      }
    ) : (
      // Kerangka sementara — bentuknya SAMA dgn editor sungguhan
      // (toolbar + kotak isi) supaya tata letak tak melompat
      // begitu paketnya selesai dimuat.
      /* @__PURE__ */ jsx("div", { className: "min-h-36 animate-pulse bg-muted/30" })
    )
  ] }) });
}
function Editor({
  modul: m,
  nilaiAwal,
  onChange,
  sectionKey,
  field
}) {
  const { documentId, editable } = useWizard();
  const urlUnggah = route("documents.uploadAttachment", documentId);
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;
  const berkasRef = useRef(null);
  const editorRef = useRef(null);
  const [mengunggah, setMengunggah] = useState(false);
  const [galat, setGalat] = useState("");
  const [status, setStatus] = useState(STATUS_KOSONG);
  const muatAwal = useRef(buatMuatAwal(m)).current;
  const kirim = useCallback(async (berkas) => {
    setGalat("");
    setMengunggah(true);
    const hasil = await unggahGambar(urlUnggah, await kecilkan(berkas), sectionKey);
    setMengunggah(false);
    if (hasil.galat) setGalat(hasil.galat);
    return hasil.path;
  }, [urlUnggah, sectionKey]);
  const sisipkanGambar = useCallback((jalur) => {
    const editor = editorRef.current;
    if (!editor) return;
    editor.update(() => {
      const selection = m.lexicalCore.$getSelection();
      const node = new m.ImageNode("/storage/" + jalur);
      if (m.lexicalCore.$isRangeSelection(selection)) {
        m.lexicalCore.$insertNodes([node]);
      } else {
        m.lexicalCore.$getRoot().append(node);
      }
    });
  }, []);
  const pilihBerkas = async (e) => {
    const berkas = e.target.files?.[0];
    e.target.value = "";
    if (!berkas) return;
    const jalur = await kirim(berkas);
    if (jalur) sisipkanGambar(jalur);
  };
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsxs(
      m.mComposer.LexicalComposer,
      {
        initialConfig: {
          namespace: `rich-text-${sectionKey}`,
          editable,
          nodes: [m.richText.QuoteNode, m.list.ListNode, m.list.ListItemNode, m.ImageNode],
          onError: (e) => console.error(e),
          editorState: (editor) => muatAwal(editor, nilaiAwal)
        },
        children: [
          /* @__PURE__ */ jsx(
            Toolbar,
            {
              m,
              status,
              mengunggah,
              onGambar: () => berkasRef.current?.click()
            }
          ),
          /* @__PURE__ */ jsx(
            m.mRichTextPlugin.RichTextPlugin,
            {
              contentEditable: /* @__PURE__ */ jsx(
                m.mContentEditable.ContentEditable,
                {
                  className: "min-h-28 px-3 py-2 text-sm outline-none",
                  "aria-placeholder": field.placeholder ?? "",
                  placeholder: /* @__PURE__ */ jsx("p", { className: "pointer-events-none absolute top-2 left-3 text-sm text-muted-foreground", children: field.placeholder ?? "" })
                }
              ),
              ErrorBoundary: m.mErrorBoundary.LexicalErrorBoundary
            }
          ),
          /* @__PURE__ */ jsx(m.mHistory.HistoryPlugin, {}),
          /* @__PURE__ */ jsx(m.mListPlugin.ListPlugin, {}),
          /* @__PURE__ */ jsx(
            Sinkron,
            {
              m,
              editorRef,
              onChangeRef,
              onStatus: setStatus,
              kirim,
              sisipkanGambar
            }
          )
        ]
      }
    ),
    galat ? /* @__PURE__ */ jsx("p", { className: "mt-1 text-xs text-destructive", children: galat }) : null,
    /* @__PURE__ */ jsx(
      "input",
      {
        ref: berkasRef,
        type: "file",
        className: "hidden",
        accept: field.image_accept ?? "image/jpeg,image/png",
        onChange: pilihBerkas
      }
    )
  ] });
}
function Toolbar({
  m,
  status,
  mengunggah,
  onGambar
}) {
  const [editor] = m.mComposerCtx.useLexicalComposerContext();
  const format = (jenis) => editor.dispatchCommand(m.lexicalCore.FORMAT_TEXT_COMMAND, jenis);
  const kutipan = () => {
    editor.update(() => {
      const selection = m.lexicalCore.$getSelection();
      if (!m.lexicalCore.$isRangeSelection(selection)) return;
      m.selection.$setBlocksType(selection, () => status.quote ? m.lexicalCore.$createParagraphNode() : m.richText.$createQuoteNode());
    });
  };
  const daftar = (jenis) => {
    if (jenis === "ordered" && status.ol || jenis === "bullet" && status.ul) {
      editor.dispatchCommand(m.list.REMOVE_LIST_COMMAND, void 0);
      return;
    }
    editor.dispatchCommand(
      jenis === "ordered" ? m.list.INSERT_ORDERED_LIST_COMMAND : m.list.INSERT_UNORDERED_LIST_COMMAND,
      void 0
    );
  };
  const bersihkan = () => {
    editor.update(() => {
      const selection = m.lexicalCore.$getSelection();
      if (!m.lexicalCore.$isRangeSelection(selection)) return;
      ["bold", "italic", "underline"].forEach((f) => {
        if (selection.hasFormat(f)) editor.dispatchCommand(m.lexicalCore.FORMAT_TEXT_COMMAND, f);
      });
      if (status.quote) m.selection.$setBlocksType(selection, () => m.lexicalCore.$createParagraphNode());
    });
    if (status.ol || status.ul) editor.dispatchCommand(m.list.REMOVE_LIST_COMMAND, void 0);
  };
  return /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-0.5 rounded-t-lg border-b border-input bg-muted/40 p-1", children: [
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Tebal", ikon: TextBoldIcon, aktif: status.bold, onKlik: () => format("bold") }),
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Miring", ikon: TextItalicIcon, aktif: status.italic, onKlik: () => format("italic") }),
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Garis bawah", ikon: TextUnderlineIcon, aktif: status.underline, onKlik: () => format("underline") }),
    /* @__PURE__ */ jsx("span", { className: "mx-1 h-5 w-px bg-border" }),
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Kutipan", ikon: QuoteDownIcon, aktif: status.quote, onKlik: kutipan }),
    /* @__PURE__ */ jsx("span", { className: "mx-1 h-5 w-px bg-border" }),
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Daftar bernomor", ikon: LeftToRightListNumberIcon, aktif: status.ol, onKlik: () => daftar("ordered") }),
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Daftar berbutir", ikon: LeftToRightListBulletIcon, aktif: status.ul, onKlik: () => daftar("bullet") }),
    /* @__PURE__ */ jsx("span", { className: "mx-1 h-5 w-px bg-border" }),
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Gambar", ikon: Image01Icon, aktif: false, onKlik: onGambar }),
    /* @__PURE__ */ jsx(TombolFormat, { judul: "Bersihkan format", ikon: EraserIcon, aktif: false, onKlik: bersihkan }),
    mengunggah ? /* @__PURE__ */ jsx("span", { className: "ml-2 text-xs text-primary", children: "Mengunggah gambar…" }) : null
  ] });
}
function Sinkron({
  m,
  editorRef,
  onChangeRef,
  onStatus,
  kirim,
  sisipkanGambar
}) {
  const [editor] = m.mComposerCtx.useLexicalComposerContext();
  editorRef.current = editor;
  useEffect(() => {
    return m.utils.mergeRegister(
      editor.registerUpdateListener(({ editorState }) => {
        editorState.read(() => {
          const root = m.lexicalCore.$getRoot();
          const kosong = root.getTextContent().trim() === "" && !root.getChildren().some((n) => n instanceof m.ImageNode);
          onChangeRef.current(kosong ? "" : m.html.$generateHtmlFromNodes(editor, null));
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
            bold: selection.hasFormat("bold"),
            italic: selection.hasFormat("italic"),
            underline: selection.hasFormat("underline"),
            quote: m.richText.$isQuoteNode(elemen) || m.richText.$isQuoteNode(elemen.getParent()),
            ol: dalamListItem && list?.getListType() === "number",
            ul: dalamListItem && list?.getListType() === "bullet"
          });
        });
      })
    );
  }, [editor]);
  useEffect(() => {
    const root = editor.getRootElement();
    if (!root) return;
    const berkasGambar = (list) => {
      if (!list) return null;
      return Array.from(list).find((f) => f.type.startsWith("image/")) ?? null;
    };
    const tempel = (e) => {
      const berkas = berkasGambar(e.clipboardData?.files);
      if (!berkas) return;
      e.preventDefault();
      void kirim(berkas).then((jalur) => {
        if (jalur) sisipkanGambar(jalur);
      });
    };
    const seret = (e) => {
      const berkas = berkasGambar(e.dataTransfer?.files);
      if (!berkas) return;
      e.preventDefault();
      void kirim(berkas).then((jalur) => {
        if (jalur) sisipkanGambar(jalur);
      });
    };
    root.addEventListener("paste", tempel);
    root.addEventListener("drop", seret);
    return () => {
      root.removeEventListener("paste", tempel);
      root.removeEventListener("drop", seret);
    };
  }, [editor, kirim, sisipkanGambar]);
  return null;
}
const barisKosong = (fields) => Object.fromEntries(fields.map((f) => [f.key, ""]));
function RepeatableGroup({ section, value, onChange }) {
  const { labelMati, babOn, setBabOn, catatanItem } = useWizard();
  const catatan = catatanItem[section.key];
  const fields = section.group_fields ?? section.fields ?? [];
  const minItems = section.min_groups ?? section.min_items ?? 1;
  const tersimpan = (Array.isArray(value) ? value : []).filter((r) => r && typeof r === "object");
  const rows = tersimpan.length ? tersimpan : minItems > 0 ? [barisKosong(fields)] : [];
  const uids = useRef([]);
  const penghitung = useRef(0);
  while (uids.current.length < rows.length) uids.current.push(++penghitung.current);
  if (uids.current.length > rows.length) uids.current.length = rows.length;
  const wajib = section.required ?? true;
  const sakelar = section.toggle_key ?? null;
  const babLain = labelMati[section.key] ?? null;
  const labelMatiBab = babLain?.label ?? null;
  const prefix = section.auto_number ?? "";
  const prefixMati = babLain?.auto_number ?? prefix;
  const labelBeda = labelMatiBab !== null && labelMatiBab !== (section.label ?? null);
  const babMati = Boolean(sakelar) && !babOn;
  const nomor = (i) => (babOn ? prefix : prefixMati) + (i + 1);
  const tulisBaris = (i, tambalan) => onChange(rows.map((r, n) => n === i ? { ...r, ...tambalan } : r));
  const ubahBaris = (i, fkey, teks) => tulisBaris(i, { [fkey]: teks });
  const hapus = (i) => {
    uids.current.splice(i, 1);
    onChange(rows.filter((_, n) => n !== i));
  };
  return /* @__PURE__ */ jsxs("div", { className: "mb-6", children: [
    /* @__PURE__ */ jsxs("div", { className: "mb-2 flex items-center justify-between gap-3", children: [
      /* @__PURE__ */ jsx(Label, { className: "font-semibold", children: labelBeda && !babOn ? labelMatiBab : section.label ?? section.key }),
      sakelar ? /* @__PURE__ */ jsxs("div", { className: "flex shrink-0 items-center gap-2", children: [
        /* @__PURE__ */ jsx(Switch, { id: `sw-${section.key}`, checked: babOn, onCheckedChange: setBabOn }),
        /* @__PURE__ */ jsx(Label, { htmlFor: `sw-${section.key}`, className: "text-sm", children: section.toggle_label ?? "Gunakan bab ini" }),
        /* @__PURE__ */ jsx("input", { type: "hidden", name: `sections[${sakelar}]`, value: babOn ? "1" : "0", readOnly: true })
      ] }) : null
    ] }),
    /* @__PURE__ */ jsx(Bantuan, { teks: section.help }),
    /* @__PURE__ */ jsxs(
      "div",
      {
        className: cn("transition-[opacity,filter]", babMati && "cursor-not-allowed opacity-50 grayscale"),
        inert: babMati || void 0,
        ...!wajib && section.warn_if_empty ? { "data-pp-warn-bab": section.label ?? section.key } : {},
        children: [
          rows.map((row, i) => /* @__PURE__ */ jsxs("div", { "data-pp-baris": true, className: "relative mb-3 rounded-lg border bg-muted/40 p-3", children: [
            /* @__PURE__ */ jsxs("div", { className: "mb-2 flex items-center justify-between", children: [
              prefix ? /* @__PURE__ */ jsx("span", { className: "rounded-md bg-primary px-2 py-0.5 font-mono text-xs text-primary-foreground", children: nomor(i) }) : /* @__PURE__ */ jsx("span", {}),
              /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", size: "sm", onClick: () => hapus(i), children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5 }),
                "Hapus"
              ] })
            ] }),
            fields.map((f) => /* @__PURE__ */ jsx(
              Kolom,
              {
                field: f,
                sectionKey: section.key,
                nama: `sections[${section.key}][${i}][${f.key}]`,
                opsional: !wajib,
                peringatan: f.warn_if_empty ? f.label ?? f.key : null,
                value: row[f.key] ?? "",
                onChange: (teks) => ubahBaris(i, f.key, teks),
                onPilih: f.judul_ke ? (nomor2, judul) => tulisBaris(i, { [f.key]: nomor2, [f.judul_ke]: judul }) : void 0
              },
              f.key
            )),
            /* @__PURE__ */ jsx(CatatanItem, { daftar: catatan?.[String(i)], nilaiKini: Object.values(row).join(" ") })
          ] }, uids.current[i])),
          /* @__PURE__ */ jsxs(
            Button,
            {
              type: "button",
              variant: "outline",
              size: "sm",
              onClick: () => onChange([...rows, barisKosong(fields)]),
              children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 }),
                section.add_button_label ?? "+ Tambah"
              ]
            }
          )
        ]
      }
    )
  ] });
}
function Kolom({
  field,
  sectionKey,
  nama,
  opsional,
  peringatan,
  value,
  onChange,
  onPilih
}) {
  const tanda = {
    ...opsional ? { "data-optional": "" } : {},
    ...peringatan ? { "data-pp-warn": peringatan } : {}
  };
  return /* @__PURE__ */ jsxs("div", { className: "mb-2", children: [
    /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm font-semibold", children: field.label ?? field.key }),
    field.type === "textarea" ? /* @__PURE__ */ jsx(
      Textarea,
      {
        rows: 3,
        ...tanda,
        name: nama,
        value,
        placeholder: field.placeholder ?? "",
        onChange: (e) => onChange(e.target.value)
      }
    ) : field.type === "document_picker" ? /* @__PURE__ */ jsx(
      DocumentPicker,
      {
        value,
        onChange,
        nama,
        opsional,
        peringatan,
        placeholder: field.placeholder,
        onPilih
      }
    ) : field.type === "image" ? /* @__PURE__ */ jsx(ImageUpload, { value, onChange, sectionKey, field, nama }) : field.type === "rich_text" ? /* @__PURE__ */ jsx(
      RichText,
      {
        value,
        onChange,
        sectionKey,
        field,
        nama,
        opsional,
        peringatan
      }
    ) : /* @__PURE__ */ jsx(
      Input,
      {
        ...tanda,
        name: nama,
        value,
        placeholder: field.placeholder ?? "",
        onChange: (e) => onChange(e.target.value)
      }
    )
  ] });
}
function RichList({ section, value, onChange }) {
  const catatan = useWizard().catatanItem[section.key];
  const prefix = section.auto_number ?? "";
  const items = (Array.isArray(value) ? value : []).length ? value : [""];
  const idDatalist = `dl-${section.key}`;
  const ubah = (i, teks) => onChange(items.map((v, n) => n === i ? teks : v));
  const hapus = (i) => {
    const sisa = items.filter((_, n) => n !== i);
    onChange(sisa.length ? sisa : [""]);
  };
  return /* @__PURE__ */ jsxs("div", { className: "mb-6", children: [
    /* @__PURE__ */ jsx(Label, { className: "mb-1.5 font-semibold", children: section.label ?? section.key }),
    /* @__PURE__ */ jsx(Bantuan, { teks: section.help }),
    section.suggestions ? /* @__PURE__ */ jsx("datalist", { id: idDatalist, children: section.suggestions.map((s) => /* @__PURE__ */ jsx("option", { value: s }, s)) }) : null,
    items.map((item, i) => (
      // Kunci INDEKS di sini aman — berbeda dengan RepeatableGroup:
      // barisnya cuma satu <input> tanpa keadaan internal, jadi
      // pemakaian ulang DOM tak bisa membuat isinya tertukar.
      // eslint-disable-next-line react/no-array-index-key
      /* @__PURE__ */ jsxs("div", { className: "mb-2", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex gap-2", children: [
          prefix ? /* @__PURE__ */ jsxs("span", { className: "flex h-8 shrink-0 items-center rounded-lg border border-input bg-muted px-2.5 font-mono text-sm", children: [
            prefix,
            i + 1
          ] }) : null,
          /* @__PURE__ */ jsx(
            Input,
            {
              className: "flex-1",
              name: `sections[${section.key}][]`,
              value: item ?? "",
              list: section.suggestions ? idDatalist : void 0,
              placeholder: section.suggestions ? "Masukkan referensi..." : "Masukkan poin...",
              onChange: (e) => ubah(i, e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(Button, { type: "button", variant: "outline", size: "icon", className: "size-8 shrink-0", onClick: () => hapus(i), title: "Hapus", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 1.5 }) })
        ] }),
        /* @__PURE__ */ jsx(CatatanItem, { daftar: catatan?.[String(i)], nilaiKini: item ?? "" })
      ] }, i)
    )),
    /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", size: "sm", onClick: () => onChange([...items, ""]), children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 }),
      "Tambah Poin"
    ] })
  ] });
}
function Text({ section, value, onChange }) {
  const val = typeof value === "string" && value !== "" ? value : section.default ?? "";
  const nama = `sections[${section.key}]`;
  const tipe = section.type ?? "text";
  return /* @__PURE__ */ jsxs("div", { className: "mb-6", children: [
    /* @__PURE__ */ jsx(Label, { className: "mb-1.5 font-semibold", htmlFor: nama, children: section.label ?? section.key }),
    /* @__PURE__ */ jsx(Bantuan, { teks: section.help }),
    tipe === "textarea" ? /* @__PURE__ */ jsx(
      Textarea,
      {
        id: nama,
        name: nama,
        rows: 4,
        value: val,
        placeholder: section.placeholder ?? "",
        onChange: (e) => onChange(e.target.value)
      }
    ) : /* @__PURE__ */ jsx(
      Input,
      {
        id: nama,
        name: nama,
        type: tipe === "date" ? "date" : "text",
        value: val,
        placeholder: section.placeholder ?? "",
        onChange: (e) => onChange(e.target.value)
      }
    )
  ] });
}
const KOSONG = "__kosong__";
function UserPicker(props) {
  const { section } = props;
  const { candidates, ketersediaan, papan } = useWizard();
  const multiple = section.multiple ?? false;
  if (section.key in papan && !multiple) {
    return /* @__PURE__ */ jsx(PapanKetersediaan, { ...props });
  }
  const options = candidates[section.key] ?? [];
  const jadwal = ketersediaan[section.key] ?? {};
  return multiple ? /* @__PURE__ */ jsx(Berulang, { ...props, options, jadwal }) : /* @__PURE__ */ jsx(Tunggal, { ...props, options });
}
function Berulang({
  section,
  value,
  onChange,
  options,
  jadwal
}) {
  const required = section.required ?? false;
  const terpilih = Array.isArray(value) ? value : [];
  const items = terpilih.length ? terpilih : [""];
  const ubah = (i, id) => onChange(items.map((v, n) => n === i ? id === KOSONG ? "" : Number(id) : v));
  const hapus = (i) => {
    const sisa = items.filter((_, n) => n !== i);
    onChange(sisa.length ? sisa : [""]);
  };
  return /* @__PURE__ */ jsxs("div", { className: "mb-6", children: [
    /* @__PURE__ */ jsxs(Label, { className: "mb-1.5 font-semibold", children: [
      section.label ?? section.key,
      required ? /* @__PURE__ */ jsx("span", { className: "text-destructive", children: " *" }) : null
    ] }),
    /* @__PURE__ */ jsx(Bantuan, { teks: section.hint }),
    items.map((sel, i) => (
      // eslint-disable-next-line react/no-array-index-key
      /* @__PURE__ */ jsxs("div", { className: "mb-2 flex gap-2", "data-pp-pilih-kotak": true, children: [
        /* @__PURE__ */ jsxs(Select, { value: sel ? String(sel) : KOSONG, onValueChange: (v) => ubah(i, v), children: [
          /* @__PURE__ */ jsx(SelectTrigger, { className: "h-8 flex-1", children: /* @__PURE__ */ jsx(SelectValue, { placeholder: "— Pilih —" }) }),
          /* @__PURE__ */ jsxs(SelectContent, { children: [
            /* @__PURE__ */ jsx(SelectItem, { value: KOSONG, children: "— Pilih —" }),
            options.map((o) => /* @__PURE__ */ jsx(SelectItem, { value: String(o.id), children: labelKandidat(o, jadwal[o.id]) }, o.id))
          ] })
        ] }),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "hidden",
            "data-pp-pilih": true,
            ...required ? {} : { "data-optional": "" },
            name: `sections[${section.key}][]`,
            value: sel ? String(sel) : "",
            readOnly: true
          }
        ),
        /* @__PURE__ */ jsx(Button, { type: "button", variant: "outline", size: "icon", className: "size-8 shrink-0", onClick: () => hapus(i), children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 1.5 }) })
      ] }, i)
    )),
    /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", size: "sm", onClick: () => onChange([...items, ""]), children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 }),
      "Tambah Pembuat"
    ] })
  ] });
}
function Tunggal({ section, value, onChange, options }) {
  const required = section.required ?? false;
  const sel = value === null || value === void 0 || value === "" ? "" : String(value);
  return /* @__PURE__ */ jsxs("div", { className: "mb-6", "data-pp-pilih-kotak": true, children: [
    /* @__PURE__ */ jsxs(Label, { className: "mb-1.5 font-semibold", children: [
      section.label ?? section.key,
      required ? /* @__PURE__ */ jsx("span", { className: "text-destructive", children: " *" }) : null
    ] }),
    /* @__PURE__ */ jsx(Bantuan, { teks: section.hint }),
    /* @__PURE__ */ jsxs(Select, { value: sel || KOSONG, onValueChange: (v) => onChange(v === KOSONG ? "" : Number(v)), children: [
      /* @__PURE__ */ jsx(SelectTrigger, { className: "h-8 w-full", children: /* @__PURE__ */ jsx(SelectValue, { placeholder: "— Pilih —" }) }),
      /* @__PURE__ */ jsxs(SelectContent, { children: [
        /* @__PURE__ */ jsx(SelectItem, { value: KOSONG, children: "— Pilih —" }),
        options.map((o) => /* @__PURE__ */ jsx(SelectItem, { value: String(o.id), children: labelKandidat(o) }, o.id))
      ] })
    ] }),
    /* @__PURE__ */ jsx(
      "input",
      {
        type: "hidden",
        "data-pp-pilih": true,
        ...required ? {} : { "data-optional": "" },
        name: `sections[${section.key}]`,
        value: sel,
        readOnly: true
      }
    ),
    options.length === 0 ? /* @__PURE__ */ jsxs("p", { className: "mt-1 flex items-center gap-1.5 text-xs text-chart-3", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Alert02Icon, strokeWidth: 1.5, className: "size-3.5" }),
      "Belum ada kandidat untuk peran ini."
    ] }) : null
  ] });
}
function labelKandidat(o, a) {
  const off = a?.off ? a.jenis : null;
  return `${o.nama} — ${o.nrp} — ${o.dept}${off ? ` · sedang ${off.toLowerCase()}` : ""}`;
}
const PETA = {
  rich_list: RichList,
  reference_picker: RichList,
  repeatable_group: RepeatableGroup,
  jsa_analysis: JsaAnalysis,
  user_picker: UserPicker,
  text: Text,
  textarea: Text,
  date: Text
  // widget tanggal (bukan diketik)
};
function Seksi({ section, value, onChange }) {
  const Komponen = PETA[section.type] ?? Text;
  return /* @__PURE__ */ jsx(Komponen, { section, value, onChange });
}
const TERISI = (el) => el.type !== "file" && String(el.value).trim() !== "";
function dilewati(el) {
  return Boolean(el.disabled) || el.dataset.optional !== void 0 || el.offsetParent === null || el.closest("[inert]") !== null;
}
function validasiWajib(root) {
  let pertama = null;
  const tandai = (el, kosong) => {
    if (kosong) {
      el.setAttribute("aria-invalid", "true");
      if (!pertama) pertama = el;
    } else {
      el.removeAttribute("aria-invalid");
    }
  };
  root.querySelectorAll(
    "input:not([type=hidden]):not([type=file]):not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]), textarea, select"
  ).forEach((el) => {
    if (dilewati(el)) return;
    tandai(el, !String(el.value).trim());
  });
  const grup = {};
  root.querySelectorAll("input[type=radio][required]").forEach((el) => {
    if (el.disabled || el.offsetParent === null) return;
    (grup[el.name] ??= []).push(el);
  });
  Object.values(grup).forEach((radios) => {
    const terpilih = radios.some((el) => el.checked);
    radios.forEach((el) => tandai(el, !terpilih));
    if (!terpilih && !pertama) pertama = radios[0];
  });
  root.querySelectorAll("input[type=hidden][data-pp-pilih]").forEach((el) => {
    const kotak = el.closest("[data-pp-pilih-kotak]");
    if (!kotak || el.dataset.optional !== void 0 || kotak.offsetParent === null) return;
    const pemicu = kotak.querySelector("[data-slot=select-trigger]");
    if (pemicu) tandai(pemicu, !String(el.value).trim());
  });
  root.querySelectorAll("input[type=hidden][data-pp-rich]").forEach((el) => {
    const kotak = el.closest(".pp-rt");
    if (!kotak || el.dataset.optional !== void 0 || kotak.offsetParent === null) return;
    const kosong = !String(el.value).trim();
    kotak.classList.toggle("pp-rt-invalid", kosong);
    if (kosong && !pertama) pertama = kotak.querySelector("[contenteditable=true]") ?? kotak;
  });
  return pertama;
}
function warnKosong(root) {
  const perKolom = Array.from(root.querySelectorAll("[data-pp-warn]")).filter((el) => {
    if (String(el.value).trim() !== "") return false;
    const baris = el.closest("[data-pp-baris]");
    if (!baris) return false;
    return Array.from(baris.querySelectorAll("input, textarea")).some(
      (lain) => lain !== el && TERISI(lain)
    );
  }).map((el) => ({ el, label: el.getAttribute("data-pp-warn") ?? "" }));
  const perBab = Array.from(root.querySelectorAll("[data-pp-warn-bab]")).filter(
    (bab) => !Array.from(bab.querySelectorAll("input, textarea")).some(TERISI)
  ).map((bab) => ({
    el: bab.querySelector("input, textarea"),
    label: `${bab.getAttribute("data-pp-warn-bab")} (belum diisi sama sekali)`
  })).filter((w) => w.el !== null && w.el.offsetParent !== null && !w.el.closest("[inert]"));
  return [...perBab, ...perKolom];
}
function antarKe(el) {
  el.scrollIntoView({ behavior: "smooth", block: "center" });
  setTimeout(() => {
    try {
      el.focus({ preventScroll: true });
    } catch {
    }
  }, 250);
}
const JUDUL_LOG = "Log Revisi (Catatan Perubahan)";
function DocumentsEdit() {
  const props = usePage().props;
  const { document: doc, schema, currentStep, totalSteps, isRevLogStep, editable, previewV, rujukanPdfUrl } = props;
  const langkahSchema = schema.steps ?? [];
  const pakaiLogRevisi = totalSteps > langkahSchema.length;
  const [versi, setVersi] = useState(previewV);
  useEffect(() => setVersi(previewV), [previewV]);
  const judulLangkah = isRevLogStep ? JUDUL_LOG : langkahSchema.find((s) => s.step === currentStep)?.title ?? `Langkah ${currentStep}`;
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: doc.judul,
      remah: [{ label: "Dokumen", href: route("documents.index") }, { label: "Pengisian" }],
      sub: (
        // `sub` dirender di dalam <p>; semua yang di bawah ini <span>,
        // jadi susunannya tetap sah. Lencana & status memakai komponen
        // yang sama dengan daftar dokumen — nol hex diketik di sini,
        // warnanya tetap dari props `statusMeta` (CLAUDE.md §4).
        /* @__PURE__ */ jsxs("span", { className: "flex flex-wrap items-center gap-x-2 gap-y-1", children: [
          /* @__PURE__ */ jsx("span", { className: "text-primary font-mono font-semibold", children: doc.nomor }),
          doc.nomorFinal ? null : /* @__PURE__ */ jsx(Badge, { variant: "outline", title: "Nomor final dikunci setelah disetujui", children: "sementara" }),
          /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: doc.jenis }),
          /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: doc.departemen }),
          /* @__PURE__ */ jsx(StatusBadge, { status: doc.status }),
          /* @__PURE__ */ jsx("span", { "aria-hidden": "true", children: "·" }),
          /* @__PURE__ */ jsxs("span", { children: [
            "Pembuat: ",
            doc.pembuat
          ] }),
          /* @__PURE__ */ jsx("span", { "aria-hidden": "true", children: "·" }),
          /* @__PURE__ */ jsxs("span", { children: [
            "No. Revisi: ",
            doc.noRevisi
          ] })
        ] })
      ),
      aksi: /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: File01Icon, strokeWidth: 1.5 }),
        "Lihat PDF"
      ] }) }),
      children: [
        /* @__PURE__ */ jsx(
          RailLangkah,
          {
            langkah: [
              ...langkahSchema.map((s) => ({ n: s.step, judul: s.title ?? "" })),
              ...pakaiLogRevisi ? [{ n: langkahSchema.length + 1, judul: JUDUL_LOG }] : []
            ],
            aktif: currentStep
          }
        ),
        editable ? null : /* @__PURE__ */ jsxs("p", { className: "border-chart-3/40 bg-chart-3/10 flex items-center gap-2 rounded-xl border px-3 py-2 text-sm", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: LockIcon, strokeWidth: 1.5, className: "size-4 shrink-0" }),
          /* @__PURE__ */ jsxs("span", { children: [
            "Dokumen berstatus ",
            /* @__PURE__ */ jsx("strong", { children: props.statusLabels[doc.status] ?? doc.status }),
            " — mode baca."
          ] })
        ] }),
        /* @__PURE__ */ jsx(
          UmpanBalikPeninjau,
          {
            rangkuman: props.reviewSummary,
            catatanItem: props.catatanItem,
            yatim: props.catatanYatim,
            langkah: langkahSchema,
            aktif: currentStep
          }
        ),
        /* @__PURE__ */ jsxs("div", { className: "grid gap-4 lg:grid-cols-12", children: [
          /* @__PURE__ */ jsx("div", { className: "lg:col-span-7", children: /* @__PURE__ */ jsx(FormLangkah, { judulLangkah, versi, onVersi: setVersi }, `${doc.id}:${currentStep}`) }),
          /* @__PURE__ */ jsx("div", { className: "lg:col-span-5", children: /* @__PURE__ */ jsx(PanelPratinjau, { documentId: doc.id, versi, rujukanPdfUrl }) })
        ] })
      ]
    }
  );
}
function RailLangkah({ langkah, aktif }) {
  return /* @__PURE__ */ jsx("ol", { className: "ring-foreground/10 bg-card flex flex-wrap items-center gap-x-2 gap-y-1 rounded-2xl px-4 py-2 ring-1", children: langkah.map(({ n, judul }, i) => /* @__PURE__ */ jsxs("li", { className: "flex items-center gap-2", children: [
    /* @__PURE__ */ jsx(
      "span",
      {
        className: cn(
          "inline-flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums",
          n === aktif ? "bg-primary text-primary-foreground" : n < aktif ? "bg-chart-2 text-primary-foreground" : "bg-muted text-muted-foreground"
        ),
        "aria-current": n === aktif ? "step" : void 0,
        children: n
      }
    ),
    /* @__PURE__ */ jsx("span", { className: cn("text-sm", n === aktif ? "text-foreground font-semibold" : "text-muted-foreground"), children: judul }),
    i < langkah.length - 1 ? /* @__PURE__ */ jsx(
      HugeiconsIcon,
      {
        icon: ArrowRight01Icon,
        strokeWidth: 1.5,
        className: "text-muted-foreground mx-1 size-3.5",
        "aria-hidden": "true"
      }
    ) : null
  ] }, n)) });
}
function UmpanBalikPeninjau({
  rangkuman,
  catatanItem,
  yatim,
  langkah,
  aktif
}) {
  const jumlahSeksi = (key) => Object.values(catatanItem[key] ?? {}).reduce((t, daftar) => t + daftar.length, 0);
  const perLangkah = langkah.map((s) => ({
    n: s.step,
    jumlah: (s.sections ?? []).reduce((t, sec) => t + jumlahSeksi(sec.key), 0)
  })).filter((s) => s.jumlah > 0);
  if (!rangkuman && perLangkah.length === 0 && yatim.length === 0) return null;
  return /* @__PURE__ */ jsxs("div", { className: "border-destructive/40 bg-destructive/10 rounded-xl border px-3 py-2 text-sm", children: [
    rangkuman ? /* @__PURE__ */ jsxs("p", { className: "border-destructive/30 mb-2 border-b pb-2 whitespace-pre-line", children: [
      /* @__PURE__ */ jsx("strong", { children: "Rangkuman Peninjau:" }),
      " ",
      rangkuman
    ] }) : null,
    perLangkah.length > 0 ? /* @__PURE__ */ jsxs("p", { className: "flex flex-wrap items-center gap-1.5", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Message01Icon, strokeWidth: 1.5, className: "size-4 shrink-0" }),
      /* @__PURE__ */ jsx("span", { className: "font-semibold", children: "Catatan per item:" }),
      perLangkah.map((s) => /* @__PURE__ */ jsxs(Badge, { variant: s.n === aktif ? "destructive" : "outline", children: [
        s.jumlah,
        " di Langkah ",
        s.n
      ] }, s.n)),
      /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "— tampil di bawah isian masing-masing." })
    ] }) : null,
    yatim.length > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx("p", { className: "mt-2 font-semibold", children: "Catatan tanpa item — periksa manual:" }),
      /* @__PURE__ */ jsx("ul", { className: "mt-1 space-y-1", children: yatim.map((c, i) => (
        // eslint-disable-next-line react/no-array-index-key
        /* @__PURE__ */ jsxs("li", { children: [
          /* @__PURE__ */ jsxs(Badge, { variant: "outline", className: "mr-1.5", children: [
            c.bagian,
            " · ",
            c.item
          ] }),
          c.komentar,
          /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
            " — ",
            c.oleh
          ] })
        ] }, i)
      )) })
    ] }) : null
  ] });
}
function FormLangkah({
  judulLangkah,
  versi,
  onVersi
}) {
  const props = usePage().props;
  const {
    document: doc,
    schema,
    contentMap,
    userValues,
    currentStep,
    totalSteps,
    isRevLogStep,
    editable,
    babAktif,
    judulLangkahMati,
    revisiKirim,
    tanggalCetak
  } = props;
  const seksiLangkah = schema.steps?.find((s) => s.step === currentStep)?.sections ?? [];
  const terakhir = currentStep >= totalSteps;
  const [babOn, setBabOn] = useState(babAktif);
  const [nilai, setNilai] = useState(
    () => Object.fromEntries(
      seksiLangkah.map((sec) => [
        sec.key,
        sec.type === "user_picker" ? userValues[sec.key] ?? null : contentMap[sec.key] ?? null
      ])
    )
  );
  const [logRows, setLogRows] = useState(() => {
    const tersimpan = contentMap.catatan_revisi;
    return Array.isArray(tersimpan) && tersimpan.length ? tersimpan : [barisLogBaru()];
  });
  const [edisi, setEdisi] = useState(doc.edisi);
  const [noRevisi, setNoRevisi] = useState(doc.noRevisi);
  const [tanggal, setTanggal] = useState(tanggalCetak);
  const [pesanRevisi, setPesanRevisi] = useState("");
  const [menyusunRevisi, setMenyusunRevisi] = useState(false);
  const salinanArsip = tanggalCetak !== null;
  const [savedAt, setSavedAt] = useState("");
  const [previewing, setPreviewing] = useState(false);
  const [mengirim, setMengirim] = useState(false);
  const [dialog, setDialog] = useState(null);
  const [kosong, setKosong] = useState([]);
  const formRef = useRef(null);
  const muatan = () => {
    const sections = { ...nilai };
    seksiLangkah.forEach((sec) => {
      if (sec.toggle_key) sections[sec.toggle_key] = babOn ? "1" : "0";
    });
    if (isRevLogStep) sections.catatan_revisi = logRows;
    return {
      step: currentStep,
      sections,
      ...isRevLogStep ? { edisi, no_revisi: noRevisi } : {},
      ...isRevLogStep && tanggal ? { tanggal_terbit: tanggal.terbit, tanggal_revisi: tanggal.revisi } : {}
    };
  };
  const muatanRef = useRef(muatan);
  muatanRef.current = muatan;
  const simpanDiam = async () => {
    try {
      const r = await fetch(route("documents.autosave", doc.id), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-XSRF-TOKEN": xsrf()
        },
        body: JSON.stringify(muatanRef.current())
      });
      if (!r.ok) {
        toast.error("Gagal menyimpan otomatis.");
        return null;
      }
      return await r.json();
    } catch {
      return null;
    }
  };
  const pertama = useRef(true);
  useEffect(() => {
    if (!editable) return;
    if (pertama.current) {
      pertama.current = false;
      return;
    }
    const t = setTimeout(() => {
      if (mengirim) return;
      void simpanDiam().then((j) => {
        if (j?.saved_at) setSavedAt(j.saved_at);
      });
    }, 1200);
    return () => clearTimeout(t);
  }, [nilai, babOn, logRows, edisi, noRevisi]);
  const pratinjau = async () => {
    setPreviewing(true);
    const j = await simpanDiam();
    onVersi(`${j?.v ?? versi}-${Date.now()}`);
    setPreviewing(false);
  };
  const kirimLangkah = (action) => {
    setMengirim(true);
    router.post(route("documents.saveStep", doc.id), { ...muatan(), action }, {
      onFinish: () => setMengirim(false)
    });
  };
  const isiLogOtomatis = async () => {
    setPesanRevisi("");
    setMenyusunRevisi(true);
    try {
      const r = await fetch(route("documents.revisi.usulan", doc.id), {
        headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" }
      });
      if (!r.ok) throw new Error();
      const j = await r.json();
      if (!j.baris.length) {
        setPesanRevisi("Belum ada bab yang berbeda dari versi yang berlaku — isi dokumen dulu, lalu tekan Revisi lagi.");
        return;
      }
      const tetap = logRows.filter(barisLogBerisi);
      const urut = tetap.length ? Number(tetap[tetap.length - 1]?.no_rev) || 0 : (Number(j.no_rev) || 1) - 1;
      const babBerubah = j.baris;
      const halaman = [...new Set(babBerubah.map((b) => b.halaman).filter(Boolean))].join(", ");
      setLogRows([
        ...tetap,
        {
          no_rev: urut + 1,
          tanggal: j.tanggal,
          halaman,
          catatan: "",
          sub: babBerubah.map((b) => ({ catatan: "", bab: b.bab }))
        }
      ]);
    } catch {
      setPesanRevisi("Gagal menyusun catatan revisi. Coba lagi.");
    } finally {
      setMenyusunRevisi(false);
    }
  };
  const lanjut = (action) => {
    const root = formRef.current;
    if (!root) return;
    const belum = validasiWajib(root);
    if (belum) {
      toast.error("Ada kolom yang belum diisi", {
        description: "Lengkapi semua kolom yang ditandai merah sebelum melanjutkan."
      });
      antarKe(belum);
      return;
    }
    if (action === "next") {
      kirimLangkah("next");
      return;
    }
    const sisa = warnKosong(root);
    if (sisa.length) {
      setKosong(sisa);
      setDialog("kosong");
      return;
    }
    setDialog("kirim");
  };
  const konteks = {
    documentId: doc.id,
    editable,
    dokumenBerlaku: props.dokumenBerlaku,
    labelMati: props.labelMati,
    babOn,
    setBabOn,
    candidates: props.candidates,
    ketersediaan: props.ketersediaan,
    papan: props.papan,
    ambang: props.ambang,
    catatanItem: props.catatanItem
  };
  const judul = isRevLogStep ? judulLangkah : babOn ? judulLangkah : judulLangkahMati ?? judulLangkah;
  return /* @__PURE__ */ jsxs(PenyediaWizard, { value: konteks, children: [
    /* @__PURE__ */ jsx("form", { ref: formRef, onSubmit: (e) => e.preventDefault(), children: /* @__PURE__ */ jsxs(Card, { className: "relative gap-0 py-0", children: [
      menyusunRevisi ? /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "absolute inset-x-0 top-0 h-0.5 overflow-hidden rounded-t-4xl", children: /* @__PURE__ */ jsx("span", { className: "garis-lari bg-primary block h-full w-1/4" }) }) : null,
      /* @__PURE__ */ jsxs(CardHeader, { className: "flex items-center justify-between border-b px-4 py-3", children: [
        /* @__PURE__ */ jsxs("span", { className: "font-semibold", children: [
          "Langkah ",
          currentStep,
          " — ",
          judul
        ] }),
        savedAt ? /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground flex items-center gap-1.5 text-sm", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CloudSavingDone01Icon, strokeWidth: 1.5, className: "size-4" }),
          "Tersimpan otomatis ",
          savedAt
        ] }) : null
      ] }),
      /* @__PURE__ */ jsx(CardContent, { className: "p-4", children: isRevLogStep ? /* @__PURE__ */ jsx(
        RevisionLog,
        {
          rows: logRows,
          onRows: setLogRows,
          edisi,
          noRevisi,
          onEdisi: setEdisi,
          onNoRevisi: setNoRevisi,
          judul: doc.judul,
          revisiKirim,
          pesan: pesanRevisi,
          tanggal,
          onTanggal: (patch) => setTanggal((lama) => lama ? { ...lama, ...patch } : lama),
          adaTombolRevisi: !salinanArsip
        }
      ) : seksiLangkah.map((sec) => /* @__PURE__ */ jsx(
        Seksi,
        {
          section: sec,
          value: nilai[sec.key],
          onChange: (v) => setNilai((lama) => ({ ...lama, [sec.key]: v }))
        },
        sec.key
      )) }),
      /* @__PURE__ */ jsxs(CardFooter, { className: "flex justify-between border-t px-4 py-3", children: [
        /* @__PURE__ */ jsx("div", { children: currentStep > 1 ? editable ? /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", onClick: () => kirimLangkah("back"), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeft01Icon, strokeWidth: 1.5 }),
          "Kembali"
        ] }) : /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.edit", { document: doc.id, view_step: currentStep - 1 }), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeft01Icon, strokeWidth: 1.5 }),
          "Kembali"
        ] }) }) : null }),
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap gap-2", children: [
          /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", disabled: previewing, onClick: pratinjau, children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5 }),
            "Preview"
          ] }),
          isRevLogStep && editable && !salinanArsip ? /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", disabled: menyusunRevisi, onClick: isiLogOtomatis, children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RefreshCwIcon, strokeWidth: 1.5 }),
            "Revisi"
          ] }) : null,
          editable ? terakhir ? /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", onClick: () => kirimLangkah("save"), children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FloppyDiskIcon, strokeWidth: 1.5 }),
              "Simpan"
            ] }),
            /* @__PURE__ */ jsxs(Button, { type: "button", onClick: () => lanjut("submit"), children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: SentIcon, strokeWidth: 1.5 }),
              "Kirim"
            ] })
          ] }) : /* @__PURE__ */ jsxs(Button, { type: "button", onClick: () => lanjut("next"), children: [
            "Langkah Berikutnya",
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5 })
          ] }) : terakhir ? null : /* @__PURE__ */ jsx(Button, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("documents.edit", { document: doc.id, view_step: currentStep + 1 }), children: [
            "Langkah Berikutnya",
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5 })
          ] }) })
        ] })
      ] })
    ] }) }),
    /* @__PURE__ */ jsx(AlertDialog, { open: dialog === "kosong", onOpenChange: (b) => setDialog(b ? "kosong" : null), children: /* @__PURE__ */ jsxs(AlertDialogContent, { children: [
      /* @__PURE__ */ jsxs(AlertDialogHeader, { children: [
        /* @__PURE__ */ jsx(AlertDialogTitle, { children: "Ada isian yang masih kosong" }),
        /* @__PURE__ */ jsx(AlertDialogDescription, { asChild: true, children: /* @__PURE__ */ jsxs("div", { children: [
          "Kolom berikut belum diisi:",
          /* @__PURE__ */ jsx("ul", { className: "mt-2 list-inside list-disc", children: [...new Set(kosong.map((k) => k.label))].map((l) => /* @__PURE__ */ jsx("li", { children: l }, l)) }),
          /* @__PURE__ */ jsx("p", { className: "mt-2 text-sm", children: "Kolom ini tidak wajib — dokumen tetap bisa dikirim tanpanya." })
        ] }) })
      ] }),
      /* @__PURE__ */ jsxs(AlertDialogFooter, { children: [
        /* @__PURE__ */ jsx(AlertDialogCancel, { onClick: () => setDialog("kosongLanjut"), children: "Tetap kirim" }),
        /* @__PURE__ */ jsx(
          AlertDialogAction,
          {
            onClick: () => {
              setDialog(null);
              if (kosong[0]) antarKe(kosong[0].el);
            },
            children: "Isi dulu"
          }
        )
      ] })
    ] }) }),
    /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        buka: dialog === "kosongLanjut",
        onUbahBuka: (b) => setDialog(b ? "kosongLanjut" : null),
        judul: "Kirim tanpa mengisinya?",
        pesan: "Dokumen tidak bisa diedit setelah dikirim (masih bisa Ditarik selama belum ditinjau).",
        tombolYa: "Ya, kirim apa adanya",
        onKonfirmasi: () => kirimLangkah("submit")
      }
    ),
    /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        buka: dialog === "kirim",
        onUbahBuka: (b) => setDialog(b ? "kirim" : null),
        judul: "Kirim Dokumen?",
        pesan: "Kirim dokumen untuk ditinjau? Dokumen tidak bisa diedit setelah dikirim (masih bisa Ditarik selama belum ditinjau).",
        tombolYa: "Ya, kirim",
        onKonfirmasi: () => kirimLangkah("submit")
      }
    )
  ] });
}
function PanelPratinjau({
  documentId,
  versi,
  rujukanPdfUrl
}) {
  const [tab, setTab] = useState("pratinjau");
  const [rujukanDimuat, setRujukanDimuat] = useState(false);
  const src = `${route("documents.pdf", documentId)}?v=${versi}#toolbar=0&navpanes=0&view=Fit`;
  return /* @__PURE__ */ jsxs(Card, { className: "sticky top-4 gap-0 py-0", children: [
    /* @__PURE__ */ jsx(CardHeader, { className: "flex items-center justify-between border-b px-4 py-2", children: rujukanPdfUrl ? (
      // Dua tab, bukan dua panel bersanding: layar 1366px tak cukup
      // untuk formulir + dua A4, dan yang dibutuhkan memang
      // bergantian — baca rujukan, lalu ketik.
      /* @__PURE__ */ jsxs("div", { className: "flex gap-1", children: [
        /* @__PURE__ */ jsxs(TabPratinjau, { aktif: tab === "pratinjau", onKlik: () => setTab("pratinjau"), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-3.5" }),
          "Pratinjau"
        ] }),
        /* @__PURE__ */ jsxs(
          TabPratinjau,
          {
            aktif: tab === "referensi",
            onKlik: () => {
              setTab("referensi");
              setRujukanDimuat(true);
            },
            children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: File01Icon, strokeWidth: 1.5, className: "size-3.5" }),
              "Referensi"
            ]
          }
        )
      ] })
    ) : /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-1.5 text-sm font-semibold", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }),
      "Pratinjau PDF"
    ] }) }),
    /* @__PURE__ */ jsxs(CardContent, { className: "relative h-[78vh] bg-[#525659] p-0", children: [
      /* @__PURE__ */ jsxs("div", { className: "absolute inset-0 flex flex-col items-center justify-center gap-2 text-sm text-white/60", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: File01Icon, strokeWidth: 1.5, className: "size-8" }),
        "Menyiapkan pratinjau…"
      ] }),
      /* @__PURE__ */ jsx(
        "iframe",
        {
          id: "previewFrame",
          title: "Pratinjau PDF",
          src,
          className: cn("relative size-full border-0", tab === "pratinjau" ? "block" : "hidden")
        }
      ),
      rujukanPdfUrl ? /* @__PURE__ */ jsx(
        "iframe",
        {
          title: "Berkas dokumen lama",
          src: rujukanDimuat ? `${rujukanPdfUrl}#toolbar=1&navpanes=0&view=Fit` : void 0,
          className: cn("relative size-full border-0", tab === "referensi" ? "block" : "hidden")
        }
      ) : null
    ] })
  ] });
}
function TabPratinjau({ aktif, onKlik, children }) {
  return /* @__PURE__ */ jsx(
    "button",
    {
      type: "button",
      onClick: onKlik,
      className: cn(
        "focus-visible:ring-ring/50 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm transition-colors focus-visible:ring-[3px] focus-visible:outline-none",
        aktif ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:bg-accent"
      ),
      children
    }
  );
}
export {
  DocumentsEdit as default
};
