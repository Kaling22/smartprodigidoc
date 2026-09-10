import { jsxs, jsx } from "react/jsx-runtime";
import { InboxIcon, ArrowTurnBackwardIcon, Exchange01Icon, ClipboardCheckIcon, ViewIcon, FileEditIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, router } from "@inertiajs/react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, e as CardFooter, b as CardHeader, c as CardTitle, d as CardDescription } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
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
function ReviewIndex() {
  const { documents, statusRevisi } = usePage().props;
  const antrean = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    { judul: "Judul", render: (d) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: d.judul }) },
    { judul: "Jenis", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.jenis ?? "—" }) },
    { judul: "Dept", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.dept ?? "—" }) },
    { judul: "Pembuat", render: (d) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: d.pembuat ?? "—" }) },
    {
      judul: "Edisi/Revisi",
      urut: "revisi",
      // Angka DOKUMEN (yang tercetak di kop), bukan penghitung putaran
      // peninjauan — lihat catatan di `Review/Show.tsx`.
      render: (d) => /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
        "Ed. ",
        d.edisi,
        " · Rev. ",
        d.no_revisi
      ] })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(TombolPdf, { id: d.id }),
        d.boleh_alih ? /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("review.show", { document: d.id, alih: 1 }), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Exchange01Icon, strokeWidth: 1.5, className: "size-4" }),
          "Alihkan"
        ] }) }) : null,
        /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("review.show", d.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ClipboardCheckIcon, strokeWidth: 1.5, className: "size-4" }),
          "Tinjau"
        ] }) })
      ] })
    }
  ];
  const revisi = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    { judul: "Judul", render: (d) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: d.judul }) },
    { judul: "Pembuat", render: (d) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: d.pembuat ?? "—" }) },
    {
      judul: "Tahap",
      render: () => /* @__PURE__ */ jsx(Badge, { variant: "destructive", children: "Ditolak — menunggu revisi pembuat" })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(TombolPdf, { id: d.id }),
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.show", d.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }),
          "Lihat"
        ] }) }),
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            judul: "Batalkan Revisi?",
            pesan: "Batalkan penolakan? Dokumen kembali ditinjau (in_review) untuk Anda periksa ulang.",
            tombolYa: "Ya, batalkan",
            onKonfirmasi: () => router.post(
              route("review.cancelRevision", d.id),
              {},
              { preserveScroll: true }
            ),
            pemicu: /* @__PURE__ */ jsxs(Button, { variant: "secondary", size: "sm", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: ArrowTurnBackwardIcon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              ),
              "Batalkan Revisi"
            ] })
          }
        )
      ] })
    }
  ];
  return /* @__PURE__ */ jsxs(AppLayout, { judul: "Tinjau Dokumen", sub: "Dokumen yang menunggu peninjauan Anda.", children: [
    /* @__PURE__ */ jsxs(Card, { children: [
      /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
        DataTable,
        {
          kolom: antrean,
          baris: documents.data,
          kunci: (d) => d.id,
          kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
            /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: InboxIcon,
                strokeWidth: 1.5,
                className: "size-6"
              }
            ) }),
            /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada dokumen untuk ditinjau." })
          ] }) })
        }
      ) }),
      /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
    ] }),
    /* @__PURE__ */ jsxs(Card, { children: [
      /* @__PURE__ */ jsxs(CardHeader, { className: "border-b", children: [
        /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-1.5", children: [
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: ArrowTurnBackwardIcon,
              strokeWidth: 1.5,
              className: "size-4"
            }
          ),
          "Status Revisi"
        ] }),
        /* @__PURE__ */ jsx(CardDescription, { children: "Dokumen yang Anda kembalikan." })
      ] }),
      /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
        DataTable,
        {
          kolom: revisi,
          baris: statusRevisi,
          kunci: (d) => d.id,
          kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
            /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: InboxIcon,
                strokeWidth: 1.5,
                className: "size-6"
              }
            ) }),
            /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada dokumen yang Anda tolak." })
          ] }) })
        }
      ) })
    ] })
  ] });
}
function TombolPdf({ id }) {
  return /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "icon", title: "Lihat PDF", children: /* @__PURE__ */ jsx("a", { href: route("documents.pdf", id), target: "_blank", rel: "noopener", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }) }) });
}
export {
  ReviewIndex as default
};
