import { jsx, jsxs } from "react/jsx-runtime";
import { usePage } from "@inertiajs/react";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent } from "./card-B3VJCD2M.js";
import { F as FormInformasi } from "./FormInformasi-BhKXxe5S.js";
import { A as AppLayout } from "./AppLayout-C74XVGrz.js";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "@hugeicons/core-free-icons";
import "@hugeicons/react";
import "react";
import "./alert-BvkCPa_V.js";
import "class-variance-authority";
import "./button-DLS2B9Gu.js";
import "./field-AJb7LB-s.js";
import "./label-emiz6fAI.js";
import "./input-B9Vuz8J-.js";
import "./ConfirmDialog-C9h_aHIF.js";
import "sonner";
function InformasiCreate() {
  const props = usePage().props;
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: `Tambah ${props.label}`,
      remah: [
        { label: props.label, href: route("informasi.index", { kategori: props.kategori }) },
        { label: "Tambah" }
      ],
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsxs(CardHeader, { children: [
          /* @__PURE__ */ jsxs(CardTitle, { children: [
            "Tambah ",
            props.label
          ] }),
          /* @__PURE__ */ jsxs(CardDescription, { children: [
            "Untuk dokumen yang ",
            /* @__PURE__ */ jsx("strong", { children: "belum ada" }),
            " di daftar. Yang sudah ada diperbarui lewat tombol Perbarui pada barisnya."
          ] })
        ] }),
        /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsx(FormInformasi, { ...props }) })
      ] })
    }
  );
}
export {
  InformasiCreate as default
};
