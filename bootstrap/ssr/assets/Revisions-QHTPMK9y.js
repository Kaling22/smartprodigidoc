import { jsx, jsxs } from "react/jsx-runtime";
import { CheckmarkCircle01Icon, FileEditIcon, ArrowTurnBackwardIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
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
function DocumentsRevisions() {
  const { documents } = usePage().props;
  const kolom = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    { judul: "Judul", render: (d) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: d.judul }) },
    {
      judul: "Status",
      render: (d) => d.draft_revisi ? /* @__PURE__ */ jsxs(Badge, { variant: "secondary", children: [
        "Revisi Diajukan (Edisi ",
        d.edisi,
        " Rev ",
        d.no_revisi,
        ")"
      ] }) : /* @__PURE__ */ jsx(Badge, { variant: "destructive", children: d.status_label })
    },
    {
      judul: "Feedback Peninjau",
      kelas: "min-w-56",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "text-sm", children: [
        d.rangkuman ? /* @__PURE__ */ jsx("p", { className: "text-destructive font-semibold whitespace-pre-line", children: potong(d.rangkuman, 220) }) : null,
        d.anotasi.map((a, i) => /* @__PURE__ */ jsxs("p", { className: "text-destructive", children: [
          "· ",
          /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
            "[",
            a.bagian,
            "]"
          ] }),
          " ",
          potong(a.komentar, 70)
        ] }, i)),
        !d.rangkuman && d.anotasi.length === 0 ? /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "Lihat komentar approver / peninjau di form revisi." }) : null,
        d.anotasi_sisa > 0 ? /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground", children: [
          "+",
          d.anotasi_sisa,
          " lainnya…"
        ] }) : null
      ] })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "icon", title: "Lihat PDF", children: /* @__PURE__ */ jsx("a", { href: route("documents.pdf", d.id), target: "_blank", rel: "noopener", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }) }) }),
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "secondary", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.edit", d.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowTurnBackwardIcon, strokeWidth: 1.5, className: "size-4" }),
          "Revisi"
        ] }) })
      ] })
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Dokumen Revisi",
      sub: `Dokumen yang perlu Anda revisi, alasannya tertulis pada tiap baris — ${documents.total} dokumen.`,
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom,
            baris: documents.data,
            kunci: (d) => d.id,
            kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
              /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: CheckmarkCircle01Icon,
                  strokeWidth: 1.5,
                  className: "text-chart-5 size-6"
                }
              ) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada dokumen yang perlu direvisi" })
            ] }) })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
      ] })
    }
  );
}
function potong(teks, batas) {
  return teks.length > batas ? `${teks.slice(0, batas)}…` : teks;
}
export {
  DocumentsRevisions as default
};
