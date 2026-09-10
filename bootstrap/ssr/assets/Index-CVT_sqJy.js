import { jsx, jsxs } from "react/jsx-runtime";
import { InboxIcon, FileEditIcon, CheckmarkBadge01Icon } from "@hugeicons/core-free-icons";
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
function ApprovalsIndex() {
  const { documents } = usePage().props;
  const kolom = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    { judul: "Judul", render: (d) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: d.judul }) },
    { judul: "Dept", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.dept ?? "—" }) },
    { judul: "Pembuat", render: (d) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: d.pembuat ?? "—" }) },
    { judul: "Peninjau", render: (d) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: d.peninjau ?? "—" }) },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "icon", title: "Lihat PDF", children: /* @__PURE__ */ jsx("a", { href: route("documents.pdf", d.id), target: "_blank", rel: "noopener", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }) }) }),
        /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("approvals.show", d.id), children: [
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: CheckmarkBadge01Icon,
              strokeWidth: 1.5,
              className: "size-4"
            }
          ),
          "Tinjau & Setujui"
        ] }) })
      ] })
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Persetujuan Saya",
      sub: "Dokumen yang lolos tinjauan dan menunggu persetujuan Anda.",
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
                  icon: InboxIcon,
                  strokeWidth: 1.5,
                  className: "size-6"
                }
              ) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada dokumen yang menunggu persetujuan." })
            ] }) })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
      ] })
    }
  );
}
export {
  ApprovalsIndex as default
};
