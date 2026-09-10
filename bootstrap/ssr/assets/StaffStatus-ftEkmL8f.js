import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { InboxIcon, ViewIcon, FileEditIcon, Message01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
import { P as PenyaringDokumen, s as saringDepartemen, a as saringJenis, b as saringStatus } from "./PenyaringDokumen-ZbRxHXvI.js";
import { S as StatusBadge } from "./StatusBadge-BV-SY8b9.js";
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
function DocumentsStaffStatus() {
  const {
    documents,
    filters,
    types,
    departments,
    canAll,
    selectedDept,
    isGlDept,
    judul,
    statusOpsi,
    prefix,
    statusLabels
  } = usePage().props;
  const kolom = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    { judul: "Judul", render: (d) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: d.judul }) },
    { judul: "Jenis", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.jenis ?? "—" }) },
    { judul: "Status", render: (d) => /* @__PURE__ */ jsx(StatusBadge, { status: d.status }) },
    {
      judul: "Pembuat",
      render: (d) => /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: d.pembuat ?? "—" })
    },
    {
      judul: "Lihat",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "icon", title: "Lihat PDF", children: /* @__PURE__ */ jsx("a", { href: route("documents.pdf", d.id), target: "_blank", rel: "noopener", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }) }) }),
        d.boleh_masukan_sejawat ? /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("masukan-sejawat.create", d.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Message01Icon, strokeWidth: 1.5, className: "size-4" }),
          "Beri Masukan"
        ] }) }) : null,
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.show", d.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }),
          "Lihat"
        ] }) })
      ] })
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul,
      sub: /* @__PURE__ */ jsxs("span", { className: "flex flex-wrap items-center gap-1", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: ViewIcon,
            strokeWidth: 1.5,
            className: "size-4",
            "aria-hidden": "true"
          }
        ),
        "Read-only — ",
        isGlDept ? "seluruh dokumen" : "pantau status dokumen",
        " ",
        canAll ? selectedDept ? /* @__PURE__ */ jsxs(Fragment, { children: [
          "di departemen ",
          /* @__PURE__ */ jsx("strong", { children: selectedDept.code })
        ] }) : "di seluruh departemen (pilih dept di sub-menu)" : "di departemen Anda",
        "."
      ] }),
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("documents.staffStatus"),
            filters,
            prefix,
            pilihan: [
              ...departments.length > 0 ? [saringDepartemen(departments)] : [],
              saringJenis(types),
              saringStatus(statusOpsi, statusLabels)
            ],
            tersembunyi: {}
          }
        ) }),
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
                  icon: InboxIcon,
                  strokeWidth: 1.5,
                  className: "size-6"
                }
              ) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: canAll && !selectedDept ? "Pilih departemen di sub-menu untuk melihat dokumennya." : "Belum ada dokumen sesuai filter." })
            ] }) })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
      ] })
    }
  );
}
export {
  DocumentsStaffStatus as default
};
