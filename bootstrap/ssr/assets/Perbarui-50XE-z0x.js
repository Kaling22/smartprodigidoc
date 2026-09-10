import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { RefreshCwIcon, HistoryIcon, LinkSquare01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage } from "@inertiajs/react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, b as CardHeader, c as CardTitle } from "./card-B3VJCD2M.js";
import { F as FormInformasi } from "./FormInformasi-BhKXxe5S.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "react";
import "sonner";
import "./field-AJb7LB-s.js";
import "./label-emiz6fAI.js";
import "./input-B9Vuz8J-.js";
import "./ConfirmDialog-C9h_aHIF.js";
function InformasiPerbarui() {
  const props = usePage().props;
  const { induk } = props;
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: `Perbarui ${induk.nomor}`,
      remah: [
        { label: props.label, href: route("informasi.index", { kategori: props.kategori }) },
        { label: `Perbarui ${induk.nomor}` }
      ],
      sub: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: induk.nomor }),
        " — ",
        induk.judul,
        induk.revisi ? /* @__PURE__ */ jsx(Badge, { variant: "secondary", className: "ml-2", children: induk.revisi }) : null
      ] }),
      children: /* @__PURE__ */ jsxs("div", { className: "grid gap-4 lg:grid-cols-3", children: [
        /* @__PURE__ */ jsx(Card, { className: "lg:col-span-2", children: /* @__PURE__ */ jsxs(CardContent, { children: [
          /* @__PURE__ */ jsxs(Alert, { className: "mb-6", children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RefreshCwIcon, strokeWidth: 1.5, className: "size-4" }),
            /* @__PURE__ */ jsxs(AlertDescription, { children: [
              "Berkas ini menjadi ",
              /* @__PURE__ */ jsx("strong", { children: "versi yang berlaku" }),
              " untuk nomor",
              " ",
              /* @__PURE__ */ jsx("span", { className: "font-mono", children: induk.nomor }),
              ". Versi sekarang pindah ke ",
              /* @__PURE__ */ jsx("strong", { children: "Riwayat" }),
              ", tidak dihapus."
            ] })
          ] }),
          /* @__PURE__ */ jsx(FormInformasi, { ...props })
        ] }) }),
        /* @__PURE__ */ jsxs(Card, { className: "h-fit", children: [
          /* @__PURE__ */ jsx(CardHeader, { children: /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-1.5", children: [
            /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: HistoryIcon,
                strokeWidth: 1.5,
                className: "size-4",
                "aria-hidden": "true"
              }
            ),
            "Versi Sekarang"
          ] }) }),
          /* @__PURE__ */ jsxs(CardContent, { className: "grid gap-2 text-sm", children: [
            /* @__PURE__ */ jsx(Rincian, { label: "Judul", children: induk.judul }),
            induk.revisi ? /* @__PURE__ */ jsx(Rincian, { label: "Edisi / Revisi", children: induk.revisi }) : null,
            induk.tanggal ? /* @__PURE__ */ jsx(Rincian, { label: "Tgl Efektif", children: induk.tanggal }) : null,
            /* @__PURE__ */ jsxs(Rincian, { label: "Diunggah", children: [
              induk.diunggah_lengkap,
              " WITA"
            ] }),
            /* @__PURE__ */ jsx(Rincian, { label: "Oleh", children: induk.oleh ?? "—" }),
            /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", className: "mt-2 w-fit", children: /* @__PURE__ */ jsxs("a", { href: route("informasi.file", induk.id), target: "_blank", rel: "noopener", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: LinkSquare01Icon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              ),
              "Buka berkas sekarang"
            ] }) })
          ] })
        ] })
      ] })
    }
  );
}
function Rincian({ label, children }) {
  return /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-[5rem_1fr] gap-2", children: [
    /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: label }),
    /* @__PURE__ */ jsx("span", { children })
  ] });
}
export {
  InformasiPerbarui as default
};
