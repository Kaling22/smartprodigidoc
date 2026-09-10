import { jsxs, jsx } from "react/jsx-runtime";
import { B as Badge, I as Ikon } from "./AppLayout-C74XVGrz.js";
import { c as cn } from "../uji-render.js";
function NomorDokumen({
  doc,
  dicoret = false
}) {
  return /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-1", children: [
    /* @__PURE__ */ jsx("span", { className: cn("font-mono text-sm", dicoret && "line-through"), children: doc.nomor }),
    doc.arsip ? /* @__PURE__ */ jsxs(Badge, { variant: "secondary", title: "Dokumen lama — PDF diunggah apa adanya", children: [
      /* @__PURE__ */ jsx(Ikon, { nama: "bi-archive", className: "size-3" }),
      "Arsip"
    ] }) : null,
    doc.nomor_luar_pola ? /* @__PURE__ */ jsx(Badge, { variant: "outline", title: "Nomor di luar pola resmi", children: "Nomor Lama" }) : null
  ] });
}
export {
  NomorDokumen as N
};
