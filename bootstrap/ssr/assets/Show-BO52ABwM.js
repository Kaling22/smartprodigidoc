import { jsxs, Fragment, jsx } from "react/jsx-runtime";
import { Exchange01Icon, RoboticIcon, HourglassIcon, SparklesIcon, Tick02Icon, Cancel01Icon, Alert02Icon, ShieldCheckIcon, TickDouble01Icon, OctagonXIcon, Message01Icon, InformationCircleIcon, File01Icon, SentIcon, ArrowTurnBackwardIcon, Album02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, usePage, router, Link } from "@inertiajs/react";
import { useState, useRef, useEffect, createContext, useContext } from "react";
import { D as Dialog, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, g as DialogFooter, B as Badge, A as AppLayout } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, b as CardHeader } from "./card-B3VJCD2M.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { L as Label } from "./label-emiz6fAI.js";
import { T as Textarea } from "./textarea-CFf6776c.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { d as PapanPilihPeninjau, x as xsrf } from "./PapanKetersediaan-Hogkj4By.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { c as cn } from "../uji-render.js";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
function DialogAlihkan({
  buka,
  onUbahBuka,
  nomor,
  url,
  kandidat,
  ketersediaan,
  ambang
}) {
  const [konfirmasi, setKonfirmasi] = useState(false);
  const form = useForm({ tujuan: null, alasan: "" });
  const kirim = () => form.post(url, {
    preserveScroll: true,
    onSuccess: () => {
      onUbahBuka(false);
      form.reset();
    }
  });
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx(Dialog, { open: buka, onOpenChange: onUbahBuka, children: /* @__PURE__ */ jsxs(DialogContent, { className: "sm:max-w-2xl", children: [
      /* @__PURE__ */ jsxs(DialogHeader, { children: [
        /* @__PURE__ */ jsxs(DialogTitle, { className: "flex items-center gap-1.5", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Exchange01Icon, strokeWidth: 1.5, className: "size-4" }),
          "Alihkan Peninjauan — ",
          /* @__PURE__ */ jsx("span", { className: "font-mono", children: nomor })
        ] }),
        /* @__PURE__ */ jsx(DialogDescription, { children: "Peninjauan langsung berpindah ke peninjau yang dipilih dan tidak lagi muncul di antrian Anda." })
      ] }),
      /* @__PURE__ */ jsxs(
        "form",
        {
          className: "max-h-[65vh] overflow-y-auto px-1",
          onSubmit: (e) => {
            e.preventDefault();
            setKonfirmasi(true);
          },
          id: "form-alihkan",
          children: [
            /* @__PURE__ */ jsx(
              PapanPilihPeninjau,
              {
                section: {
                  key: "tujuan",
                  type: "user_picker",
                  label: "Alihkan kepada",
                  required: true,
                  hint: "Kandidat yang sama dengan saat dokumen ini ditugaskan. Angka di kanan = dokumen yang sedang ia tinjau."
                },
                value: form.data.tujuan,
                onChange: (v) => form.setData("tujuan", Number(v)),
                nama: "tujuan",
                options: kandidat,
                jadwal: ketersediaan,
                memblokir: true,
                ambang
              }
            ),
            form.errors.tujuan ? /* @__PURE__ */ jsx("p", { className: "text-destructive mb-3 text-sm", children: form.errors.tujuan }) : null,
            /* @__PURE__ */ jsxs(Label, { htmlFor: "alasan-alih", className: "mb-1.5 font-semibold", children: [
              "Alasan pengalihan ",
              /* @__PURE__ */ jsx("span", { className: "text-destructive", children: "*" })
            ] }),
            /* @__PURE__ */ jsx(
              Textarea,
              {
                id: "alasan-alih",
                rows: 3,
                maxLength: 2e3,
                required: true,
                "aria-invalid": Boolean(form.errors.alasan),
                placeholder: "mis. Sedang menangani 6 JSA lain minggu ini.",
                value: form.data.alasan,
                onChange: (e) => form.setData("alasan", e.target.value)
              }
            ),
            form.errors.alasan ? /* @__PURE__ */ jsx("p", { className: "text-destructive text-sm", children: form.errors.alasan }) : null,
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-1 text-xs", children: "Dikirim ke peninjau tujuan dan tercatat di audit log." })
          ]
        }
      ),
      /* @__PURE__ */ jsxs(DialogFooter, { children: [
        /* @__PURE__ */ jsx(Button, { type: "button", variant: "outline", onClick: () => onUbahBuka(false), children: "Batal" }),
        /* @__PURE__ */ jsxs(Button, { type: "submit", form: "form-alihkan", disabled: form.processing, children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Exchange01Icon, strokeWidth: 1.5 }),
          "Alihkan"
        ] })
      ] })
    ] }) }),
    /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        buka: konfirmasi,
        onUbahBuka: setKonfirmasi,
        judul: "Alihkan peninjauan?",
        pesan: `Peninjauan ${nomor} langsung berpindah ke peninjau yang dipilih dan tidak lagi muncul di antrian Anda.`,
        tombolYa: "Ya, alihkan",
        onKonfirmasi: kirim
      }
    )
  ] });
}
const UMUM = "__umum";
function PanelAi({
  url,
  onAdopsi,
  onKeRingkasan,
  tujuan
}) {
  const [loading, setLoading] = useState(false);
  const [analyzed, setAnalyzed] = useState(false);
  const [ringkasan, setRingkasan] = useState("");
  const [temuan, setTemuan] = useState([]);
  const [galat, setGalat] = useState("");
  const [detik, setDetik] = useState(0);
  const jam = useRef(null);
  const jemput = useRef(null);
  useEffect(
    () => () => {
      clearInterval(jam.current ?? void 0);
      clearInterval(jemput.current ?? void 0);
    },
    []
  );
  const selesai = () => {
    setLoading(false);
    clearInterval(jam.current ?? void 0);
    clearInterval(jemput.current ?? void 0);
  };
  const BATAS_JEMPUT_DETIK = 300;
  const terapkanHasil = (d) => {
    setAnalyzed(true);
    setRingkasan(d.summary || "");
    setTemuan(d.findings || []);
    if (d.enabled === false) setGalat(d.summary || "");
  };
  const analisis = async () => {
    setLoading(true);
    setGalat("");
    setDetik(0);
    jam.current = setInterval(() => setDetik((d) => d + 1), 1e3);
    try {
      const r = await fetch(url, {
        method: "POST",
        headers: {
          "X-XSRF-TOKEN": xsrf(),
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });
      const d = await r.json();
      if (!r.ok) {
        selesai();
        setAnalyzed(false);
        setGalat(d.message ?? d.summary ?? `Analisis AI gagal (HTTP ${r.status}).`);
        return;
      }
      if (d.status !== "antre") {
        selesai();
        terapkanHasil(d);
        return;
      }
      let jemputan = 0;
      jemput.current = setInterval(async () => {
        jemputan += 1;
        if (jemputan * 3 >= BATAS_JEMPUT_DETIK) {
          selesai();
          setGalat("AI belum menjawab setelah 5 menit. Kemungkinan pekerja antrean (queue:work) tidak berjalan — lanjutkan tinjauan manual.");
          return;
        }
        try {
          const s = await fetch(url, {
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }
          });
          const sd = await s.json();
          if (!s.ok) {
            selesai();
            setAnalyzed(false);
            setGalat(sd.message ?? sd.summary ?? `Analisis AI gagal (HTTP ${s.status}).`);
            return;
          }
          if (sd.status === "selesai" || sd.enabled === false) {
            selesai();
            terapkanHasil(sd);
          }
        } catch {
          selesai();
          setGalat("Gagal memanggil AI. Lanjutkan tinjauan manual.");
        }
      }, 3e3);
    } catch {
      selesai();
      setGalat("Gagal memanggil AI. Lanjutkan tinjauan manual.");
    }
  };
  const buang = (i) => setTemuan((lama) => lama.filter((_, j) => j !== i));
  const refAwal = (f) => {
    const ref = f.item_ref === null || f.item_ref === void 0 ? "" : String(f.item_ref);
    return (tujuan[f.section_key] ?? []).some((t) => t.ref === ref) ? ref : UMUM;
  };
  const [pilihan, setPilihan] = useState({});
  const adopsi = (i) => {
    const f = temuan[i];
    const ref = pilihan[i] ?? refAwal(f);
    if (ref === UMUM || !onAdopsi(f.section_key, ref, f.suggestion)) {
      onKeRingkasan(f.suggestion);
    }
    buang(i);
  };
  return /* @__PURE__ */ jsx(Card, { children: /* @__PURE__ */ jsxs(CardContent, { children: [
    /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center justify-between gap-2", children: [
      /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-1.5 font-bold", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RoboticIcon, strokeWidth: 1.5, className: "size-4" }),
        "AI Review Assist",
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm font-normal", children: "— saran, bukan keputusan" })
      ] }),
      /* @__PURE__ */ jsx(Button, { type: "button", variant: "outline", size: "sm", onClick: analisis, disabled: loading, children: loading ? /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: HourglassIcon, strokeWidth: 1.5, className: "animate-pulse" }),
        "Menganalisis… ",
        detik,
        "d"
      ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: SparklesIcon, strokeWidth: 1.5 }),
        "Analisis dengan AI"
      ] }) })
    ] }),
    loading ? /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground mt-2 flex items-center gap-1.5 text-sm", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: HourglassIcon, strokeWidth: 1.5, className: "size-3.5" }),
      "AI sedang membaca seluruh dokumen — biasanya 1–2 menit untuk JSA. Jangan tutup halaman ini."
    ] }) : null,
    ringkasan ? /* @__PURE__ */ jsxs("p", { className: "bg-muted mt-3 mb-2 rounded-lg border px-3 py-2 text-sm", children: [
      /* @__PURE__ */ jsx("strong", { children: "Ringkasan AI:" }),
      " ",
      ringkasan
    ] }) : null,
    temuan.length > 0 ? /* @__PURE__ */ jsxs("div", { className: "mt-2", children: [
      /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground mb-2 text-sm", children: [
        "Temuan — ",
        /* @__PURE__ */ jsx("strong", { children: "Adopsi" }),
        " untuk memasukkan ke catatan (boleh diedit dulu), atau ",
        /* @__PURE__ */ jsx("strong", { children: "Tolak" }),
        ":"
      ] }),
      temuan.map((f, i) => /* @__PURE__ */ jsxs("div", { className: "bg-muted/40 mb-2 rounded-lg border p-2", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center justify-between gap-2", children: [
          /* @__PURE__ */ jsxs(Badge, { variant: "outline", className: "font-mono", children: [
            f.section_key,
            labelRef(f.item_ref)
          ] }),
          /* @__PURE__ */ jsx(Badge, { variant: f.severity === "critical" ? "destructive" : "secondary", children: f.severity })
        ] }),
        /* @__PURE__ */ jsxs("p", { className: "mt-1 text-sm", children: [
          /* @__PURE__ */ jsx("strong", { children: f.severity === "info" ? "Catatan:" : "Masalah:" }),
          " ",
          f.issue
        ] }),
        /* @__PURE__ */ jsxs("label", { className: "text-muted-foreground mt-1 block text-xs", children: [
          /* @__PURE__ */ jsx("strong", { children: f.severity === "info" ? "Keterangan" : "Saran perbaikan" }),
          " ",
          "(boleh diedit):"
        ] }),
        /* @__PURE__ */ jsx(
          Textarea,
          {
            rows: 4,
            className: "text-sm",
            value: f.suggestion,
            onChange: (e) => setTemuan(
              (lama) => lama.map(
                (t, j) => j === i ? { ...t, suggestion: e.target.value } : t
              )
            )
          }
        ),
        /* @__PURE__ */ jsx("label", { className: "text-muted-foreground mt-2 block text-xs", children: /* @__PURE__ */ jsx("strong", { children: "Masukkan ke:" }) }),
        /* @__PURE__ */ jsxs(
          Select,
          {
            value: pilihan[i] ?? refAwal(f),
            onValueChange: (v) => setPilihan((lama) => ({ ...lama, [i]: v })),
            children: [
              /* @__PURE__ */ jsx(SelectTrigger, { className: "w-full", "aria-label": "Kotak tujuan temuan", children: /* @__PURE__ */ jsx(SelectValue, {}) }),
              /* @__PURE__ */ jsxs(SelectContent, { children: [
                /* @__PURE__ */ jsx(SelectItem, { value: "__umum", children: "Ringkasan / Catatan Umum" }),
                (tujuan[f.section_key] ?? []).map((t) => /* @__PURE__ */ jsx(SelectItem, { value: t.ref, children: t.label }, t.ref))
              ] })
            ]
          }
        ),
        /* @__PURE__ */ jsxs("div", { className: "mt-1 flex justify-end gap-1", children: [
          /* @__PURE__ */ jsxs(Button, { type: "button", size: "sm", onClick: () => adopsi(i), children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Tick02Icon, strokeWidth: 1.5 }),
            "Adopsi"
          ] }),
          /* @__PURE__ */ jsxs(
            Button,
            {
              type: "button",
              size: "sm",
              variant: "outline",
              onClick: () => buang(i),
              children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 1.5 }),
                "Tolak"
              ]
            }
          )
        ] })
      ] }, i))
    ] }) : null,
    analyzed && temuan.length === 0 && !galat ? /* @__PURE__ */ jsxs("p", { className: "mt-2 flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-400", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Tick02Icon, strokeWidth: 1.5, className: "size-4" }),
      "Tidak ada temuan signifikan dari AI."
    ] }) : null,
    galat ? /* @__PURE__ */ jsxs("p", { className: "text-destructive mt-2 flex items-center gap-1.5 text-sm", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Alert02Icon, strokeWidth: 1.5, className: "size-4" }),
      galat
    ] }) : null
  ] }) });
}
function labelRef(ref) {
  if (ref === null || ref === void 0 || ref === "") return "";
  const m = String(ref).match(/^L(\d+)(?:-B(\d+))?(?:-P(\d+))?$/);
  if (!m) return ` › item ${Number(ref) + 1}`;
  const nama = ["Langkah", "Bahaya", "Kendali"];
  return m.slice(1).reduce((s, v, i) => v === void 0 ? s : `${s} › ${nama[i]} ${Number(v) + 1}`, "");
}
const Konteks = createContext(null);
const PenyediaTinjau = Konteks.Provider;
function useTinjau() {
  const nilai = useContext(Konteks);
  if (!nilai) {
    throw new Error("Komponen tinjau dipakai di luar PenyediaTinjau.");
  }
  return nilai;
}
function SeksiTinjau({ section, nilai }) {
  const items = Array.isArray(nilai) ? nilai : [];
  return /* @__PURE__ */ jsxs(Card, { className: "gap-0 py-0", children: [
    /* @__PURE__ */ jsx(CardHeader, { className: "bg-muted/50 rounded-t-xl border-b px-4 py-3 font-bold", children: section.label ?? section.key }),
    /* @__PURE__ */ jsx(CardContent, { className: "p-4", children: section.type === "jsa_analysis" ? /* @__PURE__ */ jsx(JsaTinjau, { seksi: section.key, langkah: items }) : /* @__PURE__ */ jsx(DaftarItem, { section, items }) })
  ] });
}
function DaftarItem({ section, items }) {
  const kunciRich = (section.group_fields ?? section.fields ?? []).filter((f) => f.type === "rich_text").map((f) => f.key);
  if (items.length === 0) {
    return /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "(Kosong)" });
  }
  return /* @__PURE__ */ jsx(Fragment, { children: items.map((item, i) => /* @__PURE__ */ jsxs("div", { className: "mb-3 grid gap-2 border-b pb-3 md:grid-cols-12", children: [
    /* @__PURE__ */ jsxs("div", { className: "md:col-span-7", children: [
      /* @__PURE__ */ jsxs("div", { className: "text-muted-foreground text-xs", children: [
        "Item ",
        i + 1
      ] }),
      /* @__PURE__ */ jsx(IsiItem, { item, kunciRich }),
      /* @__PURE__ */ jsx(CatatanLama, { seksi: section.key, item: String(i) })
    ] }),
    /* @__PURE__ */ jsx("div", { className: "md:col-span-5", children: /* @__PURE__ */ jsx(
      KotakAnotasi,
      {
        seksi: section.key,
        item: String(i),
        placeholder: "Catatan peninjau untuk item ini (opsional)"
      }
    ) })
  ] }, i)) });
}
function IsiItem({ item, kunciRich }) {
  if (typeof item !== "object" || item === null) {
    return /* @__PURE__ */ jsx("div", { className: "text-sm", children: String(item ?? "") });
  }
  return /* @__PURE__ */ jsx(Fragment, { children: Object.entries(item).map(([k, v]) => {
    if (kunciRich.includes(k)) {
      return v ? /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-xs", children: [
          k,
          ":"
        ] }),
        /* @__PURE__ */ jsx(
          "div",
          {
            className: "pp-rt-isi",
            dangerouslySetInnerHTML: { __html: String(v) }
          }
        )
      ] }, k) : null;
    }
    const tampil = Boolean(v) && k !== "isi" || typeof v === "string" && !v.startsWith("lampiran/");
    return tampil ? /* @__PURE__ */ jsxs("div", { className: "text-sm", children: [
      /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-xs", children: [
        k,
        ":"
      ] }),
      " ",
      typeof v === "string" ? v : ""
    ] }, k) : null;
  }) });
}
function JsaTinjau({ seksi, langkah }) {
  const { verdicts } = useTinjau();
  if (langkah.length === 0) {
    return /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "(Belum ada analisa bahaya)" });
  }
  return /* @__PURE__ */ jsx(Fragment, { children: langkah.map((step, li) => /* @__PURE__ */ jsxs("div", { className: "mb-3 rounded-lg border p-2", children: [
    /* @__PURE__ */ jsxs(
      BarisTinjau,
      {
        seksi,
        item: `L${li}`,
        placeholder: "Catatan untuk langkah kerja ini (opsional)",
        children: [
          /* @__PURE__ */ jsxs("div", { className: "text-muted-foreground text-xs", children: [
            "Langkah Kerja ",
            li + 1
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "font-semibold", children: [
            li + 1,
            ". ",
            step.langkah ?? ""
          ] })
        ]
      }
    ),
    (step.bahaya ?? []).map((b, bi) => {
      const refB = `L${li}-B${bi}`;
      const kendali = b.pengendalian ?? [];
      return /* @__PURE__ */ jsxs("div", { className: "pl-3", children: [
        /* @__PURE__ */ jsxs(
          BarisTinjau,
          {
            seksi,
            item: refB,
            placeholder: "Catatan untuk bahaya ini (opsional)",
            children: [
              /* @__PURE__ */ jsxs("div", { className: "text-destructive flex items-center gap-1.5 text-xs", children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Alert02Icon, strokeWidth: 1.5, className: "size-3.5" }),
                "Bahaya & Risiko ",
                li + 1,
                ".",
                bi + 1
              ] }),
              /* @__PURE__ */ jsx("div", { className: "text-sm", children: b.risiko ?? "" }),
              /* @__PURE__ */ jsx(Borongan, { refB, jumlah: kendali.length })
            ]
          }
        ),
        kendali.map((p, pi) => /* @__PURE__ */ jsx("div", { className: "pl-5", children: /* @__PURE__ */ jsxs(
          BarisTinjau,
          {
            seksi,
            item: `${refB}-P${pi}`,
            perluRevisi: verdicts[`${refB}-P${pi}`] === "perlu_revisi",
            placeholder: "Catatan untuk pengendalian ini (opsional)",
            children: [
              /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400", children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ShieldCheckIcon, strokeWidth: 1.5, className: "size-3.5" }),
                "Tindakan Pengendalian ",
                li + 1,
                ".",
                bi + 1,
                ".",
                pi + 1
              ] }),
              /* @__PURE__ */ jsx("div", { className: "text-sm", children: p }),
              /* @__PURE__ */ jsx(TombolVerdict, { item: `${refB}-P${pi}`, nomor: `${li + 1}.${bi + 1}.${pi + 1}` })
            ]
          }
        ) }, pi))
      ] }, bi);
    })
  ] }, li)) });
}
function BarisTinjau({
  seksi,
  item,
  placeholder,
  perluRevisi = false,
  children
}) {
  return /* @__PURE__ */ jsxs(
    "div",
    {
      className: cn(
        "mb-2 grid gap-2 rounded-sm md:grid-cols-12",
        perluRevisi && "border-destructive border-l-[3px] pl-2"
      ),
      children: [
        /* @__PURE__ */ jsxs("div", { className: "md:col-span-7", children: [
          children,
          /* @__PURE__ */ jsx(CatatanLama, { seksi, item })
        ] }),
        /* @__PURE__ */ jsx("div", { className: "md:col-span-5", children: /* @__PURE__ */ jsx(KotakAnotasi, { seksi, item, placeholder }) })
      ]
    }
  );
}
function TombolVerdict({ item, nomor }) {
  const { pakaiVerdict, verdicts, setVerdict } = useTinjau();
  if (!pakaiVerdict) return null;
  const pilihan = [
    { nilai: "sesuai", label: "Sesuai", glif: Tick02Icon },
    { nilai: "perlu_revisi", label: "Perlu Revisi", glif: Cancel01Icon }
  ];
  return /* @__PURE__ */ jsx("div", { className: "mt-2 flex", role: "group", "aria-label": `Penilaian pengendalian ${nomor}`, children: pilihan.map(({ nilai, label, glif }, i) => /* @__PURE__ */ jsxs(
    "label",
    {
      className: cn(
        "relative inline-flex cursor-pointer items-center gap-1 border px-2.5 py-1 text-xs font-medium transition-colors",
        "has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ring",
        i === 0 ? "rounded-l-md" : "-ml-px rounded-r-md",
        verdicts[item] === nilai ? nilai === "sesuai" ? "z-10 border-emerald-700 bg-emerald-700 font-semibold text-white" : "border-destructive bg-destructive text-destructive-foreground ring-destructive/30 z-10 font-semibold ring-3" : "hover:bg-accent"
      ),
      children: [
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "radio",
            className: "sr-only",
            name: `verdicts[${item}]`,
            value: nilai,
            required: true,
            checked: verdicts[item] === nilai,
            onChange: () => setVerdict(item, nilai)
          }
        ),
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: glif, strokeWidth: 1.5, className: "size-3.5" }),
        label
      ]
    },
    nilai
  )) });
}
function Borongan({ refB, jumlah }) {
  const { pakaiVerdict, setVerdictBorongan } = useTinjau();
  if (!pakaiVerdict || jumlah <= 1) return null;
  const refs = Array.from({ length: jumlah }, (_, i) => `${refB}-P${i}`);
  return /* @__PURE__ */ jsxs("div", { className: "mt-2 flex flex-wrap items-center gap-2", children: [
    /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-xs", children: [
      "Tandai ",
      jumlah,
      " pengendalian sekaligus:"
    ] }),
    /* @__PURE__ */ jsxs(
      Button,
      {
        type: "button",
        variant: "outline",
        size: "sm",
        onClick: () => setVerdictBorongan(refs, "sesuai"),
        children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: TickDouble01Icon, strokeWidth: 1.5 }),
          "Semua Sesuai"
        ]
      }
    ),
    /* @__PURE__ */ jsxs(
      Button,
      {
        type: "button",
        variant: "outline",
        size: "sm",
        onClick: () => setVerdictBorongan(refs, "perlu_revisi"),
        children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: OctagonXIcon, strokeWidth: 1.5 }),
          "Semua Perlu Revisi"
        ]
      }
    )
  ] });
}
function CatatanLama({ seksi, item }) {
  const daftar = useTinjau().anotasiLama[seksi]?.[item] ?? [];
  return /* @__PURE__ */ jsx(Fragment, { children: daftar.map((komentar, i) => /* @__PURE__ */ jsxs("div", { className: "text-destructive mt-1 flex gap-1.5 text-xs", children: [
    /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Message01Icon, strokeWidth: 1.5, className: "size-3.5 shrink-0" }),
    /* @__PURE__ */ jsxs("span", { children: [
      "Catatan sebelumnya: ",
      komentar
    ] })
  ] }, i)) });
}
function KotakAnotasi({
  seksi,
  item,
  placeholder
}) {
  const { catatan, ubahCatatan, dariAi } = useTinjau();
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx(
      Textarea,
      {
        "data-annot": `${seksi}-${item}`,
        rows: 2,
        className: "text-sm",
        placeholder,
        value: catatan[seksi]?.[item] ?? "",
        onChange: (e) => ubahCatatan(seksi, item, e.target.value)
      }
    ),
    dariAi[seksi]?.[item] ? /* @__PURE__ */ jsxs("div", { className: "text-primary mt-1 flex items-center gap-1.5 text-xs", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RoboticIcon, strokeWidth: 1.5, className: "size-3.5" }),
      "Diadopsi dari saran AI"
    ] }) : null
  ] });
}
function ReviewShow() {
  const props = usePage().props;
  const {
    document: doc,
    schema,
    tipeDitinjau,
    contentMap,
    anotasiLama,
    lampiran,
    formAction,
    backUrl,
    pakaiVerdict,
    aiUrl,
    pengalihan,
    alihKandidat
  } = props;
  const [catatan, setCatatan] = useState({});
  const [dariAi, setDariAi] = useState({});
  const [verdicts, setVerdicts] = useState({});
  const [ringkasan, setRingkasan] = useState("");
  const [keputusan, setKeputusan] = useState(null);
  const [alih, setAlih] = useState(Boolean(props.alihOtomatis));
  const seksi = (schema.steps ?? []).flatMap((s) => s.sections ?? []).filter((s) => tipeDitinjau.includes(s.type));
  const ubahCatatan = (s, item, teks) => setCatatan((lama) => ({ ...lama, [s]: { ...lama[s], [item]: teks } }));
  const konteks = {
    catatan,
    ubahCatatan,
    dariAi,
    anotasiLama,
    verdicts,
    setVerdict: (item, nilai) => setVerdicts((lama) => ({ ...lama, [item]: nilai })),
    setVerdictBorongan: (refs, nilai) => setVerdicts((lama) => ({ ...lama, ...Object.fromEntries(refs.map((r) => [r, nilai])) })),
    pakaiVerdict
  };
  const tujuan = Object.fromEntries(
    seksi.map((s) => {
      const isi = contentMap[s.key];
      const daftar = [];
      if (!Array.isArray(isi)) return [s.key, daftar];
      const potong = (teks) => {
        const t = String(teks ?? "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
        return t.length > 32 ? `${t.slice(0, 32)}…` : t;
      };
      if (s.type === "jsa_analysis") {
        isi.forEach((step, li) => {
          daftar.push({ ref: `L${li}`, label: `Langkah ${li + 1} — ${potong(step?.langkah)}` });
          (step?.bahaya ?? []).forEach((b, bi) => {
            daftar.push({
              ref: `L${li}-B${bi}`,
              label: `${li + 1}.${bi + 1} Bahaya — ${potong(b?.risiko)}`
            });
            (b?.pengendalian ?? []).forEach((p, pi) => {
              daftar.push({
                ref: `L${li}-B${bi}-P${pi}`,
                label: `${li + 1}.${bi + 1}.${pi + 1} Kendali — ${potong(p)}`
              });
            });
          });
        });
        return [s.key, daftar];
      }
      isi.forEach((item, i) => {
        const teks = item && typeof item === "object" ? Object.values(item).filter((v) => typeof v === "string").join(" ") : item;
        daftar.push({ ref: String(i), label: `Item ${i + 1} — ${potong(teks)}` });
      });
      return [s.key, daftar];
    })
  );
  const adopsi = (sectionKey, itemRef, saran) => {
    const ref = itemRef === null || itemRef === void 0 || itemRef === "" ? null : String(itemRef);
    if (!ref || !document.querySelector(`[data-annot="${sectionKey}-${ref}"]`)) return false;
    const lama = catatan[sectionKey]?.[ref] ?? "";
    ubahCatatan(sectionKey, ref, lama ? `${lama}
${saran}` : saran);
    setDariAi((l) => ({ ...l, [sectionKey]: { ...l[sectionKey], [ref]: true } }));
    document.querySelector(`[data-annot="${sectionKey}-${ref}"]`)?.scrollIntoView({ behavior: "smooth", block: "center" });
    return true;
  };
  const kirim = (decision) => router.post(formAction, {
    ...decision ? { decision } : {},
    summary: ringkasan,
    annotations: catatan,
    annotations_ai: dariAi,
    verdicts
  });
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: `${props.judulLayar ?? "Tinjau"}: ${doc.judul}`,
      remah: [{ label: props.backLabel ?? "Kembali ke antrian", href: backUrl }, { label: doc.nomor }],
      sub: /* @__PURE__ */ jsxs("span", { className: "flex flex-wrap items-center gap-x-2 gap-y-1", children: [
        /* @__PURE__ */ jsx("span", { className: "text-primary font-mono font-semibold", children: doc.nomor }),
        /* @__PURE__ */ jsx(Badge, { variant: "outline", children: doc.dept ?? "—" }),
        /* @__PURE__ */ jsxs(Badge, { variant: "secondary", children: [
          "Edisi ",
          doc.edisi,
          " · Revisi ",
          doc.noRevisi
        ] }),
        doc.putaran > 0 ? /* @__PURE__ */ jsxs(Badge, { variant: "outline", children: [
          "Putaran tinjauan ke-",
          doc.putaran + 1
        ] }) : null
      ] }),
      aksi: /* @__PURE__ */ jsxs(Fragment, { children: [
        alihKandidat && alihKandidat.length > 0 ? /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", onClick: () => setAlih(true), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Exchange01Icon, strokeWidth: 1.5 }),
          "Alihkan"
        ] }) : null,
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: File01Icon, strokeWidth: 1.5 }),
          "Lihat PDF"
        ] }) })
      ] }),
      children: [
        pengalihan ? /* @__PURE__ */ jsxs("div", { className: "bg-accent flex gap-2 rounded-xl border px-3 py-2 text-sm", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Exchange01Icon, strokeWidth: 1.5, className: "mt-0.5 size-4 shrink-0" }),
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("strong", { children: [
              "Dialihkan oleh ",
              pengalihan.dari
            ] }),
            " ",
            /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
              "· ",
              pengalihan.waktu
            ] }),
            /* @__PURE__ */ jsx("div", { className: "whitespace-pre-line", children: pengalihan.alasan })
          ] })
        ] }) : null,
        props.alihOtomatis && (!alihKandidat || alihKandidat.length === 0) ? /* @__PURE__ */ jsxs("p", { className: "border-chart-3/40 bg-chart-3/10 flex gap-2 rounded-xl border px-3 py-2 text-sm", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "mt-0.5 size-4 shrink-0" }),
          "Pengalihan tidak tersedia untuk dokumen ini — tidak ada peninjau lain yang berwenang dan sedang bisa menerimanya."
        ] }) : null,
        /* @__PURE__ */ jsxs("div", { className: "bg-accent flex gap-2 rounded-xl border px-3 py-2 text-sm", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "mt-0.5 size-4 shrink-0" }),
          /* @__PURE__ */ jsx(
            "span",
            {
              dangerouslySetInnerHTML: {
                __html: props.petunjuk ?? "Beri catatan pada item yang perlu diperbaiki. Kosongkan bila item sudah sesuai. Bila ada satu saja catatan, pilih <strong>Kembalikan untuk Revisi</strong>."
              }
            }
          )
        ] }),
        aiUrl ? /* @__PURE__ */ jsx(
          PanelAi,
          {
            url: aiUrl,
            onAdopsi: adopsi,
            tujuan,
            onKeRingkasan: (saran) => setRingkasan((l) => l ? `${l}
${saran}` : saran)
          }
        ) : null,
        /* @__PURE__ */ jsxs(PenyediaTinjau, { value: konteks, children: [
          seksi.map((s) => /* @__PURE__ */ jsx(SeksiTinjau, { section: s, nilai: contentMap[s.key] }, s.key)),
          /* @__PURE__ */ jsx(Card, { children: /* @__PURE__ */ jsxs(CardContent, { children: [
            /* @__PURE__ */ jsx(Label, { htmlFor: "ringkasan", className: "mb-1.5 font-semibold", children: "Ringkasan / Catatan Umum (opsional)" }),
            /* @__PURE__ */ jsx(
              Textarea,
              {
                id: "ringkasan",
                rows: 2,
                className: "mb-3",
                placeholder: "Ringkasan hasil tinjauan...",
                value: ringkasan,
                onChange: (e) => setRingkasan(e.target.value)
              }
            ),
            /* @__PURE__ */ jsx(TombolKeputusan, { props, verdicts, onPilih: setKeputusan, backUrl })
          ] }) })
        ] }),
        /* @__PURE__ */ jsx(KartuLampiran, { daftar: lampiran }),
        alihKandidat && alihKandidat.length > 0 ? /* @__PURE__ */ jsx(
          DialogAlihkan,
          {
            buka: alih,
            onUbahBuka: setAlih,
            nomor: doc.nomor,
            url: props.alihUrl ?? "",
            kandidat: alihKandidat,
            ketersediaan: props.alihKetersediaan ?? {},
            ambang: props.ambang ?? { padat: 0, sibuk: 0, jenis: [] }
          }
        ) : null,
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            buka: keputusan !== null,
            onUbahBuka: (b) => b ? null : setKeputusan(null),
            judul: judulKonfirmasi(keputusan),
            pesan: pesanKonfirmasi(props, keputusan, verdicts),
            tombolYa: keputusan === "reject" ? "Ya, kembalikan" : "Ya, kirim",
            onKonfirmasi: () => {
              kirim(keputusan === "kirim" ? void 0 : keputusan ?? void 0);
              setKeputusan(null);
            }
          }
        )
      ]
    }
  );
}
function TombolKeputusan({
  props,
  verdicts,
  onPilih,
  backUrl
}) {
  if (props.pakaiVerdict) {
    const { total, dinilai, revisi } = hitungVerdict(props, verdicts);
    return /* @__PURE__ */ jsxs("div", { children: [
      /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center justify-between gap-2", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-3 text-sm", children: [
          /* @__PURE__ */ jsxs(
            "span",
            {
              className: dinilai < total ? "text-chart-3 flex items-center gap-1.5" : "flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400",
              children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: dinilai < total ? HourglassIcon : Tick02Icon,
                    strokeWidth: 1.5,
                    className: "size-4"
                  }
                ),
                dinilai,
                " dari ",
                total,
                " pengendalian dinilai"
              ]
            }
          ),
          revisi > 0 ? /* @__PURE__ */ jsxs("span", { className: "text-destructive flex items-center gap-1.5", children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: OctagonXIcon, strokeWidth: 1.5, className: "size-4" }),
            revisi,
            " perlu revisi"
          ] }) : null
        ] }),
        /* @__PURE__ */ jsxs(Button, { type: "button", disabled: dinilai < total, onClick: () => onPilih("kirim"), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: SentIcon, strokeWidth: 1.5 }),
          "Kirim Hasil Tinjauan"
        ] })
      ] }),
      dinilai < total ? /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-1 text-xs", children: "Tombol aktif setelah semua tindakan pengendalian ditandai." }) : null
    ] });
  }
  if (props.tanpaKeputusan) {
    return /* @__PURE__ */ jsxs("div", { className: "flex justify-end gap-2", children: [
      /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", children: /* @__PURE__ */ jsx(Link, { href: backUrl, children: "Batal" }) }),
      /* @__PURE__ */ jsxs(Button, { type: "button", onClick: () => onPilih("kirim"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: SentIcon, strokeWidth: 1.5 }),
        "Kirim"
      ] })
    ] });
  }
  return /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap justify-end gap-2", children: [
    /* @__PURE__ */ jsxs(Button, { type: "button", variant: "secondary", onClick: () => onPilih("reject"), children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowTurnBackwardIcon, strokeWidth: 1.5 }),
      props.labelTolak ?? "Kembalikan untuk Revisi"
    ] }),
    /* @__PURE__ */ jsxs(Button, { type: "button", onClick: () => onPilih("approve"), children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Tick02Icon, strokeWidth: 1.5 }),
      props.labelLolos ?? "Loloskan"
    ] })
  ] });
}
function hitungVerdict(props, verdicts) {
  const analisa = (props.schema.steps ?? []).flatMap((s) => s.sections ?? []).find((s) => s.type === "jsa_analysis");
  const isi = analisa ? props.contentMap[analisa.key] : null;
  const langkah = Array.isArray(isi) ? isi : [];
  const total = langkah.reduce(
    (n, l) => n + (l.bahaya ?? []).reduce((m, b) => m + (b.pengendalian ?? []).length, 0),
    0
  );
  const nilai = Object.values(verdicts);
  return {
    total,
    dinilai: nilai.filter(Boolean).length,
    revisi: nilai.filter((v) => v === "perlu_revisi").length
  };
}
function judulKonfirmasi(keputusan) {
  if (keputusan === "reject") return "Kembalikan untuk Revisi?";
  if (keputusan === "approve") return "Loloskan Dokumen?";
  return "Kirim Hasil Tinjauan?";
}
function pesanKonfirmasi(props, keputusan, verdicts) {
  if (keputusan === "reject") {
    return props.konfirmasiTolak ?? "Kembalikan dokumen ke pembuat untuk revisi?";
  }
  if (keputusan === "approve") {
    return props.konfirmasiLolos ?? "Loloskan dokumen ke tahap persetujuan?";
  }
  if (props.tanpaKeputusan) {
    return "Kirim masukan Anda kepada pembuat dokumen? Status dokumen tidak berubah.";
  }
  const revisi = Object.values(verdicts).filter((v) => v === "perlu_revisi").length;
  return revisi > 0 ? `${revisi} pengendalian ditandai Perlu Revisi. Dokumen akan dikembalikan ke pembuat.` : "Semua pengendalian sesuai. Loloskan dokumen ke tahap persetujuan?";
}
function KartuLampiran({ daftar }) {
  if (daftar.length === 0) return null;
  return /* @__PURE__ */ jsxs(Card, { className: "gap-0 py-0", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "flex items-center gap-1.5 border-b px-4 py-3 font-semibold", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Album02Icon, strokeWidth: 1.5, className: "size-4" }),
      "Foto Lampiran & Komentar"
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "grid gap-3 p-4 md:grid-cols-2", children: daftar.map((att) => /* @__PURE__ */ jsx(FotoLampiran, { att }, att.id)) })
  ] });
}
function FotoLampiran({ att }) {
  const [komentar, setKomentar] = useState("");
  return /* @__PURE__ */ jsxs("div", { className: "rounded-xl border p-2", children: [
    /* @__PURE__ */ jsx("img", { src: att.url, alt: att.nama, className: "mb-2 max-h-56 w-full rounded-lg object-contain" }),
    /* @__PURE__ */ jsx("div", { className: "mb-2 text-sm", children: att.komentar.length === 0 ? /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "Belum ada komentar." }) : att.komentar.map((c) => /* @__PURE__ */ jsxs("div", { className: "border-primary mb-1 border-l-3 pl-2", children: [
      /* @__PURE__ */ jsx("strong", { children: c.oleh }),
      ": ",
      c.isi,
      " ",
      /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
        "· ",
        c.waktu
      ] })
    ] }, c.id)) }),
    /* @__PURE__ */ jsxs(
      "form",
      {
        className: "flex gap-1",
        onSubmit: (e) => {
          e.preventDefault();
          router.post(
            route("attachments.comment", att.id),
            { comment: komentar },
            { preserveScroll: true, onSuccess: () => setKomentar("") }
          );
        },
        children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              required: true,
              maxLength: 1e3,
              placeholder: "Komentari foto ini...",
              value: komentar,
              onChange: (e) => setKomentar(e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(Button, { type: "submit", variant: "outline", size: "icon", "aria-label": "Kirim komentar", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: SentIcon, strokeWidth: 1.5 }) })
        ]
      }
    )
  ] });
}
export {
  ReviewShow as default
};
