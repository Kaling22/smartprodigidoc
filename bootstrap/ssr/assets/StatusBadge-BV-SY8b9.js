import { jsx, jsxs } from "react/jsx-runtime";
import { usePage } from "@inertiajs/react";
import { B as Badge, I as Ikon } from "./AppLayout-C74XVGrz.js";
import { c as cn } from "../uji-render.js";
function StatusBadge({ status, className }) {
  const { statusMeta, statusLabels } = usePage().props;
  const meta = statusMeta[status];
  const label = statusLabels[status] ?? status;
  if (!meta) {
    return /* @__PURE__ */ jsx(Badge, { variant: "outline", className, children: label });
  }
  const [warna, warnaGelap, ikon] = meta;
  return /* @__PURE__ */ jsxs(
    "span",
    {
      className: cn(
        "inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap",
        className
      ),
      style: {
        color: warna,
        borderColor: `color-mix(in oklab, ${warna} 45%, transparent)`,
        // Ujung gelapnya jadi latar tipis — dua warna di STATUS_META
        // memang sepasang ramp, bukan warna cadangan.
        backgroundColor: `color-mix(in oklab, ${warnaGelap} 12%, transparent)`
      },
      children: [
        /* @__PURE__ */ jsx(Ikon, { nama: ikon, className: "size-3.5" }),
        label
      ]
    }
  );
}
export {
  StatusBadge as S
};
