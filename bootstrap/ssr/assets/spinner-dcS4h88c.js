import { jsx } from "react/jsx-runtime";
import { c as cn } from "../uji-render.js";
import { HugeiconsIcon } from "@hugeicons/react";
import { Loading03Icon } from "@hugeicons/core-free-icons";
function Spinner({ className, ...props }) {
  return /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Loading03Icon, strokeWidth: 2, "data-slot": "spinner", role: "status", "aria-label": "Loading", className: cn("size-4 animate-spin", className), ...props });
}
export {
  Spinner as S
};
