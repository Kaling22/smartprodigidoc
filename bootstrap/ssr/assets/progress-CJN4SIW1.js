import { jsx } from "react/jsx-runtime";
import { Progress as Progress$1 } from "radix-ui";
import { c as cn } from "../uji-render.js";
function Progress({
  className,
  value,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Progress$1.Root,
    {
      "data-slot": "progress",
      className: cn(
        "relative flex h-3 w-full items-center overflow-x-hidden rounded-4xl bg-muted",
        className
      ),
      ...props,
      children: /* @__PURE__ */ jsx(
        Progress$1.Indicator,
        {
          "data-slot": "progress-indicator",
          className: "size-full flex-1 bg-primary transition-all",
          style: { transform: `translateX(-${100 - (value || 0)}%)` }
        }
      )
    }
  );
}
export {
  Progress as P
};
