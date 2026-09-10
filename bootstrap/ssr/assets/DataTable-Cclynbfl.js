import { jsx, jsxs } from "react/jsx-runtime";
import { ArrowUp01Icon, ArrowDown01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { Link, usePage } from "@inertiajs/react";
import { Fragment } from "react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { c as cn } from "../uji-render.js";
import { T as Table, a as TableHeader, b as TableRow, c as TableHead, d as TableBody, e as TableCell } from "./table-COSAIfdh.js";
function Pagination({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "nav",
    {
      role: "navigation",
      "aria-label": "pagination",
      "data-slot": "pagination",
      className: cn("mx-auto flex w-full justify-center", className),
      ...props
    }
  );
}
function PaginationContent({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    "ul",
    {
      "data-slot": "pagination-content",
      className: cn("flex items-center gap-1", className),
      ...props
    }
  );
}
function PaginationItem({ ...props }) {
  return /* @__PURE__ */ jsx("li", { "data-slot": "pagination-item", ...props });
}
function DataTable({
  kolom,
  baris,
  kunci,
  kosong = "Belum ada data.",
  className,
  perluas
}) {
  return /* @__PURE__ */ jsx("div", { className: cn("overflow-x-auto", className), children: /* @__PURE__ */ jsxs(Table, { children: [
    /* @__PURE__ */ jsx(TableHeader, { className: "bg-card sticky top-0 z-10", children: /* @__PURE__ */ jsx(TableRow, { children: kolom.map((k, i) => /* @__PURE__ */ jsx(TableHead, { className: k.kelas, children: k.urut ? /* @__PURE__ */ jsx(JudulUrut, { kolom: k.urut, children: k.judul }) : k.judul }, i)) }) }),
    /* @__PURE__ */ jsx(TableBody, { children: baris.length === 0 ? /* @__PURE__ */ jsx(TableRow, { className: "hover:bg-transparent", children: /* @__PURE__ */ jsx(TableCell, { colSpan: kolom.length, className: "p-0", children: kosong }) }) : baris.map((b, i) => {
      const tambahan = perluas?.(b);
      return /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(TableRow, { className: "hover:bg-muted/40", children: kolom.map((k, j) => /* @__PURE__ */ jsx(TableCell, { className: k.kelas, children: k.render(b) }, j)) }),
        tambahan ? /* @__PURE__ */ jsx(TableRow, { children: /* @__PURE__ */ jsx(TableCell, { colSpan: kolom.length, className: "bg-muted/40 p-2", children: tambahan }) }) : null
      ] }, kunci(b, i));
    }) })
  ] }) });
}
function JudulUrut({ kolom, children }) {
  const url = usePage().url;
  const [jalur, kueri = ""] = url.split("?");
  const params = new URLSearchParams(kueri);
  const aktif = params.get("sort") === kolom;
  const menurun = aktif && params.get("dir") === "desc";
  const arahBerikut = aktif && !menurun ? "desc" : "asc";
  params.set("sort", kolom);
  params.set("dir", arahBerikut);
  params.delete("page");
  return /* @__PURE__ */ jsxs(
    Link,
    {
      href: `${jalur}?${params.toString()}`,
      preserveScroll: true,
      className: cn(
        "inline-flex items-center gap-1 hover:underline",
        aktif && "text-foreground font-semibold"
      ),
      title: `Urutkan ${arahBerikut === "asc" ? "menaik" : "menurun"}`,
      children: [
        children,
        /* @__PURE__ */ jsxs("span", { className: "flex flex-col leading-none", "aria-hidden": "true", children: [
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: ArrowUp01Icon,
              strokeWidth: 2,
              className: cn("-mb-1 size-3", aktif && !menurun ? "opacity-100" : "opacity-30")
            }
          ),
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: ArrowDown01Icon,
              strokeWidth: 2,
              className: cn("size-3", menurun ? "opacity-100" : "opacity-30")
            }
          )
        ] })
      ]
    }
  );
}
function labelHalaman(label) {
  return label.replaceAll("&laquo;", "«").replaceAll("&raquo;", "»");
}
function Paginasi({ paginator }) {
  if (paginator.links.length <= 3) {
    return null;
  }
  return /* @__PURE__ */ jsxs("div", { className: "flex w-full flex-wrap items-center justify-between gap-2", children: [
    /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground text-sm tabular-nums", children: [
      "Menampilkan ",
      paginator.from ?? 0,
      "–",
      paginator.to ?? 0,
      " dari ",
      paginator.total
    ] }),
    /* @__PURE__ */ jsx(Pagination, { className: "mx-0 w-auto justify-end", children: /* @__PURE__ */ jsx(PaginationContent, { children: paginator.links.map((l, i) => /* @__PURE__ */ jsx(PaginationItem, { children: l.url ? /* @__PURE__ */ jsx(
      Button,
      {
        asChild: true,
        size: "sm",
        variant: l.active ? "default" : "ghost",
        "aria-current": l.active ? "page" : void 0,
        children: /* @__PURE__ */ jsx(Link, { href: l.url, preserveScroll: true, children: labelHalaman(l.label) })
      }
    ) : /* @__PURE__ */ jsx(Button, { size: "sm", variant: "ghost", disabled: true, children: labelHalaman(l.label) }) }, i)) }) })
  ] });
}
export {
  DataTable as D,
  Paginasi as P
};
