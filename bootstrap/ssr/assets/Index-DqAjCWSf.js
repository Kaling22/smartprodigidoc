import { jsx, jsxs } from "react/jsx-runtime";
import { ClipboardListIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { P as PenyaringDokumen } from "./PenyaringDokumen-ZbRxHXvI.js";
import "react";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./button-DLS2B9Gu.js";
import "./table-COSAIfdh.js";
import "./input-B9Vuz8J-.js";
import "./input-group-C5LPhh_3.js";
import "./label-emiz6fAI.js";
import "./select-Db_F_VlA.js";
function AuditIndex() {
  const { logs, filters } = usePage().props;
  const kolom = [
    {
      judul: "Waktu (WITA)",
      kelas: "whitespace-nowrap",
      render: (l) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: l.waktu })
    },
    {
      judul: "User",
      render: (l) => /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
        l.oleh,
        " ",
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: l.nrp ?? "" })
      ] })
    },
    {
      judul: "Aksi",
      render: (l) => /* @__PURE__ */ jsx(Badge, { variant: "secondary", className: "font-mono", children: l.aksi })
    },
    {
      judul: "Dokumen",
      render: (l) => l.dokumen ? /* @__PURE__ */ jsx(Link, { href: route("documents.show", l.dokumen.id), className: "font-mono text-sm hover:underline", children: l.dokumen.nomor }) : /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "—" })
    },
    {
      judul: "IP",
      render: (l) => /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: l.ip ?? "—" })
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Audit Log",
      sub: "Jejak seluruh aksi penting (termasuk aksi admin) — waktu WITA.",
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("audit.index"),
            filters,
            labelCari: "Cari user (nama / NRP)",
            placeholderCari: "mis. Budi atau GL-0001...",
            pilihan: [
              {
                nama: "action",
                label: "Aksi",
                tipe: "teks",
                placeholder: "mis. approve, submit"
              }
            ]
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom,
            baris: logs.data,
            kunci: (l) => l.id,
            kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
              /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ClipboardListIcon, strokeWidth: 1.5, className: "size-6" }) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada catatan audit" })
            ] }) })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: logs }) })
      ] })
    }
  );
}
export {
  AuditIndex as default
};
