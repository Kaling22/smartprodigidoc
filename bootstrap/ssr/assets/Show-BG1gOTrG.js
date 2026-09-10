import { jsxs, jsx } from "react/jsx-runtime";
import { InformationCircleIcon, InboxIcon, Message01Icon, ViewIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, b as CardHeader, c as CardTitle } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { S as StatusBadge } from "./StatusBadge-BV-SY8b9.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "react";
import "sonner";
function MasukanSejawatShow() {
  const { document: doc, masukan } = usePage().props;
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: `Masukan Sejawat: ${doc.nomor}`,
      sub: /* @__PURE__ */ jsxs("span", { className: "flex flex-wrap items-center gap-1.5", children: [
        /* @__PURE__ */ jsx("span", { className: "font-medium", children: doc.judul }),
        /* @__PURE__ */ jsx("span", { className: "text-primary font-mono text-sm", children: doc.nomor }),
        /* @__PURE__ */ jsx(Badge, { variant: "outline", children: doc.dept ?? "—" }),
        /* @__PURE__ */ jsx(StatusBadge, { status: doc.status })
      ] }),
      aksi: /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.show", doc.id), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }),
        "Lihat Dokumen"
      ] }) }),
      children: [
        /* @__PURE__ */ jsxs(Alert, { children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsxs(AlertDescription, { children: [
            "Catatan dari rekan sedepartemen atas dokumen ini. Ini",
            " ",
            /* @__PURE__ */ jsx("strong", { children: "bukan hasil peninjauan" }),
            " — tak ada status yang berubah karenanya, dan Anda bebas memakainya atau tidak saat menyunting dokumen."
          ] })
        ] }),
        masukan.length === 0 ? /* @__PURE__ */ jsx(Card, { children: /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
          /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: InboxIcon,
              strokeWidth: 1.5,
              className: "size-6"
            }
          ) }),
          /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada masukan sejawat atas dokumen ini." })
        ] }) }) }) }) : masukan.map((m) => (
          // Anchor per kartu supaya Log Pesan & lonceng bisa menaut
          // langsung ke SATU masukan, bukan hanya ke halamannya.
          /* @__PURE__ */ jsxs(Card, { id: `m${m.id}`, children: [
            /* @__PURE__ */ jsxs(CardHeader, { className: "flex flex-wrap items-center justify-between gap-2", children: [
              /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: Message01Icon,
                    strokeWidth: 1.5,
                    className: "size-4",
                    "aria-hidden": "true"
                  }
                ),
                m.oleh
              ] }),
              /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: m.waktu })
            ] }),
            /* @__PURE__ */ jsxs(CardContent, { className: "grid gap-3", children: [
              m.ringkasan ? /* @__PURE__ */ jsxs("div", { children: [
                /* @__PURE__ */ jsx("div", { className: "text-muted-foreground mb-1 text-xs font-semibold", children: "Ringkasan / Catatan Umum" }),
                /* @__PURE__ */ jsx("p", { className: "whitespace-pre-line", children: m.ringkasan })
              ] }) : null,
              m.perSection.map((s, i) => /* @__PURE__ */ jsxs("div", { className: "grid gap-2 rounded-2xl border p-3", children: [
                /* @__PURE__ */ jsx("div", { className: "text-sm font-semibold", children: s.label }),
                s.items.map((c, j) => /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap gap-2", children: [
                  /* @__PURE__ */ jsx(Badge, { variant: "secondary", className: "whitespace-nowrap", children: c.ref }),
                  /* @__PURE__ */ jsx("span", { className: "whitespace-pre-line", children: c.komentar })
                ] }, j))
              ] }, i)),
              m.perSection.length === 0 && !m.ringkasan ? /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "(Masukan kosong)" }) : null
            ] })
          ] }, m.id)
        ))
      ]
    }
  );
}
export {
  MasukanSejawatShow as default
};
