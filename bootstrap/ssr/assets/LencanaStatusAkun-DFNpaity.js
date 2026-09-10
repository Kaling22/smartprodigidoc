import { jsx } from "react/jsx-runtime";
import { B as Badge } from "./AppLayout-C74XVGrz.js";
const VARIAN = {
  active: "default",
  pending: "secondary",
  rejected: "destructive"
};
function LencanaStatusAkun({ status }) {
  return /* @__PURE__ */ jsx(Badge, { variant: VARIAN[status] ?? "outline", className: "capitalize", children: status });
}
export {
  LencanaStatusAkun as L
};
