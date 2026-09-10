import { jsx, jsxs } from "react/jsx-runtime";
import { ListViewIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { A as AppLayout, B as Badge, I as Ikon } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { s as saringDepartemen, P as PenyaringDokumen } from "./PenyaringDokumen-ZbRxHXvI.js";
import "react";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./table-COSAIfdh.js";
import "./input-B9Vuz8J-.js";
import "./input-group-C5LPhh_3.js";
import "./label-emiz6fAI.js";
import "./select-Db_F_VlA.js";
function LogPesan() {
  const { pesan, filters, tahapan, departments, prefix } = usePage().props;
  const pilihan = [
    {
      nama: "tahap",
      label: "Tahap",
      opsi: [
        ["", "Semua"],
        ...Object.entries(tahapan).map(([k, [label]]) => [k, label])
      ]
    },
    ...departments.length > 0 ? [saringDepartemen(departments)] : [],
    { nama: "dari", label: "Dari", tipe: "tanggal" },
    { nama: "sampai", label: "Sampai", tipe: "tanggal" }
  ];
  const kolom = [
    {
      judul: "Tanggal",
      kelas: "whitespace-nowrap",
      render: (b) => /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
        b.tanggal,
        " WITA"
      ] })
    },
    {
      judul: "Dokumen",
      render: (b) => /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
        /* @__PURE__ */ jsx("span", { className: "block font-mono font-semibold", children: b.nomor }),
        /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
          b.judul,
          " ",
          /* @__PURE__ */ jsx(Badge, { variant: "secondary", className: "font-normal", children: b.dept })
        ] })
      ] })
    },
    {
      judul: "Tahap",
      render: (b) => {
        const [label, warna] = tahapan[b.tahap] ?? [b.tahap, "secondary"];
        return /* @__PURE__ */ jsx(Badge, { variant: rona(warna), children: label });
      }
    },
    { judul: "Oleh", render: (b) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: b.oleh ?? "—" }) },
    { judul: "Pesan", kelas: "max-w-104", render: (b) => /* @__PURE__ */ jsx(Alasan, { alasan: b.alasan }) },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (b) => (
        /*
         | Tujuan, label, DAN ikonnya seluruhnya dari controller
         | (`DocumentLogController::tautan`): ia bergantung pada
         | PENONTON, bukan hanya tahap — alasan penolakan yang sama
         | mengantar PEMBUATNYA ke form revisi dan orang lain ke halaman
         | dokumen. Peta kedua sengaja TIDAK ditaruh di sini: dua peta
         | yang harus sepakat adalah dua peta yang suatu hari tidak
         | sepakat. `null` = tanpa tombol (baris pemusnahan).
         */
        b.tautan ? /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: b.tautan.url, children: [
          /* @__PURE__ */ jsx(Ikon, { nama: b.tautan.ikon, className: "size-4" }),
          b.tautan.label
        ] }) }) : null
      )
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Log Pesan",
      sub: `Alasan revisi, penolakan, pengalihan, pemusnahan, dan balasan masukan ${departments.length > 0 ? "di 7 departemen" : "di departemen Anda"}. Baca saja.`,
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("log.pesan"),
            filters,
            labelCari: "Cari (pesan / nomor / judul)",
            placeholderCari: `mis. APD atau ${prefix}-SOP...`,
            pilihan
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom,
            baris: pesan.data,
            kunci: (b, i) => `${b.tahap}-${b.nomor}-${i}`,
            kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
              /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ListViewIcon, strokeWidth: 1.5, className: "size-6" }) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada pesan tercatat" })
            ] }) })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: pesan }) })
      ] })
    }
  );
}
function rona(warna) {
  if (warna === "danger") return "destructive";
  if (warna === "primary") return "default";
  if (warna === "secondary") return "outline";
  return "secondary";
}
function Alasan({ alasan }) {
  if (alasan.length <= 140) {
    return /* @__PURE__ */ jsx("span", { className: "text-sm", children: alasan });
  }
  return /* @__PURE__ */ jsxs("details", { className: "text-sm", children: [
    /* @__PURE__ */ jsxs("summary", { className: "cursor-pointer list-none [&::-webkit-details-marker]:hidden", children: [
      alasan.slice(0, 140),
      "…",
      /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs", children: " selengkapnya" })
    ] }),
    /* @__PURE__ */ jsx("div", { className: "mt-1 whitespace-pre-line", children: alasan })
  ] });
}
export {
  LogPesan as default
};
