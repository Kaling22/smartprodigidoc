import { jsx, jsxs } from "react/jsx-runtime";
import { HourglassIcon, OctagonXIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { router } from "@inertiajs/react";
import { A as Alert, b as AlertTitle, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { A as AuthLayout } from "./AuthLayout-fjbuoxut.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
function Pending({ name, status }) {
  return /* @__PURE__ */ jsx(AuthLayout, { judul: "Menunggu Persetujuan", lebar: "max-w-sm", children: /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-4", children: [
    /* @__PURE__ */ jsx("div", { className: "bg-muted flex size-12 items-center justify-center rounded-full", children: /* @__PURE__ */ jsx(
      HugeiconsIcon,
      {
        icon: HourglassIcon,
        strokeWidth: 1.5,
        className: "text-muted-foreground size-6"
      }
    ) }),
    /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-1", children: [
      /* @__PURE__ */ jsx("h1", { className: "text-2xl font-bold", children: "Akun Menunggu Persetujuan" }),
      /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground text-sm text-balance", children: [
        "Halo ",
        /* @__PURE__ */ jsx("strong", { className: "text-foreground", children: name }),
        ", pendaftaran Anda sudah diterima. Akun akan aktif setelah disetujui oleh Group Leader, Pimpinan, atau Admin IT departemen Anda."
      ] })
    ] }),
    status === "rejected" ? /* @__PURE__ */ jsxs(Alert, { variant: "destructive", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: OctagonXIcon, strokeWidth: 1.5, className: "size-4" }),
      /* @__PURE__ */ jsx(AlertTitle, { children: "Pendaftaran ditolak" }),
      /* @__PURE__ */ jsx(AlertDescription, { children: "Silakan hubungi administrator departemen Anda." })
    ] }) : null,
    /* @__PURE__ */ jsx(Button, { variant: "outline", onClick: () => router.post(route("logout")), children: "Kembali ke Login" })
  ] }) });
}
export {
  Pending as default
};
