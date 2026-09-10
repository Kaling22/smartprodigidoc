import { jsx, jsxs } from "react/jsx-runtime";
import { usePage, Link } from "@inertiajs/react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription, e as EmptyContent } from "./empty-CkAl4IHy.js";
import { A as AppLayout, I as Ikon } from "./AppLayout-C74XVGrz.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "react";
import "sonner";
import "@hugeicons/react";
import "@hugeicons/core-free-icons";
function InformasiNonaktif() {
  const { kat } = usePage().props;
  return /* @__PURE__ */ jsx(AppLayout, { judul: kat.nama, children: /* @__PURE__ */ jsxs(Empty, { children: [
    /* @__PURE__ */ jsxs(EmptyHeader, { children: [
      /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: kat.ikon, className: "text-muted-foreground size-8" }) }),
      /* @__PURE__ */ jsxs(EmptyTitle, { children: [
        kat.nama,
        " Sedang Ditutup"
      ] }),
      /* @__PURE__ */ jsxs(EmptyDescription, { children: [
        /* @__PURE__ */ jsx("p", { children: "Kategori ini dinonaktifkan oleh Admin, jadi daftarnya tidak dibuka dan unggahan baru tidak diterima." }),
        /* @__PURE__ */ jsxs("p", { className: "mt-2", children: [
          "Dokumen yang sudah ada ",
          /* @__PURE__ */ jsx("strong", { children: "tidak dihapus" }),
          ". Bila Anda masih memerlukannya, hubungi Admin untuk membuka kembali kategori ini."
        ] })
      ] })
    ] }),
    /* @__PURE__ */ jsx(EmptyContent, { children: /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", children: /* @__PURE__ */ jsx(Link, { href: route("dashboard"), children: "Kembali" }) }) })
  ] }) });
}
export {
  InformasiNonaktif as default
};
