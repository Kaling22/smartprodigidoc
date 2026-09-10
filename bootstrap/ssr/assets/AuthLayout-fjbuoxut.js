import { jsxs, jsx } from "react/jsx-runtime";
import { Head, Link } from "@inertiajs/react";
import { c as cn } from "../uji-render.js";
function AuthLayout({
  judul,
  lebar = "max-w-xs",
  children
}) {
  return /* @__PURE__ */ jsxs("div", { className: "ui-v2 grid min-h-svh lg:grid-cols-2", children: [
    /* @__PURE__ */ jsx(Head, { title: judul }),
    /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-4 p-6 md:p-10", children: [
      /* @__PURE__ */ jsx("div", { className: "flex flex-1 items-center justify-center", children: /* @__PURE__ */ jsxs("div", { className: cn("flex w-full flex-col gap-6", lebar), children: [
        /* @__PURE__ */ jsxs(Link, { href: route("login"), className: "flex items-center gap-2 font-medium", children: [
          /* @__PURE__ */ jsx(
            "img",
            {
              src: "/images/logo-web.png",
              alt: "SmartPro",
              className: "h-12 w-auto object-contain dark:hidden"
            }
          ),
          /* @__PURE__ */ jsx(
            "img",
            {
              src: "/images/logodarkmode.png",
              alt: "SmartPro",
              className: "hidden h-12 w-auto object-contain dark:block"
            }
          )
        ] }),
        children
      ] }) }),
      /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground text-center text-xs", children: [
        "© ",
        (/* @__PURE__ */ new Date()).getFullYear(),
        " PT Putra Perkasa Abadi — Divisi ICTMD"
      ] })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "bg-primary relative hidden lg:block", children: [
      /* @__PURE__ */ jsx(
        "img",
        {
          src: "/images/login.jpg",
          alt: "",
          className: "absolute inset-0 h-full w-full object-cover"
        }
      ),
      /* @__PURE__ */ jsx("div", { className: "from-primary/85 via-primary/30 to-primary/60 absolute inset-0 bg-gradient-to-t" }),
      /* @__PURE__ */ jsxs("div", { className: "text-primary-foreground absolute inset-0 flex flex-col justify-between gap-8 p-12", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-1 flex-col justify-center gap-4", children: [
          /* @__PURE__ */ jsx("p", { className: "max-w-sm text-3xl leading-tight font-semibold text-balance", children: "Dokumen mutu, satu pintu." }),
          /* @__PURE__ */ jsx("p", { className: "max-w-sm text-sm/relaxed opacity-80", children: "SOP, Instruksi Kerja, Standar Parameter, dan JSA untuk tujuh departemen PT Putra Perkasa Abadi — site Adaro." })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "bg-primary-foreground/10 ring-primary-foreground/20 max-w-sm rounded-2xl p-6 shadow-lg ring-1", children: [
          /* @__PURE__ */ jsx("p", { className: "font-semibold", children: "Satu alur, dari draft sampai berlaku" }),
          /* @__PURE__ */ jsx("p", { className: "mt-1.5 text-sm/relaxed opacity-80", children: "Tiap dokumen menempuh jalur tinjau dan setuju yang sama, dan riwayat revisinya tersimpan sampai versi terakhir." })
        ] })
      ] })
    ] })
  ] });
}
export {
  AuthLayout as A
};
