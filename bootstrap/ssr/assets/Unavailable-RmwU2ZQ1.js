import { jsx, jsxs } from "react/jsx-runtime";
import { TrafficConeIcon, FileAddIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription, e as EmptyContent } from "./empty-CkAl4IHy.js";
import { A as AppLayout } from "./AppLayout-C74XVGrz.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "react";
import "sonner";
function DocumentsUnavailable() {
  const { type, jenisBaru } = usePage().props;
  return /* @__PURE__ */ jsx(AppLayout, { judul: "Jenis Dokumen Belum Tersedia", children: /* @__PURE__ */ jsxs(Empty, { children: [
    /* @__PURE__ */ jsxs(EmptyHeader, { children: [
      /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
        HugeiconsIcon,
        {
          icon: TrafficConeIcon,
          strokeWidth: 1.5,
          className: "text-chart-3 size-8"
        }
      ) }),
      /* @__PURE__ */ jsxs(EmptyTitle, { children: [
        "Dokumen ",
        type.code,
        " Belum Tersedia"
      ] }),
      /* @__PURE__ */ jsxs(EmptyDescription, { children: [
        type.name,
        " — schema untuk jenis dokumen ini sedang menunggu contoh dokumen dari tim. Jenis lain akan aktif setelah format & contohnya tersedia."
      ] })
    ] }),
    /* @__PURE__ */ jsx(EmptyContent, { children: /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap justify-center gap-2", children: [
      jenisBaru ? /* @__PURE__ */ jsx(Button, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("documents.create", { type: jenisBaru }), children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: FileAddIcon,
            strokeWidth: 1.5,
            className: "size-4"
          }
        ),
        "Buat Dokumen ",
        jenisBaru
      ] }) }) : null,
      /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", children: /* @__PURE__ */ jsx(Link, { href: route("documents.index"), children: "Kembali" }) })
    ] }) })
  ] }) });
}
export {
  DocumentsUnavailable as default
};
