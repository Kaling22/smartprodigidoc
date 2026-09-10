import { jsxs, jsx } from "react/jsx-runtime";
import { P as Progress } from "./progress-CJN4SIW1.js";
import { c as cn } from "../uji-render.js";
function PitaCakupan({ c, ringkas = false }) {
  const persen = c.sasaran > 0 ? c.persen : 0;
  const rona = c.sasaran === 0 ? "[&>[data-slot=progress-indicator]]:bg-muted-foreground/40" : persen < 50 ? "[&>[data-slot=progress-indicator]]:bg-destructive" : persen < 80 ? "[&>[data-slot=progress-indicator]]:bg-chart-3" : "[&>[data-slot=progress-indicator]]:bg-chart-5";
  return /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
    /* @__PURE__ */ jsx(
      Progress,
      {
        value: Math.min(100, persen),
        className: cn("h-1.5 min-w-10 flex-1", rona),
        "aria-label": `Cakupan ${persen} persen, ${c.pembaca} dari ${c.sasaran} orang`
      }
    ),
    c.sasaran === 0 ? /* @__PURE__ */ jsx("span", { className: "text-muted-foreground shrink-0 text-xs whitespace-nowrap", children: "tak ada sasaran" }) : /* @__PURE__ */ jsxs("span", { className: "shrink-0 text-xs whitespace-nowrap tabular-nums", children: [
      /* @__PURE__ */ jsxs("strong", { children: [
        persen,
        "%"
      ] }),
      !ringkas && /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
        " ",
        "· ",
        c.pembaca,
        " dari ",
        c.sasaran,
        " orang"
      ] })
    ] })
  ] });
}
export {
  PitaCakupan as P
};
