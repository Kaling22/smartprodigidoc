import { jsx, jsxs } from "react/jsx-runtime";
import { Search01Icon, Cancel01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { router } from "@inertiajs/react";
import { useState, useRef, useEffect } from "react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { I as InputGroup, b as InputGroupAddon, a as InputGroupInput } from "./input-group-C5LPhh_3.js";
import { c as cn } from "../uji-render.js";
import { L as Label } from "./label-emiz6fAI.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
function Kbd({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "kbd",
    {
      "data-slot": "kbd",
      className: cn(
        "pointer-events-none inline-flex h-5 w-fit min-w-5 items-center justify-center gap-1 rounded-sm bg-muted px-1 font-sans text-xs font-medium text-muted-foreground select-none in-data-[slot=tooltip-content]:bg-background/20 in-data-[slot=tooltip-content]:text-background dark:in-data-[slot=tooltip-content]:bg-background/10 [&_svg:not([class*='size-'])]:size-3",
        className
      ),
      ...props
    }
  );
}
const SEMUA = "*";
function PenyaringDokumen({
  url,
  filters,
  prefix = "",
  pilihan = [],
  /** Ikut dikirim tanpa tampil — mis. departemen yang dipilih dari sub-menu. */
  tersembunyi = {},
  contoh,
  labelCari = "Cari",
  placeholderCari
}) {
  const [nilai, setNilai] = useState(() => {
    const awal = { q: filters.q ?? "" };
    pilihan.forEach((p) => awal[p.nama] = filters[p.nama] ?? "");
    return awal;
  });
  const kotak = useRef(null);
  useEffect(() => {
    const tekan = (e) => {
      const t = e.target;
      const sedangMengetik = t instanceof HTMLInputElement || t instanceof HTMLTextAreaElement || t?.isContentEditable;
      if (e.key === "/" && !sedangMengetik) {
        e.preventDefault();
        kotak.current?.focus();
      }
    };
    document.addEventListener("keydown", tekan);
    return () => document.removeEventListener("keydown", tekan);
  }, []);
  const adaIsi = Object.values(nilai).some((v) => v !== "");
  const pertama = useRef(true);
  useEffect(() => {
    if (pertama.current) {
      pertama.current = false;
      return;
    }
    const jeda = setTimeout(() => {
      router.get(
        url,
        { ...tersembunyi, ...nilai },
        { preserveState: true, preserveScroll: true, replace: true }
      );
    }, 300);
    return () => clearTimeout(jeda);
  }, [nilai]);
  function saring(e) {
    e.preventDefault();
    router.get(url, { ...tersembunyi, ...nilai }, { preserveState: true });
  }
  return /* @__PURE__ */ jsxs("form", { onSubmit: saring, className: "flex flex-wrap items-end gap-3", children: [
    /* @__PURE__ */ jsxs("div", { className: "min-w-56 flex-1", children: [
      /* @__PURE__ */ jsx(Label, { htmlFor: "cari", className: "text-muted-foreground mb-1 text-xs", children: labelCari }),
      /* @__PURE__ */ jsxs(InputGroup, { children: [
        /* @__PURE__ */ jsx(InputGroupAddon, { children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Search01Icon, strokeWidth: 1.5, className: "size-4" }) }),
        /* @__PURE__ */ jsx(
          InputGroupInput,
          {
            id: "cari",
            ref: kotak,
            value: nilai.q,
            onChange: (e) => setNilai({ ...nilai, q: e.target.value }),
            placeholder: placeholderCari ?? `mis. ${contoh ?? `${prefix}-SOP`} atau judul…`
          }
        ),
        /* @__PURE__ */ jsx(InputGroupAddon, { align: "inline-end", children: /* @__PURE__ */ jsx(Kbd, { children: "/" }) })
      ] })
    ] }),
    pilihan.map((p) => /* @__PURE__ */ jsxs("div", { className: "min-w-36", children: [
      /* @__PURE__ */ jsx(Label, { htmlFor: `saring-${p.nama}`, className: "text-muted-foreground mb-1 text-xs", children: p.label }),
      p.tipe === "teks" || p.tipe === "tanggal" ? /* @__PURE__ */ jsx(
        Input,
        {
          id: `saring-${p.nama}`,
          type: p.tipe === "tanggal" ? "date" : "text",
          value: nilai[p.nama],
          placeholder: p.placeholder,
          onChange: (e) => setNilai({ ...nilai, [p.nama]: e.target.value })
        }
      ) : /* @__PURE__ */ jsxs(
        Select,
        {
          value: nilai[p.nama] || SEMUA,
          onValueChange: (v) => setNilai({ ...nilai, [p.nama]: v === SEMUA ? "" : v }),
          children: [
            /* @__PURE__ */ jsx(SelectTrigger, { id: `saring-${p.nama}`, className: "w-full", children: /* @__PURE__ */ jsx(SelectValue, {}) }),
            /* @__PURE__ */ jsx(SelectContent, { children: (p.opsi ?? []).map(([v, label]) => /* @__PURE__ */ jsx(SelectItem, { value: v || SEMUA, children: label }, v || SEMUA)) })
          ]
        }
      )
    ] }, p.nama)),
    /* @__PURE__ */ jsx("div", { className: "flex gap-1", children: adaIsi ? /* @__PURE__ */ jsx(
      Button,
      {
        type: "button",
        variant: "ghost",
        size: "icon",
        "aria-label": "Reset penyaring",
        onClick: () => router.get(url, tersembunyi),
        children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 1.5, className: "size-4" })
      }
    ) : null })
  ] });
}
function saringDepartemen(departments) {
  return {
    nama: "department_id",
    label: "Departemen",
    opsi: [["", "Semua"], ...departments.map((d) => [String(d.id), d.code])]
  };
}
function saringJenis(types) {
  return {
    nama: "type",
    label: "Jenis",
    opsi: [["", "Semua"], ...types.map((t) => [t, t])]
  };
}
function saringStatus(opsi, labels) {
  return {
    nama: "status",
    label: "Status",
    opsi: [["", "Semua"], ...opsi.map((s) => [s, labels[s] ?? s])]
  };
}
export {
  PenyaringDokumen as P,
  saringJenis as a,
  saringStatus as b,
  saringDepartemen as s
};
