import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { RadioIcon, ArrowLeft01Icon, FilterIcon, Cancel01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, a as CardContent } from "./card-B3VJCD2M.js";
import { F as Field, a as FieldLabel } from "./field-AJb7LB-s.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { P as PitaCakupan } from "./PitaCakupan-D6HiIzDA.js";
import { R as RincianPembaca } from "./RincianPembaca-DlYicxjy.js";
import { A as AppLayout } from "./AppLayout-C74XVGrz.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./label-emiz6fAI.js";
import "./progress-CJN4SIW1.js";
import "sonner";
function DocumentsRincianInformasi() {
  const { informasi, cakupan, rincian, departments, deptId } = usePage().props;
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: "Rincian Distribusi Informasi",
      sub: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: informasi.nomor }),
        " · ",
        informasi.kategori,
        informasi.revisi ? ` · ${informasi.revisi}` : ""
      ] }),
      aksi: /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", variant: "outline", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.distribution", { sumber: "informasi" }), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeft01Icon, strokeWidth: 1.5, className: "size-4" }),
        "Kembali ke Distribusi"
      ] }) }),
      children: [
        /* @__PURE__ */ jsx("h2", { className: "text-lg font-bold", children: informasi.judul }),
        /* @__PURE__ */ jsxs(Card, { children: [
          /* @__PURE__ */ jsxs(CardHeader, { className: "flex flex-wrap items-center justify-between gap-2", children: [
            /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: RadioIcon,
                  strokeWidth: 1.5,
                  className: "size-4",
                  "aria-hidden": "true"
                }
              ),
              "Distribusi"
            ] }),
            /* @__PURE__ */ jsx("div", { className: "min-w-56", children: /* @__PURE__ */ jsx(PitaCakupan, { c: cakupan }) })
          ] }),
          departments.length > 0 ? /* @__PURE__ */ jsx(CardContent, { className: "border-b pb-6", children: /* @__PURE__ */ jsx(PemilihDept, { informasiId: informasi.id, departments, deptId }) }) : null,
          /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsx(
            RincianPembaca,
            {
              rincian,
              cakupan,
              kosong: "Tak ada pengguna aktif yang dihitung sebagai sasaran di lingkup ini, jadi tak ada yang bisa diukur."
            }
          ) })
        ] })
      ]
    }
  );
}
function PemilihDept({
  informasiId,
  departments,
  deptId
}) {
  const SEMUA = "*";
  const [pilihan, setPilihan] = useState(deptId ? String(deptId) : SEMUA);
  function terapkan(e) {
    e.preventDefault();
    router.get(route("documents.rincianInformasi", informasiId), {
      ...pilihan === SEMUA ? {} : { department_id: pilihan }
    });
  }
  return /* @__PURE__ */ jsxs("form", { onSubmit: terapkan, className: "flex flex-wrap items-end gap-2", children: [
    /* @__PURE__ */ jsxs(Field, { className: "w-auto min-w-48", children: [
      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "dept", children: "Departemen" }),
      /* @__PURE__ */ jsxs(Select, { value: pilihan, onValueChange: setPilihan, children: [
        /* @__PURE__ */ jsx(SelectTrigger, { id: "dept", className: "w-full", children: /* @__PURE__ */ jsx(SelectValue, {}) }),
        /* @__PURE__ */ jsxs(SelectContent, { children: [
          /* @__PURE__ */ jsx(SelectItem, { value: SEMUA, children: "Semua (7 departemen)" }),
          departments.map((d) => /* @__PURE__ */ jsx(SelectItem, { value: String(d.id), children: d.code }, d.id))
        ] })
      ] })
    ] }),
    /* @__PURE__ */ jsxs(Button, { type: "submit", size: "sm", variant: "secondary", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FilterIcon, strokeWidth: 1.5, className: "size-4" }),
      "Terapkan"
    ] }),
    deptId ? /* @__PURE__ */ jsx(
      Button,
      {
        type: "button",
        size: "icon",
        variant: "ghost",
        title: "Reset",
        "aria-label": "Reset penyaring departemen",
        onClick: () => router.get(route("documents.rincianInformasi", informasiId)),
        children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 1.5, className: "size-4" })
      }
    ) : null
  ] });
}
export {
  DocumentsRincianInformasi as default
};
