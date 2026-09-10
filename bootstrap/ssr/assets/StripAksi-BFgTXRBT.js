import { jsxs, jsx } from "react/jsx-runtime";
import { MoreVerticalIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { Children } from "react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { p as DropdownMenu, q as DropdownMenuTrigger, r as DropdownMenuContent } from "./AppLayout-C74XVGrz.js";
function StripAksi({ label = "Aksi", children }) {
  if (Children.toArray(children).length === 0) {
    return null;
  }
  return /* @__PURE__ */ jsxs(DropdownMenu, { children: [
    /* @__PURE__ */ jsx(DropdownMenuTrigger, { asChild: true, children: /* @__PURE__ */ jsx(Button, { variant: "ghost", size: "icon", "aria-label": `${label} untuk baris ini`, children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: MoreVerticalIcon, strokeWidth: 1.5, className: "size-4" }) }) }),
    /* @__PURE__ */ jsx(DropdownMenuContent, { align: "end", className: "w-56", children })
  ] });
}
export {
  StripAksi as S
};
