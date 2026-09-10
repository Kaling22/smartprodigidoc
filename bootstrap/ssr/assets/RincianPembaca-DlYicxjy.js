import { jsx, jsxs } from "react/jsx-runtime";
import { CheckmarkCircle01Icon, SmartPhone01Icon, LaptopIcon, AlertCircleIcon, CheckmarkCircle02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { B as Badge, a as Avatar } from "./AppLayout-C74XVGrz.js";
function RincianPembaca({
  rincian,
  cakupan,
  kosong = "Tak ada pengguna aktif yang bisa dihitung sebagai sasaran, jadi tak ada yang bisa diukur."
}) {
  if (cakupan.sasaran === 0) {
    return /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: kosong });
  }
  return /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:grid-cols-2", children: [
    /* @__PURE__ */ jsxs("div", { children: [
      /* @__PURE__ */ jsxs("p", { className: "mb-2 flex items-center gap-1.5 text-sm font-semibold", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: CheckmarkCircle01Icon,
            strokeWidth: 1.5,
            className: "text-chart-5 size-4",
            "aria-hidden": "true"
          }
        ),
        "Sudah Membaca",
        /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: rincian.sudah.length })
      ] }),
      rincian.sudah.length === 0 ? /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "Belum ada yang membukanya." }) : rincian.sudah.map((r, i) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between gap-2 border-b py-1", children: [
        /* @__PURE__ */ jsxs("span", { className: "flex min-w-0 items-center gap-1.5 text-sm", children: [
          /* @__PURE__ */ jsx(Avatar, { nama: r.nama, foto: r.foto, className: "size-6 shrink-0" }),
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: r.platform === "mobile" ? SmartPhone01Icon : LaptopIcon,
              strokeWidth: 1.5,
              className: "text-muted-foreground size-3.5 shrink-0",
              "aria-hidden": "true"
            }
          ),
          /* @__PURE__ */ jsx("span", { className: "truncate", children: r.nama }),
          /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground shrink-0", children: [
            "· ",
            r.jabatan
          ] })
        ] }),
        /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-xs", children: [
          r.waktu,
          " WITA"
        ] })
      ] }, i))
    ] }),
    /* @__PURE__ */ jsxs("div", { children: [
      /* @__PURE__ */ jsxs("p", { className: "mb-2 flex items-center gap-1.5 text-sm font-semibold", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: AlertCircleIcon,
            strokeWidth: 1.5,
            className: "text-destructive size-4",
            "aria-hidden": "true"
          }
        ),
        "Belum Membaca",
        /* @__PURE__ */ jsx(Badge, { variant: "destructive", children: rincian.belum.length })
      ] }),
      rincian.belum.length === 0 ? /* @__PURE__ */ jsxs("p", { className: "text-chart-5 flex items-center gap-1.5 text-sm", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: CheckmarkCircle02Icon,
            strokeWidth: 1.5,
            className: "size-3.5",
            "aria-hidden": "true"
          }
        ),
        "Semua sudah membaca."
      ] }) : rincian.belum.map((u, i) => /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5 border-b py-1 text-sm", children: [
        /* @__PURE__ */ jsx(Avatar, { nama: u.nama, foto: u.foto, className: "size-6 shrink-0" }),
        /* @__PURE__ */ jsx("span", { className: "truncate", children: u.nama }),
        /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground shrink-0", children: [
          " · ",
          u.jabatan
        ] })
      ] }, i))
    ] })
  ] });
}
export {
  RincianPembaca as R
};
