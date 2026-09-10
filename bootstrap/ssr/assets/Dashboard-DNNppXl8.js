import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { usePage, Link, useForm, router } from "@inertiajs/react";
import * as React from "react";
import { useState } from "react";
import { a as Avatar, u as useIsMobile, i as ItemGroup, j as Item, k as ItemMedia, I as Ikon, l as ItemContent, m as ItemTitle, n as ItemDescription, D as Dialog, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, g as DialogFooter, B as Badge, S as Separator, A as AppLayout } from "./AppLayout-C74XVGrz.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { P as PenyaringDokumen, a as saringJenis, s as saringDepartemen, b as saringStatus } from "./PenyaringDokumen-ZbRxHXvI.js";
import { S as StatusBadge } from "./StatusBadge-BV-SY8b9.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent, e as CardFooter, f as CardAction } from "./card-B3VJCD2M.js";
import { P as Progress } from "./progress-CJN4SIW1.js";
import * as RechartsPrimitive from "recharts";
import { AreaChart, CartesianGrid, XAxis, Area, PieChart, Pie, Label as Label$1, Sector, RadialBarChart, PolarGrid, RadialBar, PolarRadiusAxis, BarChart, YAxis, Bar } from "recharts";
import { c as cn } from "../uji-render.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { ToggleGroup as ToggleGroup$1, ScrollArea as ScrollArea$1, Tabs as Tabs$1, HoverCard as HoverCard$1, RadioGroup as RadioGroup$1 } from "radix-ui";
import { cva } from "class-variance-authority";
import "clsx";
import { ArrowRight01Icon, ArrowLeftIcon, ArrowRightIcon, ArrowDownIcon, ArrowLeft01Icon, UserCheck01Icon, CalendarAdd01Icon, InformationCircleIcon, ArrowUpRight01Icon, ArrowDownRight01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { B as Button, b as buttonVariants } from "./button-DLS2B9Gu.js";
import { P as PitaCakupan } from "./PitaCakupan-D6HiIzDA.js";
import { id } from "date-fns/locale";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { getDefaultClassNames, DayPicker } from "react-day-picker";
import { I as Input } from "./input-B9Vuz8J-.js";
import { L as Label } from "./label-emiz6fAI.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription } from "./empty-CkAl4IHy.js";
import { T as Table, a as TableHeader, b as TableRow, c as TableHead, d as TableBody, e as TableCell } from "./table-COSAIfdh.js";
import "sonner";
import "next-themes";
import "./input-group-C5LPhh_3.js";
import "react-dom/server";
import "tailwind-merge";
function AktivitasTerbaru({
  tabel,
  filters,
  departemen,
  statusOpsi,
  jenisList
}) {
  const { statusLabels } = usePage().props;
  const kolom = [
    {
      judul: "Dokumen",
      render: (b) => /* @__PURE__ */ jsxs(Link, { href: b.tautan, className: "hover:underline", children: [
        /* @__PURE__ */ jsx("div", { className: "font-medium", children: b.judul }),
        /* @__PURE__ */ jsx("div", { className: "text-muted-foreground text-xs", children: b.nomor })
      ] })
    },
    {
      judul: "Pembuat",
      render: (b) => /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(Avatar, { nama: b.pembuat.nama, foto: b.pembuat.foto, className: "size-7" }),
        /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
          /* @__PURE__ */ jsx("div", { className: "truncate text-sm", children: b.pembuat.nama }),
          /* @__PURE__ */ jsx("div", { className: "text-muted-foreground truncate text-xs", children: b.pembuat.jabatan })
        ] })
      ] })
    },
    {
      judul: "Dibuat",
      render: (b) => /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: b.dibuat ?? "—" })
    },
    {
      judul: "Diperbarui",
      render: (b) => /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: b.diperbarui ?? "—" })
    },
    {
      judul: "Status",
      render: (b) => /* @__PURE__ */ jsx(StatusBadge, { status: b.status })
    },
    {
      judul: "Kemajuan",
      kelas: "w-40",
      render: (b) => /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(Progress, { value: b.persen, className: "h-1.5" }),
        /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground w-9 shrink-0 text-right text-xs tabular-nums", children: [
          b.persen,
          "%"
        ] })
      ] })
    }
  ];
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Aktivitas Terbaru" }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Dokumen yang belakangan bergerak, lintas tujuh departemen" }),
      /* @__PURE__ */ jsx("div", { className: "mt-3", children: /* @__PURE__ */ jsx(
        PenyaringDokumen,
        {
          url: route("dashboard"),
          filters,
          contoh: "PPA-ADRO-SOP",
          pilihan: [
            saringJenis(jenisList),
            ...departemen.length > 0 ? [saringDepartemen(departemen)] : [],
            saringStatus(statusOpsi, statusLabels)
          ]
        }
      ) })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
      DataTable,
      {
        kolom,
        baris: tabel.data,
        kunci: (b) => b.id,
        kosong: "Belum ada aktivitas dokumen."
      }
    ) }),
    /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: tabel }) })
  ] });
}
const THEMES = { light: "", dark: ".dark" };
const INITIAL_DIMENSION = { width: 320, height: 200 };
const ChartContext = React.createContext(null);
function useChart() {
  const context = React.useContext(ChartContext);
  if (!context) {
    throw new Error("useChart must be used within a <ChartContainer />");
  }
  return context;
}
function ChartContainer({
  id: id2,
  className,
  children,
  config,
  initialDimension = INITIAL_DIMENSION,
  ...props
}) {
  const uniqueId = React.useId();
  const chartId = `chart-${id2 ?? uniqueId.replace(/:/g, "")}`;
  return /* @__PURE__ */ jsx(ChartContext.Provider, { value: { config }, children: /* @__PURE__ */ jsxs(
    "div",
    {
      "data-slot": "chart",
      "data-chart": chartId,
      className: cn(
        "flex aspect-video justify-center text-xs [&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground [&_.recharts-cartesian-grid_line[stroke='#ccc']]:stroke-border/50 [&_.recharts-curve.recharts-tooltip-cursor]:stroke-border [&_.recharts-dot[stroke='#fff']]:stroke-transparent [&_.recharts-layer]:outline-hidden [&_.recharts-polar-grid_[stroke='#ccc']]:stroke-border [&_.recharts-radial-bar-background-sector]:fill-muted [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted [&_.recharts-reference-line_[stroke='#ccc']]:stroke-border [&_.recharts-sector]:outline-hidden [&_.recharts-sector[stroke='#fff']]:stroke-transparent [&_.recharts-surface]:outline-hidden",
        className
      ),
      ...props,
      children: [
        /* @__PURE__ */ jsx(ChartStyle, { id: chartId, config }),
        /* @__PURE__ */ jsx(
          RechartsPrimitive.ResponsiveContainer,
          {
            initialDimension,
            children
          }
        )
      ]
    }
  ) });
}
const ChartStyle = ({ id: id2, config }) => {
  const colorConfig = Object.entries(config).filter(
    ([, config2]) => config2.theme ?? config2.color
  );
  if (!colorConfig.length) {
    return null;
  }
  return /* @__PURE__ */ jsx(
    "style",
    {
      dangerouslySetInnerHTML: {
        __html: Object.entries(THEMES).map(
          ([theme, prefix]) => `
${prefix} [data-chart=${id2}] {
${colorConfig.map(([key, itemConfig]) => {
            const color = itemConfig.theme?.[theme] ?? itemConfig.color;
            return color ? `  --color-${key}: ${color};` : null;
          }).join("\n")}
}
`
        ).join("\n")
      }
    }
  );
};
const ChartTooltip = RechartsPrimitive.Tooltip;
function ChartTooltipContent({
  active,
  payload,
  className,
  indicator = "dot",
  hideLabel = false,
  hideIndicator = false,
  label,
  labelFormatter,
  labelClassName,
  formatter,
  color,
  nameKey,
  labelKey
}) {
  const { config } = useChart();
  const tooltipLabel = React.useMemo(() => {
    if (hideLabel || !payload?.length) {
      return null;
    }
    const [item] = payload;
    const key = `${labelKey ?? item?.dataKey ?? item?.name ?? "value"}`;
    const itemConfig = getPayloadConfigFromPayload(config, item, key);
    const value = !labelKey && typeof label === "string" ? config[label]?.label ?? label : itemConfig?.label;
    if (labelFormatter) {
      return /* @__PURE__ */ jsx("div", { className: cn("font-medium", labelClassName), children: labelFormatter(value, payload) });
    }
    if (!value) {
      return null;
    }
    return /* @__PURE__ */ jsx("div", { className: cn("font-medium", labelClassName), children: value });
  }, [
    label,
    labelFormatter,
    payload,
    hideLabel,
    labelClassName,
    config,
    labelKey
  ]);
  if (!active || !payload?.length) {
    return null;
  }
  const nestLabel = payload.length === 1 && indicator !== "dot";
  return /* @__PURE__ */ jsxs(
    "div",
    {
      className: cn(
        "grid min-w-32 items-start gap-1.5 rounded-lg border border-border/50 bg-background px-2.5 py-1.5 text-xs shadow-xl",
        className
      ),
      children: [
        !nestLabel ? tooltipLabel : null,
        /* @__PURE__ */ jsx("div", { className: "grid gap-1.5", children: payload.filter((item) => item.type !== "none").map((item, index) => {
          const key = `${nameKey ?? item.name ?? item.dataKey ?? "value"}`;
          const itemConfig = getPayloadConfigFromPayload(config, item, key);
          const indicatorColor = color ?? item.payload?.fill ?? item.color;
          return /* @__PURE__ */ jsx(
            "div",
            {
              className: cn(
                "flex w-full flex-wrap items-stretch gap-2 [&>svg]:h-2.5 [&>svg]:w-2.5 [&>svg]:text-muted-foreground",
                indicator === "dot" && "items-center"
              ),
              children: formatter && item?.value !== void 0 && item.name ? formatter(item.value, item.name, item, index, item.payload) : /* @__PURE__ */ jsxs(Fragment, { children: [
                itemConfig?.icon ? /* @__PURE__ */ jsx(itemConfig.icon, {}) : !hideIndicator && /* @__PURE__ */ jsx(
                  "div",
                  {
                    className: cn(
                      "shrink-0 rounded-[2px] border-(--color-border) bg-(--color-bg)",
                      {
                        "h-2.5 w-2.5": indicator === "dot",
                        "w-1": indicator === "line",
                        "w-0 border-[1.5px] border-dashed bg-transparent": indicator === "dashed",
                        "my-0.5": nestLabel && indicator === "dashed"
                      }
                    ),
                    style: {
                      "--color-bg": indicatorColor,
                      "--color-border": indicatorColor
                    }
                  }
                ),
                /* @__PURE__ */ jsxs(
                  "div",
                  {
                    className: cn(
                      "flex flex-1 justify-between leading-none",
                      nestLabel ? "items-end" : "items-center"
                    ),
                    children: [
                      /* @__PURE__ */ jsxs("div", { className: "grid gap-1.5", children: [
                        nestLabel ? tooltipLabel : null,
                        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: itemConfig?.label ?? item.name })
                      ] }),
                      item.value != null && /* @__PURE__ */ jsx("span", { className: "font-mono font-medium text-foreground tabular-nums", children: typeof item.value === "number" ? item.value.toLocaleString() : String(item.value) })
                    ]
                  }
                )
              ] })
            },
            index
          );
        }) })
      ]
    }
  );
}
function getPayloadConfigFromPayload(config, payload, key) {
  if (typeof payload !== "object" || payload === null) {
    return void 0;
  }
  const payloadPayload = "payload" in payload && typeof payload.payload === "object" && payload.payload !== null ? payload.payload : void 0;
  let configLabelKey = key;
  if (key in payload && typeof payload[key] === "string") {
    configLabelKey = payload[key];
  } else if (payloadPayload && key in payloadPayload && typeof payloadPayload[key] === "string") {
    configLabelKey = payloadPayload[key];
  }
  return configLabelKey in config ? config[configLabelKey] : config[key];
}
const toggleVariants = cva(
  "group/toggle inline-flex items-center justify-center gap-1 rounded-4xl text-sm font-medium whitespace-nowrap transition-colors outline-none hover:bg-muted hover:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 aria-pressed:bg-muted dark:aria-invalid:ring-destructive/40 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        default: "bg-transparent",
        outline: "border border-input bg-transparent hover:bg-muted"
      },
      size: {
        default: "h-9 min-w-9 px-3 has-data-[icon=inline-end]:pr-2.5 has-data-[icon=inline-start]:pl-2.5",
        sm: "h-8 min-w-8 px-3 has-data-[icon=inline-end]:pr-2 has-data-[icon=inline-start]:pl-2",
        lg: "h-10 min-w-10 px-4 has-data-[icon=inline-end]:pr-3 has-data-[icon=inline-start]:pl-3"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);
const ToggleGroupContext = React.createContext({
  size: "default",
  variant: "default",
  spacing: 2,
  orientation: "horizontal"
});
function ToggleGroup({
  className,
  variant,
  size,
  spacing = 2,
  orientation = "horizontal",
  children,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    ToggleGroup$1.Root,
    {
      "data-slot": "toggle-group",
      "data-variant": variant,
      "data-size": size,
      "data-spacing": spacing,
      "data-orientation": orientation,
      style: { "--gap": spacing },
      className: cn(
        "group/toggle-group flex w-fit flex-row items-center gap-[--spacing(var(--gap))] data-[spacing=0]:data-[variant=outline]:rounded-4xl data-vertical:flex-col data-vertical:items-stretch",
        className
      ),
      ...props,
      children: /* @__PURE__ */ jsx(
        ToggleGroupContext.Provider,
        {
          value: { variant, size, spacing, orientation },
          children
        }
      )
    }
  );
}
function ToggleGroupItem({
  className,
  children,
  variant = "default",
  size = "default",
  ...props
}) {
  const context = React.useContext(ToggleGroupContext);
  return /* @__PURE__ */ jsx(
    ToggleGroup$1.Item,
    {
      "data-slot": "toggle-group-item",
      "data-variant": context.variant || variant,
      "data-size": context.size || size,
      "data-spacing": context.spacing,
      className: cn(
        "shrink-0 group-data-[spacing=0]/toggle-group:rounded-none group-data-[spacing=0]/toggle-group:px-3 group-data-[spacing=0]/toggle-group:shadow-none focus:z-10 focus-visible:z-10 group-data-[spacing=0]/toggle-group:has-data-[icon=inline-end]:pr-2.5 group-data-[spacing=0]/toggle-group:has-data-[icon=inline-start]:pl-2.5 group-data-horizontal/toggle-group:data-[spacing=0]:first:rounded-l-3xl group-data-vertical/toggle-group:data-[spacing=0]:first:rounded-t-3xl group-data-horizontal/toggle-group:data-[spacing=0]:last:rounded-r-3xl group-data-vertical/toggle-group:data-[spacing=0]:last:rounded-b-3xl data-[state=on]:bg-muted group-data-horizontal/toggle-group:data-[spacing=0]:data-[variant=outline]:border-l-0 group-data-vertical/toggle-group:data-[spacing=0]:data-[variant=outline]:border-t-0 group-data-horizontal/toggle-group:data-[spacing=0]:data-[variant=outline]:first:border-l group-data-vertical/toggle-group:data-[spacing=0]:data-[variant=outline]:first:border-t",
        toggleVariants({
          variant: context.variant || variant,
          size: context.size || size
        }),
        className
      ),
      ...props,
      children
    }
  );
}
const konfigurasi = {
  dibuat: { label: "Dibuat", color: "var(--chart-1)" },
  berlaku: { label: "Berlaku", color: "var(--chart-2)" }
};
const NAMA_DURASI = {
  hari: "Harian",
  minggu: "Mingguan",
  bulan: "Bulanan"
};
function GrafikOverview({
  deret,
  durasi,
  onDurasi
}) {
  const isMobile = useIsMobile();
  const data = deret.labels.map((label, i) => ({
    label,
    dibuat: deret.dibuat[i] ?? 0,
    berlaku: deret.berlaku[i] ?? 0
  }));
  const rasio = deret.total > 0 ? Math.round(deret.berlakuTotal / deret.total * 100) : null;
  const pilih = (nilai) => {
    if (nilai) onDurasi(nilai);
  };
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Overview Dokumen" }),
      /* @__PURE__ */ jsxs(CardDescription, { children: [
        /* @__PURE__ */ jsxs("span", { className: "font-semibold text-foreground", children: [
          deret.berlakuTotal,
          " dari ",
          deret.total
        ] }),
        " ",
        "dokumen yang dibuat sudah disahkan",
        rasio !== null ? ` (${rasio}%)` : "",
        " dalam ",
        deret.rentang
      ] }),
      /* @__PURE__ */ jsxs(CardAction, { children: [
        /* @__PURE__ */ jsxs(
          ToggleGroup,
          {
            type: "single",
            value: durasi,
            onValueChange: pilih,
            variant: "outline",
            className: "hidden *:data-[slot=toggle-group-item]:px-4! @[767px]/card:flex",
            children: [
              /* @__PURE__ */ jsx(ToggleGroupItem, { value: "hari", children: "Harian" }),
              /* @__PURE__ */ jsx(ToggleGroupItem, { value: "minggu", children: "Mingguan" }),
              /* @__PURE__ */ jsx(ToggleGroupItem, { value: "bulan", children: "Bulanan" })
            ]
          }
        ),
        /* @__PURE__ */ jsxs(Select, { value: durasi, onValueChange: pilih, children: [
          /* @__PURE__ */ jsx(
            SelectTrigger,
            {
              className: "flex w-32 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate @[767px]/card:hidden",
              size: "sm",
              "aria-label": "Pilih durasi",
              children: /* @__PURE__ */ jsx(SelectValue, { placeholder: NAMA_DURASI[durasi] })
            }
          ),
          /* @__PURE__ */ jsx(SelectContent, { className: "rounded-xl", children: Object.keys(NAMA_DURASI).map((k) => /* @__PURE__ */ jsx(SelectItem, { value: k, className: "rounded-lg", children: NAMA_DURASI[k] }, k)) })
        ] })
      ] })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "flex flex-1 flex-col px-2 pt-4 sm:px-6 sm:pt-6", children: /* @__PURE__ */ jsx(ChartContainer, { config: konfigurasi, className: "aspect-auto min-h-[200px] w-full flex-1", children: /* @__PURE__ */ jsxs(AreaChart, { data, children: [
      /* @__PURE__ */ jsxs("defs", { children: [
        /* @__PURE__ */ jsxs("linearGradient", { id: "isiDibuat", x1: "0", y1: "0", x2: "0", y2: "1", children: [
          /* @__PURE__ */ jsx("stop", { offset: "5%", stopColor: "var(--color-dibuat)", stopOpacity: 1 }),
          /* @__PURE__ */ jsx("stop", { offset: "95%", stopColor: "var(--color-dibuat)", stopOpacity: 0.1 })
        ] }),
        /* @__PURE__ */ jsxs("linearGradient", { id: "isiBerlaku", x1: "0", y1: "0", x2: "0", y2: "1", children: [
          /* @__PURE__ */ jsx("stop", { offset: "5%", stopColor: "var(--color-berlaku)", stopOpacity: 0.8 }),
          /* @__PURE__ */ jsx("stop", { offset: "95%", stopColor: "var(--color-berlaku)", stopOpacity: 0.1 })
        ] })
      ] }),
      /* @__PURE__ */ jsx(CartesianGrid, { vertical: false }),
      /* @__PURE__ */ jsx(
        XAxis,
        {
          dataKey: "label",
          tickLine: false,
          axisLine: false,
          tickMargin: 8,
          minTickGap: isMobile ? 24 : 32
        }
      ),
      /* @__PURE__ */ jsx(ChartTooltip, { cursor: false, content: /* @__PURE__ */ jsx(ChartTooltipContent, { indicator: "dot" }) }),
      /* @__PURE__ */ jsx(
        Area,
        {
          dataKey: "berlaku",
          type: "natural",
          fill: "url(#isiBerlaku)",
          stroke: "var(--color-berlaku)"
        }
      ),
      /* @__PURE__ */ jsx(
        Area,
        {
          dataKey: "dibuat",
          type: "natural",
          fill: "url(#isiDibuat)",
          stroke: "var(--color-dibuat)"
        }
      )
    ] }) }) })
  ] });
}
function ScrollArea({
  className,
  children,
  ...props
}) {
  return /* @__PURE__ */ jsxs(
    ScrollArea$1.Root,
    {
      "data-slot": "scroll-area",
      className: cn("relative", className),
      ...props,
      children: [
        /* @__PURE__ */ jsx(
          ScrollArea$1.Viewport,
          {
            "data-slot": "scroll-area-viewport",
            className: "size-full rounded-[inherit] transition-[color,box-shadow] outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-1",
            children
          }
        ),
        /* @__PURE__ */ jsx(ScrollBar, {}),
        /* @__PURE__ */ jsx(ScrollArea$1.Corner, {})
      ]
    }
  );
}
function ScrollBar({
  className,
  orientation = "vertical",
  ...props
}) {
  return /* @__PURE__ */ jsx(
    ScrollArea$1.ScrollAreaScrollbar,
    {
      "data-slot": "scroll-area-scrollbar",
      "data-orientation": orientation,
      orientation,
      className: cn(
        "flex touch-none p-px transition-colors select-none data-horizontal:h-2.5 data-horizontal:flex-col data-horizontal:border-t data-horizontal:border-t-transparent data-vertical:h-full data-vertical:w-2.5 data-vertical:border-l data-vertical:border-l-transparent",
        className
      ),
      ...props,
      children: /* @__PURE__ */ jsx(
        ScrollArea$1.ScrollAreaThumb,
        {
          "data-slot": "scroll-area-thumb",
          className: "relative flex-1 rounded-full bg-border"
        }
      )
    }
  );
}
function Tabs({
  className,
  orientation = "horizontal",
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Tabs$1.Root,
    {
      "data-slot": "tabs",
      "data-orientation": orientation,
      className: cn(
        "group/tabs flex gap-2 data-horizontal:flex-col",
        className
      ),
      ...props
    }
  );
}
const tabsListVariants = cva(
  "group/tabs-list inline-flex w-fit items-center justify-center rounded-4xl p-[3px] text-muted-foreground group-data-horizontal/tabs:h-9 group-data-vertical/tabs:h-fit group-data-vertical/tabs:flex-col group-data-vertical/tabs:rounded-2xl data-[variant=line]:rounded-none",
  {
    variants: {
      variant: {
        default: "bg-muted",
        line: "gap-1 bg-transparent"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);
function TabsList({
  className,
  variant = "default",
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Tabs$1.List,
    {
      "data-slot": "tabs-list",
      "data-variant": variant,
      className: cn(tabsListVariants({ variant }), className),
      ...props
    }
  );
}
function TabsTrigger({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Tabs$1.Trigger,
    {
      "data-slot": "tabs-trigger",
      className: cn(
        "relative inline-flex h-[calc(100%-1px)] flex-1 items-center justify-center gap-1.5 rounded-xl border border-transparent px-2 py-1 text-sm font-medium whitespace-nowrap text-foreground/60 transition-all group-data-vertical/tabs:w-full group-data-vertical/tabs:justify-start group-data-vertical/tabs:px-2.5 group-data-vertical/tabs:py-1.5 hover:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-1 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50 has-data-[icon=inline-end]:pr-1.5 has-data-[icon=inline-start]:pl-1.5 dark:text-muted-foreground dark:hover:text-foreground [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
        "group-data-[variant=line]/tabs-list:bg-transparent group-data-[variant=line]/tabs-list:data-active:bg-transparent dark:group-data-[variant=line]/tabs-list:data-active:border-transparent dark:group-data-[variant=line]/tabs-list:data-active:bg-transparent",
        "data-active:bg-background data-active:text-foreground dark:data-active:border-input dark:data-active:bg-input/30 dark:data-active:text-foreground",
        "after:absolute after:bg-foreground after:opacity-0 after:transition-opacity group-data-horizontal/tabs:after:inset-x-0 group-data-horizontal/tabs:after:bottom-[-5px] group-data-horizontal/tabs:after:h-0.5 group-data-vertical/tabs:after:inset-y-0 group-data-vertical/tabs:after:-right-1 group-data-vertical/tabs:after:w-0.5 group-data-[variant=line]/tabs-list:data-active:after:opacity-100",
        className
      ),
      ...props
    }
  );
}
function TabsContent({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Tabs$1.Content,
    {
      "data-slot": "tabs-content",
      className: cn("flex-1 text-sm outline-none", className),
      ...props
    }
  );
}
function HoverCard({
  ...props
}) {
  return /* @__PURE__ */ jsx(HoverCard$1.Root, { "data-slot": "hover-card", ...props });
}
function HoverCardTrigger({
  ...props
}) {
  return /* @__PURE__ */ jsx(HoverCard$1.Trigger, { "data-slot": "hover-card-trigger", ...props });
}
function HoverCardContent({
  className,
  align = "center",
  sideOffset = 4,
  ...props
}) {
  return /* @__PURE__ */ jsx(HoverCard$1.Portal, { "data-slot": "hover-card-portal", children: /* @__PURE__ */ jsx(
    HoverCard$1.Content,
    {
      "data-slot": "hover-card-content",
      align,
      sideOffset,
      className: cn(
        "z-50 w-72 origin-(--radix-hover-card-content-transform-origin) rounded-2xl bg-popover p-4 text-sm text-popover-foreground shadow-2xl ring-1 ring-foreground/5 outline-hidden duration-100 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95",
        className
      ),
      ...props
    }
  ) });
}
function TumpukanWajah({
  pembaca,
  satuan = "pembaca"
}) {
  if (pembaca.length === 0) {
    return /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs", children: "belum ada" });
  }
  const sisa = pembaca.length - 4;
  return /* @__PURE__ */ jsxs(HoverCard, { openDelay: 120, children: [
    /* @__PURE__ */ jsx(HoverCardTrigger, { asChild: true, children: /* @__PURE__ */ jsxs("div", { className: "flex w-fit items-center", tabIndex: 0, children: [
      pembaca.slice(0, 4).map((p, i) => /* @__PURE__ */ jsx(
        Avatar,
        {
          nama: p.nama,
          foto: p.foto,
          className: "ring-card -ml-2 size-7 ring-2 first:ml-0"
        },
        `${p.nama}-${i}`
      )),
      sisa > 0 && /* @__PURE__ */ jsxs("span", { className: "bg-muted ring-card -ml-2 flex size-7 items-center justify-center rounded-full text-[0.625rem] font-semibold ring-2", children: [
        "+",
        sisa
      ] })
    ] }) }),
    /* @__PURE__ */ jsxs(HoverCardContent, { align: "start", className: "w-56", children: [
      /* @__PURE__ */ jsxs("p", { className: "mb-2 text-xs font-semibold", children: [
        pembaca.length,
        " ",
        satuan
      ] }),
      /* @__PURE__ */ jsx("ul", { className: "space-y-1.5", children: pembaca.map((p, i) => /* @__PURE__ */ jsxs("li", { className: "flex items-center gap-2 text-sm", children: [
        /* @__PURE__ */ jsx(Avatar, { nama: p.nama, foto: p.foto, className: "size-6" }),
        /* @__PURE__ */ jsx("span", { className: "truncate", children: p.nama }),
        p.ket ? /* @__PURE__ */ jsx("span", { className: "text-muted-foreground ml-auto shrink-0 text-xs tabular-nums", children: p.ket }) : null
      ] }, `${p.nama}-${i}`)) })
    ] })
  ] });
}
function KartuDistribusi({ widget }) {
  const tersedia = ["mutu", "informasi"].filter((k) => widget[k]);
  const [sumber, setSumber] = useState(tersedia[0] ?? "mutu");
  const panel = widget[sumber];
  const kata = sumber === "mutu" ? "dokumen" : "informasi";
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full min-h-[20rem] flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Distribusi & Keterbacaan" }),
      /* @__PURE__ */ jsx(CardDescription, { children: panel && panel.rendah > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsxs("span", { className: "text-foreground font-semibold", children: [
          panel.rendah,
          " ",
          kata
        ] }),
        " ",
        "belum terbaca separuh sasarannya"
      ] }) : "Semua sudah terbaca lebih dari separuh sasarannya" }),
      /* @__PURE__ */ jsxs(CardAction, { className: "flex items-center gap-2", children: [
        tersedia.length > 1 && /* @__PURE__ */ jsx(Tabs, { value: sumber, onValueChange: (v) => setSumber(v), children: /* @__PURE__ */ jsxs(TabsList, { children: [
          /* @__PURE__ */ jsx(TabsTrigger, { value: "mutu", children: "Dokumen Mutu" }),
          /* @__PURE__ */ jsx(TabsTrigger, { value: "informasi", children: "Informasi" })
        ] }) }),
        widget.bolehBukaHalaman && /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: sumber === "informasi" ? widget.urlInformasi : widget.urlMutu, children: [
          "Lihat semua",
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5, className: "size-4" })
        ] }) })
      ] })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "relative min-h-0 flex-1 px-0", children: /* @__PURE__ */ jsx("div", { className: "absolute inset-0", children: /* @__PURE__ */ jsx(ScrollArea, { className: "h-full px-(--card-spacing)", children: /* @__PURE__ */ jsx(ItemGroup, { className: "gap-2 pr-3", children: (panel?.baris ?? []).map((b) => /* @__PURE__ */ jsxs(Item, { variant: "outline", className: "flex-wrap items-start", children: [
      /* @__PURE__ */ jsx(ItemMedia, { children: /* @__PURE__ */ jsx(
        "span",
        {
          className: "flex size-9 shrink-0 items-center justify-center rounded-xl",
          style: {
            backgroundColor: `color-mix(in oklab, ${b.warna} 15%, transparent)`,
            color: b.warna
          },
          title: b.gelar,
          children: /* @__PURE__ */ jsx(Ikon, { nama: b.ikon, className: "size-4" })
        }
      ) }),
      /* @__PURE__ */ jsxs(ItemContent, { className: "min-w-0 gap-1", children: [
        /* @__PURE__ */ jsx(ItemTitle, { children: /* @__PURE__ */ jsx(Link, { href: b.tautan, className: "hover:text-primary truncate", children: b.judul }) }),
        /* @__PURE__ */ jsx(ItemDescription, { children: b.sub }),
        /* @__PURE__ */ jsxs("div", { className: "mt-1 flex items-center gap-3", children: [
          /* @__PURE__ */ jsx("div", { className: "min-w-32 flex-1", children: /* @__PURE__ */ jsx(PitaCakupan, { c: b.c, ringkas: true }) }),
          /* @__PURE__ */ jsxs(
            "span",
            {
              className: "text-muted-foreground shrink-0 text-xs tabular-nums",
              title: "Berapa kali PDF-nya diunduh",
              children: [
                b.c.unduhan,
                " unduhan"
              ]
            }
          ),
          /* @__PURE__ */ jsx(TumpukanWajah, { pembaca: b.pembaca })
        ] })
      ] })
    ] }, b.tautan)) }) }) }) })
  ] });
}
function Calendar({
  className,
  classNames,
  showOutsideDays = true,
  captionLayout = "label",
  buttonVariant = "ghost",
  locale,
  formatters,
  components,
  ...props
}) {
  const defaultClassNames = getDefaultClassNames();
  return /* @__PURE__ */ jsx(
    DayPicker,
    {
      showOutsideDays,
      className: cn(
        "group/calendar bg-background p-3 [--cell-radius:var(--radius-4xl)] [--cell-size:--spacing(8)] in-data-[slot=card-content]:bg-transparent in-data-[slot=popover-content]:bg-transparent",
        String.raw`rtl:**:[.rdp-button\_next>svg]:rotate-180`,
        String.raw`rtl:**:[.rdp-button\_previous>svg]:rotate-180`,
        className
      ),
      captionLayout,
      locale,
      formatters: {
        formatMonthDropdown: (date) => date.toLocaleString(locale?.code, { month: "short" }),
        ...formatters
      },
      classNames: {
        root: cn("w-fit", defaultClassNames.root),
        months: cn(
          "relative flex flex-col gap-4 md:flex-row",
          defaultClassNames.months
        ),
        month: cn("flex w-full flex-col gap-4", defaultClassNames.month),
        nav: cn(
          "absolute inset-x-0 top-0 flex w-full items-center justify-between gap-1",
          defaultClassNames.nav
        ),
        button_previous: cn(
          buttonVariants({ variant: buttonVariant }),
          "size-(--cell-size) p-0 select-none aria-disabled:opacity-50",
          defaultClassNames.button_previous
        ),
        button_next: cn(
          buttonVariants({ variant: buttonVariant }),
          "size-(--cell-size) p-0 select-none aria-disabled:opacity-50",
          defaultClassNames.button_next
        ),
        month_caption: cn(
          "flex h-(--cell-size) w-full items-center justify-center px-(--cell-size)",
          defaultClassNames.month_caption
        ),
        dropdowns: cn(
          "flex h-(--cell-size) w-full items-center justify-center gap-1.5 text-sm font-medium",
          defaultClassNames.dropdowns
        ),
        dropdown_root: cn(
          "relative rounded-(--cell-radius)",
          defaultClassNames.dropdown_root
        ),
        dropdown: cn(
          "absolute inset-0 bg-popover opacity-0",
          defaultClassNames.dropdown
        ),
        caption_label: cn(
          "font-medium select-none",
          captionLayout === "label" ? "text-sm" : "flex items-center gap-1 rounded-(--cell-radius) text-sm [&>svg]:size-3.5 [&>svg]:text-muted-foreground",
          defaultClassNames.caption_label
        ),
        month_grid: cn("w-full border-collapse", defaultClassNames.month_grid),
        weekdays: cn("flex", defaultClassNames.weekdays),
        weekday: cn(
          "flex-1 rounded-(--cell-radius) text-[0.8rem] font-normal text-muted-foreground select-none",
          defaultClassNames.weekday
        ),
        week: cn("mt-2 flex w-full", defaultClassNames.week),
        week_number_header: cn(
          "w-(--cell-size) select-none",
          defaultClassNames.week_number_header
        ),
        week_number: cn(
          "text-[0.8rem] text-muted-foreground select-none",
          defaultClassNames.week_number
        ),
        day: cn(
          "group/day relative aspect-square h-full w-full rounded-(--cell-radius) p-0 text-center select-none [&:last-child[data-selected=true]_button]:rounded-r-(--cell-radius)",
          props.showWeekNumber ? "[&:nth-child(2)[data-selected=true]_button]:rounded-l-(--cell-radius)" : "[&:first-child[data-selected=true]_button]:rounded-l-(--cell-radius)",
          defaultClassNames.day
        ),
        range_start: cn(
          "relative isolate z-0 rounded-l-(--cell-radius) bg-muted after:absolute after:inset-y-0 after:right-0 after:w-4 after:bg-muted",
          defaultClassNames.range_start
        ),
        range_middle: cn("rounded-none", defaultClassNames.range_middle),
        range_end: cn(
          "relative isolate z-0 rounded-r-(--cell-radius) bg-muted after:absolute after:inset-y-0 after:left-0 after:w-4 after:bg-muted",
          defaultClassNames.range_end
        ),
        today: cn(
          "rounded-(--cell-radius) bg-muted text-foreground data-[selected=true]:rounded-none",
          defaultClassNames.today
        ),
        outside: cn(
          "text-muted-foreground aria-selected:text-muted-foreground",
          defaultClassNames.outside
        ),
        disabled: cn(
          "text-muted-foreground opacity-50",
          defaultClassNames.disabled
        ),
        hidden: cn("invisible", defaultClassNames.hidden),
        ...classNames
      },
      components: {
        Root: ({ className: className2, rootRef, ...props2 }) => {
          return /* @__PURE__ */ jsx(
            "div",
            {
              "data-slot": "calendar",
              ref: rootRef,
              className: cn(className2),
              ...props2
            }
          );
        },
        Chevron: ({ className: className2, orientation, ...props2 }) => {
          if (orientation === "left") {
            return /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeftIcon, strokeWidth: 2, className: cn("size-4", className2), ...props2 });
          }
          if (orientation === "right") {
            return /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRightIcon, strokeWidth: 2, className: cn("size-4", className2), ...props2 });
          }
          return /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowDownIcon, strokeWidth: 2, className: cn("size-4", className2), ...props2 });
        },
        DayButton: ({ ...props2 }) => /* @__PURE__ */ jsx(CalendarDayButton, { locale, ...props2 }),
        WeekNumber: ({ children, ...props2 }) => {
          return /* @__PURE__ */ jsx("td", { ...props2, children: /* @__PURE__ */ jsx("div", { className: "flex size-(--cell-size) items-center justify-center text-center", children }) });
        },
        ...components
      },
      ...props
    }
  );
}
function CalendarDayButton({
  className,
  day,
  modifiers,
  locale,
  ...props
}) {
  const defaultClassNames = getDefaultClassNames();
  const ref = React.useRef(null);
  React.useEffect(() => {
    if (modifiers.focused) ref.current?.focus();
  }, [modifiers.focused]);
  return /* @__PURE__ */ jsx(
    Button,
    {
      ref,
      variant: "ghost",
      size: "icon",
      "data-day": day.date.toLocaleDateString(locale?.code),
      "data-selected-single": modifiers.selected && !modifiers.range_start && !modifiers.range_end && !modifiers.range_middle,
      "data-range-start": modifiers.range_start,
      "data-range-end": modifiers.range_end,
      "data-range-middle": modifiers.range_middle,
      className: cn(
        "relative isolate z-10 flex aspect-square size-auto w-full min-w-(--cell-size) flex-col gap-1 border-0 leading-none font-normal group-data-[focused=true]/day:relative group-data-[focused=true]/day:z-10 group-data-[focused=true]/day:border-ring group-data-[focused=true]/day:ring-[3px] group-data-[focused=true]/day:ring-ring/50 data-[range-end=true]:rounded-(--cell-radius) data-[range-end=true]:rounded-r-(--cell-radius) data-[range-end=true]:bg-primary data-[range-end=true]:text-primary-foreground data-[range-middle=true]:rounded-none data-[range-middle=true]:bg-muted data-[range-middle=true]:text-foreground data-[range-start=true]:rounded-(--cell-radius) data-[range-start=true]:rounded-l-(--cell-radius) data-[range-start=true]:bg-primary data-[range-start=true]:text-primary-foreground data-[selected-single=true]:bg-primary data-[selected-single=true]:text-primary-foreground dark:hover:text-foreground [&>span]:text-xs [&>span]:opacity-70",
        defaultClassNames.day,
        className
      ),
      ...props
    }
  );
}
function RadioGroup({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    RadioGroup$1.Root,
    {
      "data-slot": "radio-group",
      className: cn("grid w-full gap-3", className),
      ...props
    }
  );
}
function RadioGroupItem({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    RadioGroup$1.Item,
    {
      "data-slot": "radio-group-item",
      className: cn(
        "group/radio-group-item peer relative flex aspect-square size-4 shrink-0 rounded-full border border-input outline-none group-has-[:focus-visible]/field-label:ring-0 group-has-[:focus-visible]/field-label:not-data-checked:border-input after:absolute after:-inset-x-3 after:-inset-y-2 focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 aria-invalid:aria-checked:border-primary dark:bg-input/30 dark:aria-invalid:border-destructive/50 dark:aria-invalid:ring-destructive/40 data-checked:border-primary data-checked:bg-primary data-checked:text-primary-foreground group-has-[:focus-visible]/field-label:data-checked:border-primary dark:data-checked:bg-primary",
        className
      ),
      ...props,
      children: /* @__PURE__ */ jsx(
        RadioGroup$1.Indicator,
        {
          "data-slot": "radio-group-indicator",
          className: "flex size-4 items-center justify-center",
          children: /* @__PURE__ */ jsx("span", { className: "absolute top-1/2 left-1/2 size-2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-foreground" })
        }
      )
    }
  );
}
function KartuKetersediaan({
  kalender,
  offSaya,
  offAktif,
  jenisOff,
  urlOffStore,
  meja
}) {
  const [buka, setBuka] = useState(false);
  const [tab, setTab] = useState("semua");
  const form = useForm({
    jenis: Object.keys(jenisOff)[0] ?? "cuti",
    mulai: hariIniIso(),
    sampai: hariIniIso(),
    catatan: ""
  });
  const tanggalOff = kalender.sel.filter((s) => s !== null && s.off !== null).map((s) => tanggalLokal(s.tanggal));
  const selPertama = kalender.sel.find((s) => s !== null);
  const bulanTampil = selPertama ? tanggalLokal(selPertama.tanggal) : /* @__PURE__ */ new Date();
  const tugas = meja.filter((b) => b.sejak !== null).map((b) => ({
    kunci: `tugas-${b.dokumen.id}`,
    tanggal: b.sejak,
    judul: b.judul,
    ket: `${b.nomor} · ${b.umur < 1 ? "masuk hari ini" : `${b.umur} hari menunggu`}`,
    tautan: b.tautan,
    tugas: true
  }));
  const jadwal = offSaya.map((o) => ({
    kunci: `off-${o.id}`,
    tanggal: o.mulai,
    judul: o.jenis,
    // `rentang` LABEL siap cetak dari server — jangan disusun ulang di sini.
    ket: o.catatan ? `${o.rentang} · ${o.catatan}` : o.rentang,
    tautan: null,
    tugas: false
  }));
  const isiTab = {
    semua: [...tugas, ...jadwal].sort((a, b) => a.tanggal.localeCompare(b.tanggal)),
    tugas,
    jadwal
  };
  const tanggalAgendaBulanIni = new Set(isiTab.semua.map((a) => a.tanggal));
  const tanggalAgenda = kalender.sel.filter((s) => s !== null && tanggalAgendaBulanIni.has(s.tanggal)).map((s) => tanggalLokal(s.tanggal));
  const bukaDengan = (tgl) => {
    const iso = isoLokal(tgl);
    form.setData((d) => ({ ...d, mulai: iso, sampai: iso }));
    setBuka(true);
  };
  const ajukan = () => form.post(urlOffStore, { onSuccess: () => setBuka(false), preserveScroll: true });
  const batalkan = (url) => router.delete(url, { preserveScroll: true });
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsx(CardHeader, { className: "bg-muted/40 border-b", children: /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Ketersediaan Saya" }) }),
    /* @__PURE__ */ jsxs(CardContent, { className: "flex min-h-0 flex-1 flex-col", children: [
      /* @__PURE__ */ jsxs("div", { className: "mb-2 flex items-center justify-between", children: [
        /* @__PURE__ */ jsx(
          Button,
          {
            asChild: kalender.bisaMundur,
            variant: "ghost",
            size: "icon",
            className: cn("size-8", !kalender.bisaMundur && "invisible"),
            "aria-label": "Bulan sebelumnya",
            children: kalender.bisaMundur ? /* @__PURE__ */ jsx(Link, { href: kalender.urlSebelum, preserveScroll: true, preserveState: true, children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeft01Icon, strokeWidth: 1.5, className: "size-4" }) }) : /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeft01Icon, strokeWidth: 1.5, className: "size-4" })
          }
        ),
        /* @__PURE__ */ jsxs("span", { className: "text-sm font-semibold", children: [
          kalender.judul,
          !kalender.iniBulanIni && /* @__PURE__ */ jsx(
            Link,
            {
              href: kalender.urlHariIni,
              preserveScroll: true,
              preserveState: true,
              className: "ml-2 text-xs font-normal text-primary underline underline-offset-2",
              children: "hari ini"
            }
          )
        ] }),
        /* @__PURE__ */ jsx(
          Button,
          {
            asChild: kalender.bisaMaju,
            variant: "ghost",
            size: "icon",
            className: cn("size-8", !kalender.bisaMaju && "invisible"),
            "aria-label": "Bulan berikutnya",
            children: kalender.bisaMaju ? /* @__PURE__ */ jsx(Link, { href: kalender.urlSesudah, preserveScroll: true, preserveState: true, children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5, className: "size-4" }) }) : /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5, className: "size-4" })
          }
        )
      ] }),
      /* @__PURE__ */ jsx(
        Calendar,
        {
          month: bulanTampil,
          hideNavigation: true,
          showOutsideDays: false,
          locale: id,
          disabled: { before: new Date((/* @__PURE__ */ new Date()).setHours(0, 0, 0, 0)) },
          modifiers: { off: tanggalOff, agenda: tanggalAgenda },
          modifiersClassNames: {
            off: "bg-primary/20 text-primary font-bold rounded-md",
            // Titik agenda digantung DI BAWAH angkanya lewat
            // `::after`, bukan lewat elemen tambahan: sel kalender
            // digambar react-day-picker, dan menyisipkan anak ke
            // dalamnya berarti menyalin `components` bawaannya.
            agenda: "relative after:bg-primary after:absolute after:bottom-1 after:left-1/2 after:size-1 after:-translate-x-1/2 after:rounded-full after:content-['']"
          },
          onDayClick: (hari, pengubah) => {
            if (!pengubah.disabled) bukaDengan(hari);
          },
          className: "w-full p-0 [--cell-size:--spacing(9)]",
          classNames: {
            month_caption: "hidden",
            months: "w-full",
            disabled: "text-foreground/75",
            today: "rounded-md ring-1 ring-primary/40 bg-muted font-semibold data-[selected=true]:rounded-none"
          }
        }
      ),
      /* @__PURE__ */ jsxs("div", { className: "mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground", children: [
        /* @__PURE__ */ jsxs("span", { children: [
          /* @__PURE__ */ jsx("span", { className: "mr-1.5 inline-block size-2.5 rounded-full bg-primary/40 align-middle" }),
          "cuti/off"
        ] }),
        /* @__PURE__ */ jsxs("span", { children: [
          /* @__PURE__ */ jsx("span", { className: "ring-primary/40 bg-muted mr-1.5 inline-block size-2.5 rounded-full align-middle ring-1" }),
          "hari ini"
        ] }),
        /* @__PURE__ */ jsxs("span", { children: [
          /* @__PURE__ */ jsx("span", { className: "mr-1.5 inline-block size-1 rounded-full bg-primary align-middle" }),
          "ada agenda"
        ] })
      ] }),
      /* @__PURE__ */ jsxs(Tabs, { value: tab, onValueChange: setTab, className: "mt-4 min-h-0 flex-1 gap-2", children: [
        /* @__PURE__ */ jsxs(TabsList, { className: "w-full", children: [
          /* @__PURE__ */ jsx(TabsTrigger, { value: "semua", children: "Semua" }),
          /* @__PURE__ */ jsx(TabsTrigger, { value: "tugas", children: "Ditugaskan" }),
          /* @__PURE__ */ jsx(TabsTrigger, { value: "jadwal", children: "Jadwal Saya" })
        ] }),
        Object.entries(isiTab).map(([kunci, daftar]) => /* @__PURE__ */ jsx(TabsContent, { value: kunci, className: "relative min-h-[10rem] flex-1", children: /* @__PURE__ */ jsx("div", { className: "absolute inset-0", children: /* @__PURE__ */ jsx(ScrollArea, { className: "h-full", children: daftar.length === 0 ? /* @__PURE__ */ jsx("p", { className: "text-muted-foreground py-6 text-center text-xs", children: kunci === "jadwal" ? "Belum ada cuti atau off yang tercatat." : "Tak ada yang menunggu Anda." }) : /* @__PURE__ */ jsx("ul", { className: "space-y-2 pr-3", children: daftar.map((a) => /* @__PURE__ */ jsxs("li", { className: "flex items-start gap-2", children: [
          /* @__PURE__ */ jsx(
            "span",
            {
              "aria-hidden": "true",
              className: cn(
                "mt-1.5 size-2 shrink-0 rounded-full",
                a.tugas ? "bg-primary" : "bg-primary/40"
              )
            }
          ),
          /* @__PURE__ */ jsxs("div", { className: "min-w-0 flex-1", children: [
            a.tautan ? /* @__PURE__ */ jsx(
              Link,
              {
                href: a.tautan,
                className: "hover:text-primary block truncate text-xs font-medium",
                children: a.judul
              }
            ) : /* @__PURE__ */ jsx("p", { className: "truncate text-xs font-medium", children: a.judul }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground truncate text-xs", children: a.ket })
          ] }),
          /* @__PURE__ */ jsx("span", { className: "text-muted-foreground shrink-0 font-mono text-[0.6875rem]", children: a.tanggal })
        ] }, a.kunci)) }) }) }) }, kunci))
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-auto flex justify-end gap-1 pt-3", children: [
        offAktif && /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            pemicu: /* @__PURE__ */ jsxs(Button, { variant: "ghost", size: "sm", children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: UserCheck01Icon, strokeWidth: 1.5, className: "size-4" }),
              "On Site"
            ] }),
            judul: "Batalkan off?",
            pesan: "Anda langsung muncul lagi sebagai peninjau yang bisa dipilih.",
            tombolYa: "Ya, batalkan",
            onKonfirmasi: () => batalkan(offAktif.urlBatal)
          }
        ),
        /* @__PURE__ */ jsxs(Button, { variant: "ghost", size: "sm", onClick: () => setBuka(true), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CalendarAdd01Icon, strokeWidth: 1.5, className: "size-4" }),
          "Ajukan Off"
        ] })
      ] })
    ] }),
    /* @__PURE__ */ jsx(Dialog, { open: buka, onOpenChange: setBuka, children: /* @__PURE__ */ jsx(DialogContent, { className: "sm:max-w-lg", children: /* @__PURE__ */ jsxs("form", { onSubmit: (e) => e.preventDefault(), children: [
      /* @__PURE__ */ jsxs(DialogHeader, { children: [
        /* @__PURE__ */ jsx(DialogTitle, { children: "Ajukan Off" }),
        /* @__PURE__ */ jsx(DialogDescription, { children: "Selama rentang ini Anda tidak muncul sebagai peninjau yang bisa dipilih." })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "space-y-4 py-4", children: [
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsx(Label, { children: "Jenis" }),
          /* @__PURE__ */ jsx(
            RadioGroup,
            {
              value: form.data.jenis,
              onValueChange: (v) => form.setData("jenis", v),
              className: "flex flex-wrap gap-4",
              children: Object.entries(jenisOff).map(([nilai, label]) => /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
                /* @__PURE__ */ jsx(RadioGroupItem, { value: nilai, id: `jenis-${nilai}` }),
                /* @__PURE__ */ jsx(Label, { htmlFor: `jenis-${nilai}`, className: "font-normal", children: label })
              ] }, nilai))
            }
          ),
          form.errors.jenis && /* @__PURE__ */ jsx("p", { className: "text-sm text-destructive", children: form.errors.jenis })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-2 gap-3", children: [
          /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
            /* @__PURE__ */ jsx(Label, { htmlFor: "offMulai", children: "Mulai" }),
            /* @__PURE__ */ jsx(
              Input,
              {
                id: "offMulai",
                type: "date",
                min: hariIniIso(),
                value: form.data.mulai,
                onChange: (e) => form.setData("mulai", e.target.value),
                required: true
              }
            ),
            form.errors.mulai && /* @__PURE__ */ jsx("p", { className: "text-sm text-destructive", children: form.errors.mulai })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
            /* @__PURE__ */ jsx(Label, { htmlFor: "offSampai", children: "Sampai" }),
            /* @__PURE__ */ jsx(
              Input,
              {
                id: "offSampai",
                type: "date",
                min: hariIniIso(),
                max: isoLokal(new Date(Date.now() + 60 * 864e5)),
                value: form.data.sampai,
                onChange: (e) => form.setData("sampai", e.target.value),
                required: true
              }
            ),
            form.errors.sampai && /* @__PURE__ */ jsx("p", { className: "text-sm text-destructive", children: form.errors.sampai })
          ] })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsx(Label, { htmlFor: "offCatatan", children: "Catatan (opsional)" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "offCatatan",
              maxLength: 255,
              placeholder: "mis. cuti tahunan",
              value: form.data.catatan,
              onChange: (e) => form.setData("catatan", e.target.value)
            }
          )
        ] }),
        /* @__PURE__ */ jsxs(Alert, { children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsxs(AlertDescription, { children: [
            "Dokumen yang ",
            /* @__PURE__ */ jsx("strong", { children: "sedang" }),
            " Anda tinjau tetap menjadi tanggung jawab Anda."
          ] })
        ] }),
        offSaya.length > 0 && /* @__PURE__ */ jsxs("div", { className: "space-y-1", children: [
          /* @__PURE__ */ jsx("p", { className: "text-sm font-semibold", children: "Off yang tercatat" }),
          offSaya.map((off) => /* @__PURE__ */ jsxs(
            "div",
            {
              className: "flex items-center justify-between rounded-md border px-2 py-1.5 text-sm",
              children: [
                /* @__PURE__ */ jsxs("span", { children: [
                  /* @__PURE__ */ jsx("span", { className: "font-semibold", children: off.jenis }),
                  " ",
                  /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
                    "· ",
                    off.rentang
                  ] }),
                  off.catatan && /* @__PURE__ */ jsx("span", { className: "block text-xs text-muted-foreground", children: off.catatan })
                ] }),
                /* @__PURE__ */ jsx(
                  ConfirmDialog,
                  {
                    pemicu: /* @__PURE__ */ jsx(Button, { type: "button", variant: "link", size: "sm", className: "text-destructive", children: "Batalkan" }),
                    judul: "Batalkan off?",
                    pesan: `Batalkan ${off.jenis} ${off.rentang}? Anda langsung muncul lagi sebagai peninjau yang bisa dipilih.`,
                    tombolYa: "Ya, batalkan",
                    destruktif: true,
                    onKonfirmasi: () => batalkan(off.urlBatal)
                  }
                )
              ]
            },
            off.id
          ))
        ] })
      ] }),
      /* @__PURE__ */ jsxs(DialogFooter, { children: [
        /* @__PURE__ */ jsx(Button, { type: "button", variant: "outline", onClick: () => setBuka(false), children: "Batal" }),
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            pemicu: /* @__PURE__ */ jsxs(Button, { type: "button", disabled: form.processing, children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CalendarAdd01Icon, strokeWidth: 1.5, className: "size-4" }),
              "Ajukan Off"
            ] }),
            judul: "Ajukan off?",
            pesan: "Anda tidak akan bisa dipilih sebagai peninjau selama rentang ini. Dokumen yang sedang Anda tinjau tetap menjadi tanggung jawab Anda.",
            tombolYa: "Ya, ajukan",
            onKonfirmasi: ajukan
          }
        )
      ] })
    ] }) }) })
  ] });
}
function tanggalLokal(iso) {
  const [t, b, h] = iso.split("-").map(Number);
  return new Date(t, b - 1, h);
}
function isoLokal(d) {
  return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, "0"), String(d.getDate()).padStart(2, "0")].join("-");
}
function hariIniIso() {
  return isoLokal(/* @__PURE__ */ new Date());
}
const RONA$1 = {
  maju: "bg-primary text-primary-foreground",
  netral: "bg-foreground text-background",
  baik: "bg-chart-5 text-background",
  buruk: "bg-destructive text-white"
};
function KartuLog({ activities }) {
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Aktivitas Terbaru" }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Apa yang terjadi belakangan ini" })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "flex-1", children: activities.length === 0 ? /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
      /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-inbox", className: "size-6" }) }),
      /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada aktivitas" })
    ] }) }) : /* @__PURE__ */ jsx(ScrollArea, { className: "h-[22rem]", children: /* @__PURE__ */ jsx("div", { className: "pr-3", children: activities.map((log, i) => /* @__PURE__ */ jsxs("div", { className: "relative flex gap-3 pb-5", children: [
      i < activities.length - 1 && /* @__PURE__ */ jsx(
        "span",
        {
          className: "bg-border absolute top-8 bottom-0 left-[15px] w-px",
          "aria-hidden": "true"
        }
      ),
      /* @__PURE__ */ jsx(
        "span",
        {
          className: cn(
            "z-10 flex size-8 shrink-0 items-center justify-center rounded-full",
            RONA$1[log.rona] ?? RONA$1.netral
          ),
          children: /* @__PURE__ */ jsx(Ikon, { nama: log.ikon, className: "size-3.5" })
        }
      ),
      /* @__PURE__ */ jsxs("div", { className: "min-w-0 flex-1 pt-0.5", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-x-2 gap-y-1", children: [
          /* @__PURE__ */ jsx("p", { className: "text-sm font-semibold", children: log.aksi }),
          /* @__PURE__ */ jsx(Badge, { variant: "outline", className: "font-normal", children: log.kategori })
        ] }),
        /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground truncate text-xs", children: [
          "oleh ",
          log.pelaku,
          log.nomor ? " · " : "",
          log.nomor ? log.tautan ? /* @__PURE__ */ jsx(
            Link,
            {
              href: log.tautan,
              className: "hover:text-primary font-mono",
              children: log.nomor
            }
          ) : /* @__PURE__ */ jsx("span", { className: "font-mono", children: log.nomor }) : null
        ] }),
        /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-0.5 text-xs", children: log.waktu })
      ] })
    ] }, log.id)) }) }) })
  ] });
}
const RONA = {
  maju: "bg-primary",
  netral: "bg-muted-foreground",
  baik: "bg-chart-5",
  buruk: "bg-destructive"
};
function KartuMasukan({
  masukan,
  total,
  nonStaff,
  urlLogMasukan,
  urlBerlaku
}) {
  const jumlah = masukan.length;
  const sedikit = jumlah > 0 && jumlah < 3;
  const sisa = total - jumlah;
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: nonStaff ? "Masukan Saya" : "Masukan Lapangan" }),
      /* @__PURE__ */ jsxs(CardDescription, { className: "flex items-center gap-1.5", children: [
        /* @__PURE__ */ jsx(Ikon, { nama: "bi-chat-left-quote", className: "size-3.5" }),
        nonStaff ? "kiriman Anda & balasannya" : "belum ditindak"
      ] }),
      /* @__PURE__ */ jsx(CardAction, { children: /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: nonStaff ? urlBerlaku : urlLogMasukan, children: [
        nonStaff ? "Beri Masukan" : "Lihat semua",
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5, className: "size-4" })
      ] }) }) })
    ] }),
    jumlah === 0 ? /* @__PURE__ */ jsx(CardContent, { className: "flex flex-1 items-center justify-center", children: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
      /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-chat-left-dots", className: "size-6" }) }),
      nonStaff ? /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(EmptyTitle, { children: "Anda belum mengirim masukan" }),
        /* @__PURE__ */ jsxs(EmptyDescription, { children: [
          "Buka",
          " ",
          /* @__PURE__ */ jsx(Link, { href: urlBerlaku, className: "underline underline-offset-2", children: "Dokumen Berlaku" }),
          " ",
          "lalu pilih Beri Masukan."
        ] })
      ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(EmptyTitle, { children: "Tak ada masukan yang menunggu" }),
        /* @__PURE__ */ jsx(EmptyDescription, { children: "Masukan baru dari lapangan muncul di sini." })
      ] })
    ] }) }) }) : /* @__PURE__ */ jsxs(CardContent, { className: "flex flex-1 flex-col", children: [
      /* @__PURE__ */ jsx(ScrollArea, { className: "h-[17rem]", children: /* @__PURE__ */ jsx(
        ItemGroup,
        {
          className: cn(
            "grid gap-3 pr-3",
            sedikit ? "grid-cols-1 content-center" : "@3xl/card:grid-cols-2 grid-cols-1 content-start"
          ),
          children: masukan.map((m) => /* @__PURE__ */ jsx(
            Kutipan,
            {
              m,
              nonStaff,
              urlLogMasukan
            },
            m.id
          ))
        }
      ) }),
      sisa > 0 ? /* @__PURE__ */ jsxs(
        Link,
        {
          href: nonStaff ? urlBerlaku : urlLogMasukan,
          className: "text-muted-foreground hover:text-primary mt-3 text-xs underline underline-offset-2",
          children: [
            "Lihat ",
            sisa,
            " masukan lainnya →"
          ]
        }
      ) : null
    ] })
  ] });
}
function Kutipan({
  m,
  nonStaff,
  urlLogMasukan
}) {
  const tujuan = m.boleh_balas ? urlLogMasukan : m.tautan;
  return (
    /*
    | `flex-nowrap` WAJIB menemani `flex-col`.
    |
    | `Item` registry berbunyi `flex flex-wrap items-center` — dirancang
    | MENDATAR. Dibalik jadi kolom, `flex-wrap` mulai membungkus ke KOLOM
    | baru, dan `ItemHeader`/`ItemFooter` yang ber-`basis-full` (= tinggi
    | 100% begitu sumbu utamanya vertikal) memaksa tiap bagian pindah
    | kolomnya sendiri: status, umur, kutipan, pengirim, dan nomor berjajar
    | ke samping alih-alih bertumpuk. Itulah kartu masukan yang rusak.
    |
    | Karena itu pula kepala & kakinya `div` biasa, bukan `ItemHeader`/
    | `ItemFooter`: keduanya menggendong `basis-full` yang cuma benar di
    | `Item` mendatar.
    */
    /* @__PURE__ */ jsxs(Item, { variant: "outline", className: "flex-col flex-nowrap items-stretch gap-2", children: [
      /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between gap-2", children: [
        /* @__PURE__ */ jsxs(ItemTitle, { className: "flex items-center gap-1.5 text-xs font-medium", children: [
          /* @__PURE__ */ jsx(
            "span",
            {
              "aria-hidden": "true",
              className: cn("size-2 shrink-0 rounded-full", RONA[m.rona] ?? RONA.netral)
            }
          ),
          m.status
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5", children: [
          m.umur ? /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs whitespace-nowrap", children: m.umur }) : null,
          tujuan ? /* @__PURE__ */ jsxs(
            Link,
            {
              href: tujuan,
              title: m.boleh_balas ? "Balas atau tutup masukan ini" : "Buka dokumennya",
              className: "text-muted-foreground hover:text-primary",
              children: [
                /* @__PURE__ */ jsx(Ikon, { nama: "bi-check-circle", className: "size-4" }),
                /* @__PURE__ */ jsx("span", { className: "sr-only", children: m.boleh_balas ? "Balas atau tutup masukan ini" : "Buka dokumennya" })
              ]
            }
          ) : null
        ] })
      ] }),
      /* @__PURE__ */ jsxs(ItemContent, { className: "gap-1", children: [
        /* @__PURE__ */ jsxs("div", { className: "relative pl-4 text-sm leading-relaxed", children: [
          /* @__PURE__ */ jsx(
            "span",
            {
              className: "text-primary absolute top-[-0.35rem] left-0 text-xl leading-none",
              "aria-hidden": "true",
              children: "“"
            }
          ),
          m.tautan && !nonStaff ? /* @__PURE__ */ jsx(Link, { href: m.tautan, className: "hover:text-primary", children: m.isi }) : m.isi
        ] }),
        nonStaff && m.balasan ? /* @__PURE__ */ jsxs("div", { className: "border-primary bg-muted/60 mt-1 ml-4 rounded-r-md border-l-2 px-2 py-1.5 text-xs leading-relaxed", children: [
          /* @__PURE__ */ jsx(Ikon, { nama: "bi-reply", className: "mr-1 inline size-3" }),
          m.balasan
        ] }) : null
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between gap-2", children: [
        /* @__PURE__ */ jsx(ItemDescription, { className: "truncate", children: nonStaff ? `Pemilik: ${m.pemilik}` : `Dari ${m.pengirim} · Pemilik: ${m.pemilik}` }),
        /* @__PURE__ */ jsx(Badge, { variant: "secondary", className: "shrink-0 font-mono font-normal", children: m.nomor })
      ] })
    ] })
  );
}
function KartuSambutan({ greeting, nama, hero }) {
  return /* @__PURE__ */ jsx(Card, { className: "@container/card bg-gradient-to-t from-primary/5 to-card shadow-xs dark:bg-card", children: /* @__PURE__ */ jsxs(CardHeader, { children: [
    /* @__PURE__ */ jsxs(CardDescription, { className: "text-xs font-semibold tracking-wider uppercase", children: [
      hero.jabatan,
      hero.dept ? ` · ${hero.dept}` : ""
    ] }),
    /* @__PURE__ */ jsxs(CardTitle, { className: "text-xl font-semibold @[540px]/card:text-2xl", children: [
      greeting,
      ", ",
      nama,
      "."
    ] }),
    /* @__PURE__ */ jsxs(CardAction, { className: "text-right", children: [
      /* @__PURE__ */ jsx("div", { className: "text-2xl font-bold tabular-nums", children: hero.jam }),
      /* @__PURE__ */ jsxs("div", { className: "text-xs text-muted-foreground", children: [
        "WITA · ",
        hero.tanggal
      ] })
    ] }),
    /* @__PURE__ */ jsx("p", { className: "text-sm text-muted-foreground", children: hero.tunggu.length > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
      "Menunggu tindakanmu:",
      " ",
      /* @__PURE__ */ jsx("span", { className: "font-semibold text-foreground", children: hero.tunggu.join(" · ") }),
      "."
    ] }) : "Tidak ada yang menunggu tindakanmu. Selamat bekerja." }),
    /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-2 pt-2", children: hero.aksi.map((a) => /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", variant: a.utama ? "default" : "outline", children: /* @__PURE__ */ jsxs(Link, { href: a.url, children: [
      a.label,
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5, className: "size-4" })
    ] }) }, a.label)) })
  ] }) });
}
function ruasAktif({ cx, cy, innerRadius, outerRadius, startAngle, endAngle, fill }) {
  return /* @__PURE__ */ jsx(
    Sector,
    {
      cx,
      cy,
      innerRadius,
      outerRadius: (outerRadius ?? 0) + 8,
      startAngle,
      endAngle,
      fill
    }
  );
}
function KartuSebaran({ sebaran }) {
  const urutan = Object.keys(sebaran).sort((a, b) => sebaran[b].jumlah - sebaran[a].jumlah);
  const total = urutan.reduce((n, k) => n + sebaran[k].jumlah, 0);
  const warnaRamp = Object.fromEntries(
    urutan.map((k, i) => [
      k,
      `color-mix(in oklab, var(--chart-1) ${90 - i * (60 / Math.max(urutan.length - 1, 1))}%, var(--card))`
    ])
  );
  const konfigurasi2 = {
    jumlah: { label: "Dokumen" },
    ...Object.fromEntries(urutan.map((k) => [k, { label: sebaran[k].nama, color: warnaRamp[k] }]))
  };
  const data = urutan.map((k) => ({ jenis: k, jumlah: sebaran[k].jumlah, fill: warnaRamp[k] }));
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Sebaran Jenis" }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Komposisi dokumen berlaku menurut jenisnya" })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "flex-1", children: total === 0 ? /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
      /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-pie-chart", className: "size-6" }) }),
      /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada dokumen berlaku" }),
      /* @__PURE__ */ jsx(EmptyDescription, { children: "Komposisinya muncul di sini begitu ada yang disahkan." })
    ] }) }) : /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx(ChartContainer, { config: konfigurasi2, className: "mx-auto aspect-square max-h-[200px]", children: /* @__PURE__ */ jsxs(PieChart, { children: [
        /* @__PURE__ */ jsx(ChartTooltip, { cursor: false, content: /* @__PURE__ */ jsx(ChartTooltipContent, { hideLabel: true }) }),
        /* @__PURE__ */ jsx(
          Pie,
          {
            data,
            dataKey: "jumlah",
            nameKey: "jenis",
            innerRadius: 58,
            strokeWidth: 5,
            paddingAngle: 1,
            activeShape: ruasAktif,
            children: /* @__PURE__ */ jsx(
              Label$1,
              {
                content: ({ viewBox }) => {
                  if (viewBox && "cx" in viewBox && "cy" in viewBox) {
                    return /* @__PURE__ */ jsxs(
                      "text",
                      {
                        x: viewBox.cx,
                        y: viewBox.cy,
                        textAnchor: "middle",
                        dominantBaseline: "middle",
                        children: [
                          /* @__PURE__ */ jsx(
                            "tspan",
                            {
                              x: viewBox.cx,
                              y: viewBox.cy,
                              className: "fill-foreground text-3xl font-bold",
                              children: total.toLocaleString("id-ID")
                            }
                          ),
                          /* @__PURE__ */ jsx(
                            "tspan",
                            {
                              x: viewBox.cx,
                              y: (viewBox.cy || 0) + 24,
                              className: "fill-muted-foreground",
                              children: "Berlaku"
                            }
                          )
                        ]
                      }
                    );
                  }
                }
              }
            )
          }
        )
      ] }) }),
      /* @__PURE__ */ jsx("ul", { className: "mt-4 space-y-2.5", children: urutan.map((k) => /* @__PURE__ */ jsxs("li", { className: "flex items-center gap-3", children: [
        /* @__PURE__ */ jsx(
          "span",
          {
            "aria-hidden": "true",
            className: "size-2.5 shrink-0 rounded-full",
            style: { backgroundColor: warnaRamp[k] }
          }
        ),
        /* @__PURE__ */ jsxs("div", { className: "min-w-0 flex-1", children: [
          /* @__PURE__ */ jsx("div", { className: "text-sm font-semibold uppercase", children: k }),
          /* @__PURE__ */ jsx("div", { className: "text-muted-foreground truncate text-xs", children: sebaran[k].nama })
        ] }),
        /* @__PURE__ */ jsx("span", { className: "font-semibold tabular-nums", children: sebaran[k].jumlah }),
        /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground w-10 text-right text-xs tabular-nums", children: [
          Math.round(sebaran[k].jumlah / total * 100),
          "%"
        ] })
      ] }, k)) })
    ] }) })
  ] });
}
function LencanaDelta({
  arah,
  delta,
  deltaLabel,
  polos = false,
  className
}) {
  if (deltaLabel === null || delta === null) {
    return null;
  }
  const naik = delta >= 0;
  const kabar = arah === "netral" ? "netral" : naik === (arah === "baik") ? "baik" : "buruk";
  const isi = /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx(
      HugeiconsIcon,
      {
        icon: naik ? ArrowUpRight01Icon : ArrowDownRight01Icon,
        strokeWidth: 2,
        "aria-hidden": "true",
        className: "size-3.5"
      }
    ),
    deltaLabel
  ] });
  const warna = cn(
    kabar === "baik" && "text-chart-5",
    kabar === "buruk" && "text-destructive",
    kabar === "netral" && "text-muted-foreground"
  );
  if (polos) {
    return /* @__PURE__ */ jsx("span", { className: cn("inline-flex items-center gap-1 font-medium tabular-nums", warna, className), children: isi });
  }
  return /* @__PURE__ */ jsx(
    Badge,
    {
      variant: "outline",
      className: cn(
        "gap-1 font-medium tabular-nums",
        warna,
        kabar === "baik" && "border-chart-5/40",
        kabar === "buruk" && "border-destructive/40",
        className
      ),
      children: isi
    }
  );
}
function KartuStatistik({ tiles }) {
  return /* @__PURE__ */ jsx("div", { className: "@xl/main:grid-cols-2 @5xl/main:grid-cols-4 grid grid-cols-1 gap-4", children: tiles.map((t) => {
    const kartu = /* @__PURE__ */ jsxs(
      Card,
      {
        className: cn(
          // `--card-spacing` 24px → 16px: mekanismenya milik kit
          // (`ui-maia/card.tsx`), jadi header tak lagi setinggi
          // 72px tanpa satu byte pun berkas registry disunting
          // (TEMUAN-F8 1a).
          "@container/card relative h-full gap-0 transition-shadow [--card-spacing:--spacing(4)]",
          t.url && "group-hover:ring-primary/30 group-hover:ring-2"
        ),
        children: [
          /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
            /* @__PURE__ */ jsx(CardTitle, { className: "truncate text-sm font-bold", children: t.label }),
            /* @__PURE__ */ jsx(CardAction, { children: /* @__PURE__ */ jsx("span", { className: "bg-card flex size-7 shrink-0 items-center justify-center rounded-lg border", children: /* @__PURE__ */ jsx(Ikon, { nama: t.ikon, className: "size-4" }) }) })
          ] }),
          /* @__PURE__ */ jsxs(CardContent, { className: "flex flex-col gap-1 pt-(--card-spacing)", children: [
            /* @__PURE__ */ jsx(CardTitle, { className: "@[250px]/card:text-4xl text-3xl font-semibold tabular-nums", children: t.nilai }),
            /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5 text-xs", children: [
              /* @__PURE__ */ jsx(
                LencanaDelta,
                {
                  arah: t.arah,
                  delta: t.delta,
                  deltaLabel: t.deltaLabel,
                  polos: true,
                  className: "text-xs"
                }
              ),
              /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "vs 30 hari sebelumnya" })
            ] })
          ] })
        ]
      }
    );
    return t.url ? /* @__PURE__ */ jsx(Link, { href: t.url, className: "group block", children: kartu }, t.label) : /* @__PURE__ */ jsx("div", { children: kartu }, t.label);
  }) });
}
const DIPANTAU = ["draft", "in_review", "pending_approval", "rejected"];
const BERJALAN = ["draft", "in_review", "verifikasi_md", "pending_approval", "rejected", "sedang_direvisi"];
const RONA_UMUR = {
  baru: "border-chart-2/40 bg-chart-2/10 text-chart-2",
  sedang: "border-chart-3/40 bg-chart-3/10 text-chart-3",
  lama: "border-destructive/40 bg-destructive/10 text-destructive"
};
const TEKS_UMUR = {
  baru: "text-chart-2",
  sedang: "text-chart-3",
  lama: "text-destructive"
};
function LacakStatus({
  baris,
  matrix,
  urlSemua
}) {
  const { statusMeta } = usePage().props;
  const per = Object.fromEntries(matrix.map((m) => [m.status, m]));
  const totalBerjalan = BERJALAN.reduce((n, s) => n + (per[s]?.total ?? 0), 0);
  const totalSemua = matrix.reduce((n, m) => n + m.total, 0);
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Lacak Status Dokumen" }),
      /* @__PURE__ */ jsx(CardDescription, { children: baris.length > 0 ? "Yang menunggu tindakan Anda, beserta berapa lama ia diam" : "Tak ada dokumen yang menunggu tindakan Anda" }),
      /* @__PURE__ */ jsx(CardAction, { children: /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: urlSemua, children: [
        "Lihat semua",
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 1.5, className: "size-4" })
      ] }) }) })
    ] }),
    /* @__PURE__ */ jsxs(CardContent, { className: "flex min-h-0 flex-1 flex-col gap-4", children: [
      /* @__PURE__ */ jsx("div", { className: "@2xl/card:grid-cols-4 grid grid-cols-2 gap-4", children: DIPANTAU.map((s) => /* @__PURE__ */ jsx(AngkaStatus, { baris: per[s], warna: statusMeta[s]?.[0], total: totalSemua }, s)) }),
      baris.length === 0 ? /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
        /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-hourglass-split", className: "size-6" }) }),
        /* @__PURE__ */ jsx(EmptyTitle, { children: "Tak ada dokumen yang menunggu Anda" }),
        /* @__PURE__ */ jsx(EmptyDescription, { children: "Dokumen yang sudah dikirim muncul di sini beserta umurnya." })
      ] }) }) : (
        /* Tabelnya DIGULIR di dalam kartu, tingginya bukan
                               tingginya isi.
        
                               Polanya sama dengan `KartuDistribusi` dan lahir dari
                               kegagalan yang sama: kartu ini berbagi baris grid dengan
                               `KartuKetersediaan`, dan selama tabelnya digambar penuh
                               ia menarik tinggi baris ke bawah tanpa batas — makin
                               banyak dokumen berjalan, makin panjang halamannya.
        
                               Kotak absolutnya WAJIB `div` biasa. Memberi `absolute`
                               langsung ke `ScrollArea` tidak bekerja:
                               `ui-maia/scroll-area.tsx` merakit kelasnya sebagai
                               `cn("relative", className)`, dan dua kelas `position`
                               pada satu elemen dimenangkan urutan stylesheet — di
                               Tailwind v4 `relative` yang menang, `inset-0` mati, dan
                               gulirnya tak pernah aktif.
        
                               `inset-0` mengukur dari kotak BORDER `CardContent`, jadi
                               tabelnya otomatis membentang selebar kartu dan `-mx-6`
                               yang dulu dipakai untuk itu tak diperlukan lagi. */
        /* @__PURE__ */ jsx("div", { className: "relative min-h-[12rem] flex-1", children: /* @__PURE__ */ jsx("div", { className: "absolute inset-0", children: /* @__PURE__ */ jsx(ScrollArea, { className: "h-full", children: /* @__PURE__ */ jsxs(Table, { className: "table-fixed", children: [
          /* @__PURE__ */ jsx(TableHeader, { className: "bg-card sticky top-0 z-10", children: /* @__PURE__ */ jsxs(TableRow, { children: [
            /* @__PURE__ */ jsx(TableHead, { className: "w-[27%] pl-6", children: "Pembuat" }),
            /* @__PURE__ */ jsx(TableHead, { className: "w-[28%]", children: "Dokumen" }),
            /* @__PURE__ */ jsx(TableHead, { className: "w-[30%]", children: "Kemajuan" }),
            /* @__PURE__ */ jsx(TableHead, { className: "w-[15%] pr-6", children: "Status" })
          ] }) }),
          /* @__PURE__ */ jsx(TableBody, { children: baris.map((b) => /* @__PURE__ */ jsxs(TableRow, { className: "hover:bg-muted/40", children: [
            /* @__PURE__ */ jsx(TableCell, { className: "pl-6", children: /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
              /* @__PURE__ */ jsx(
                Avatar,
                {
                  nama: b.pembuat.nama,
                  foto: b.pembuat.foto,
                  className: "size-8 shrink-0"
                }
              ),
              /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
                /* @__PURE__ */ jsx(
                  "div",
                  {
                    className: "truncate text-sm font-semibold",
                    title: b.pembuat.nama,
                    children: b.pembuat.nama
                  }
                ),
                /* @__PURE__ */ jsxs("div", { className: "text-muted-foreground truncate text-xs", children: [
                  b.pembuat.jabatan,
                  b.dept ? ` · ${b.dept}` : ""
                ] })
              ] })
            ] }) }),
            /* @__PURE__ */ jsxs(TableCell, { children: [
              /* @__PURE__ */ jsx(
                Link,
                {
                  href: b.tautan,
                  className: "hover:text-primary block truncate text-sm font-semibold",
                  title: b.judul,
                  children: b.judul
                }
              ),
              /* @__PURE__ */ jsx("span", { className: "text-muted-foreground block truncate font-mono text-xs", children: b.nomor })
            ] }),
            /* @__PURE__ */ jsxs(TableCell, { children: [
              /* @__PURE__ */ jsxs("div", { className: "mb-1 flex items-center justify-between gap-2", children: [
                /* @__PURE__ */ jsx(
                  "span",
                  {
                    className: cn(
                      "truncate text-xs font-semibold",
                      TEKS_UMUR[b.tingkat]
                    ),
                    title: b.pemegang ? `${b.menunggu} · ${b.pemegang}` : b.menunggu,
                    children: b.menunggu
                  }
                ),
                /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground shrink-0 text-xs tabular-nums", children: [
                  b.ke,
                  "/",
                  b.daftarTahap.length
                ] })
              ] }),
              /* @__PURE__ */ jsx(
                Progress,
                {
                  value: b.persen,
                  className: "h-1.5",
                  title: `${b.daftarTahap.join(" → ")} · sekarang: ${b.tahap}`
                }
              )
            ] }),
            /* @__PURE__ */ jsxs(TableCell, { className: "pr-6", children: [
              /* @__PURE__ */ jsx(
                Badge,
                {
                  variant: "outline",
                  className: cn("font-medium", RONA_UMUR[b.tingkat]),
                  children: b.umur < 1 ? "hari ini" : `${b.umur} hari`
                }
              ),
              b.pemegang && /* @__PURE__ */ jsx(
                "span",
                {
                  className: "text-muted-foreground mt-1 block truncate text-xs",
                  title: b.pemegang,
                  children: b.pemegang
                }
              )
            ] })
          ] }, b.dokumen.id)) })
        ] }) }) }) })
      ),
      /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground mt-auto text-xs", children: [
        baris.length,
        " dari ",
        totalBerjalan,
        " dokumen berjalan ·",
        " ",
        /* @__PURE__ */ jsx(Link, { href: urlSemua, className: "hover:text-primary underline underline-offset-2", children: "lihat daftar lengkapnya" })
      ] })
    ] })
  ] });
}
function AngkaStatus({ baris, warna, total }) {
  const nilai = baris?.total ?? 0;
  const rona = warna ?? "var(--primary)";
  return /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
    /* @__PURE__ */ jsx("p", { className: "text-3xl font-semibold tabular-nums", children: nilai }),
    /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5", children: [
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground truncate text-xs", children: baris?.label ?? "—" }),
      baris && /* @__PURE__ */ jsx(
        LencanaDelta,
        {
          arah: baris.arah ?? "netral",
          delta: baris.delta,
          deltaLabel: baris.deltaLabel,
          polos: true,
          className: "text-xs"
        }
      )
    ] }),
    /* @__PURE__ */ jsx(
      Progress,
      {
        value: total > 0 ? nilai / total * 100 : 0,
        className: "mt-1.5 h-2 rounded-full [&>[data-slot=progress-indicator]]:bg-(--rona)",
        style: {
          "--rona": rona,
          backgroundColor: `color-mix(in oklab, ${rona} 18%, transparent)`
        }
      }
    ),
    baris?.aliranKet && /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-1 truncate text-[11px]", children: baris.aliranKet })
  ] });
}
function MeterTertinjau({ deret }) {
  const konfigurasi2 = {
    persen: { label: deret.meterLabel },
    nilai: { label: deret.meterLabel, color: "var(--chart-1)" }
  };
  const data = [{ kunci: "nilai", persen: deret.growth, fill: "var(--color-nilai)" }];
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "items-center bg-muted/40 border-b pb-0", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: deret.meterLabel }),
      /* @__PURE__ */ jsx(CardDescription, { children: deret.rentang })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "flex-1 pb-0", children: /* @__PURE__ */ jsx(ChartContainer, { config: konfigurasi2, className: "mx-auto aspect-square max-h-[220px]", children: /* @__PURE__ */ jsxs(
      RadialBarChart,
      {
        data,
        startAngle: 0,
        endAngle: deret.growth * 3.6,
        innerRadius: 80,
        outerRadius: 110,
        children: [
          /* @__PURE__ */ jsx(
            PolarGrid,
            {
              gridType: "circle",
              radialLines: false,
              stroke: "none",
              className: "first:fill-muted last:fill-background",
              polarRadius: [86, 74]
            }
          ),
          /* @__PURE__ */ jsx(RadialBar, { dataKey: "persen", background: true, cornerRadius: 10 }),
          /* @__PURE__ */ jsx(PolarRadiusAxis, { tick: false, tickLine: false, axisLine: false, domain: [0, 100], children: /* @__PURE__ */ jsx(
            Label$1,
            {
              content: ({ viewBox }) => {
                if (viewBox && "cx" in viewBox && "cy" in viewBox) {
                  return /* @__PURE__ */ jsxs("text", { x: viewBox.cx, y: viewBox.cy, textAnchor: "middle", dominantBaseline: "middle", children: [
                    /* @__PURE__ */ jsxs(
                      "tspan",
                      {
                        x: viewBox.cx,
                        y: viewBox.cy,
                        className: "fill-foreground text-3xl font-bold",
                        children: [
                          deret.growth,
                          "%"
                        ]
                      }
                    ),
                    /* @__PURE__ */ jsx(
                      "tspan",
                      {
                        x: viewBox.cx,
                        y: (viewBox.cy || 0) + 24,
                        className: "fill-muted-foreground",
                        children: deret.meterLabel
                      }
                    )
                  ] });
                }
              }
            }
          ) })
        ]
      }
    ) }) }),
    /* @__PURE__ */ jsxs(CardFooter, { className: "flex-col gap-2 text-sm", children: [
      /* @__PURE__ */ jsx("div", { className: "text-center text-muted-foreground", children: deret.meterKeterangan }),
      /* @__PURE__ */ jsxs("div", { className: "flex w-full justify-center gap-8", children: [
        /* @__PURE__ */ jsxs("div", { className: "text-center", children: [
          /* @__PURE__ */ jsx("div", { className: "text-xs text-muted-foreground", children: "Dibuat" }),
          /* @__PURE__ */ jsx("div", { className: "font-semibold tabular-nums", children: deret.total })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "text-center", children: [
          /* @__PURE__ */ jsx("div", { className: "text-xs text-muted-foreground", children: "Berlaku" }),
          /* @__PURE__ */ jsx("div", { className: "font-semibold tabular-nums", children: deret.berlakuTotal })
        ] })
      ] })
    ] })
  ] });
}
function PerformaPic({ data }) {
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: data.judul }),
      /* @__PURE__ */ jsx(CardDescription, { children: data.subjudul })
    ] }),
    /* @__PURE__ */ jsxs(CardContent, { className: "flex flex-1 flex-col gap-4", children: [
      /* @__PURE__ */ jsx("p", { className: "text-4xl font-semibold tabular-nums", children: data.picAktif }),
      /* @__PURE__ */ jsx(Separator, {}),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mb-2 text-xs font-medium", children: "Paling produktif 30 hari" }),
        /* @__PURE__ */ jsx(
          TumpukanWajah,
          {
            satuan: data.satuan,
            pembaca: data.wajah.map((w) => ({
              nama: w.nama,
              foto: w.foto,
              ket: `${w.jumlah}`
            }))
          }
        ),
        data.wajah.length > 0 && /* @__PURE__ */ jsx("div", { className: "mt-3 flex flex-col", children: data.wajah.slice(0, 5).map((w, i) => /* @__PURE__ */ jsxs(Item, { size: "sm", className: "gap-2 px-0 py-1.5", children: [
          /* @__PURE__ */ jsx(ItemMedia, { children: /* @__PURE__ */ jsx(Avatar, { nama: w.nama, foto: w.foto, className: "size-6" }) }),
          /* @__PURE__ */ jsxs(ItemContent, { className: "gap-0", children: [
            /* @__PURE__ */ jsx(ItemTitle, { className: "truncate", children: w.nama }),
            /* @__PURE__ */ jsx(ItemDescription, { className: "truncate", children: w.ket })
          ] })
        ] }, `${w.nama}-${i}`)) })
      ] }),
      /* @__PURE__ */ jsx(Separator, {}),
      /* @__PURE__ */ jsxs("div", { className: "space-y-3", children: [
        /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-xs font-medium", children: "Sorotan" }),
        data.sorotan.map((s) => /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
          /* @__PURE__ */ jsx("span", { className: "text-muted-foreground min-w-0 flex-1 truncate text-sm", children: s.label }),
          /* @__PURE__ */ jsx(LencanaDelta, { arah: s.arah, delta: s.delta, deltaLabel: s.deltaLabel }),
          /* @__PURE__ */ jsx("span", { className: "shrink-0 text-sm font-semibold tabular-nums", children: s.nilai })
        ] }, s.label))
      ] })
    ] })
  ] });
}
function rampMerah(indeks, jumlah) {
  const persen = jumlah > 1 ? 90 - indeks * (60 / (jumlah - 1)) : 90;
  return `color-mix(in oklab, var(--chart-1) ${persen}%, var(--card))`;
}
const RAMP = 6;
const langkah = (i) => rampMerah(i, RAMP);
const WARNA = {
  SOP: langkah(0),
  IK: langkah(1),
  SP: langkah(2),
  JSA: langkah(3),
  FK: langkah(4),
  PX: langkah(5)
};
const chartConfig = Object.fromEntries(
  Object.entries(WARNA).map(([kode, warna]) => [kode, { label: kode, color: warna }])
);
function PitaDepartemen({ sebaran }) {
  const jenisKeys = Object.keys(sebaran.baris[0]?.per ?? {});
  const totalJenis = {};
  for (const j of jenisKeys) {
    totalJenis[j] = sebaran.baris.reduce((acc, b) => acc + (b.per[j] ?? 0), 0);
  }
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Sebaran per Departemen" }),
      /* @__PURE__ */ jsx(CardDescription, { children: sebaran.total > 0 ? `${sebaran.total} dokumen berlaku, terbagi ke ${sebaran.baris.length} departemen` : "Belum ada dokumen berlaku" })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "flex flex-1 flex-col", children: sebaran.total === 0 ? /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
      /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-building", className: "size-6" }) }),
      /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada dokumen berlaku" })
    ] }) }) : /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-start justify-between gap-x-8 gap-y-4", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-start gap-x-8 gap-y-4", children: [
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("span", { className: "text-2xl font-semibold tabular-nums", children: [
              sebaran.persenTerbesar,
              "%"
            ] }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-xs", children: "Porsi departemen terbesar" })
          ] }),
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
              /* @__PURE__ */ jsx("span", { className: "text-2xl font-semibold tabular-nums", children: sebaran.total }),
              /* @__PURE__ */ jsx(
                LencanaDelta,
                {
                  arah: sebaran.arah,
                  delta: sebaran.delta,
                  deltaLabel: sebaran.deltaLabel
                }
              )
            ] }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-xs", children: "Dokumen berlaku · 30 hari" })
          ] })
        ] }),
        /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-x-5 gap-y-2", children: jenisKeys.map((j) => /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-1", children: [
          /* @__PURE__ */ jsx(
            "span",
            {
              "aria-hidden": "true",
              className: "h-1.5 w-8 rounded-full",
              style: { backgroundColor: WARNA[j] ?? "var(--muted-foreground)" }
            }
          ),
          /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs font-medium", children: j }),
          /* @__PURE__ */ jsxs("span", { className: "text-xs font-semibold tabular-nums", children: [
            sebaran.total > 0 ? Math.round(totalJenis[j] / sebaran.total * 100) : 0,
            "%"
          ] })
        ] }, j)) })
      ] }),
      /* @__PURE__ */ jsx(
        ChartContainer,
        {
          config: chartConfig,
          className: "mt-4 aspect-auto min-h-[260px] w-full flex-1",
          children: /* @__PURE__ */ jsxs(BarChart, { data: sebaran.baris, accessibilityLayer: true, children: [
            /* @__PURE__ */ jsx(CartesianGrid, { vertical: false, strokeDasharray: "3 3" }),
            /* @__PURE__ */ jsx(XAxis, { dataKey: "kode", tickLine: false, axisLine: false, tickMargin: 8 }),
            /* @__PURE__ */ jsx(YAxis, { tickLine: false, axisLine: false, tickMargin: 8, width: 32 }),
            /* @__PURE__ */ jsx(ChartTooltip, { content: /* @__PURE__ */ jsx(ChartTooltipContent, { indicator: "dot" }) }),
            jenisKeys.map((j, i) => /* @__PURE__ */ jsx(
              Bar,
              {
                dataKey: `per.${j}`,
                name: j,
                stackId: "a",
                maxBarSize: 44,
                fill: `var(--color-${j})`,
                radius: i === 0 ? [0, 0, 4, 4] : i === jenisKeys.length - 1 ? [4, 4, 0, 0] : 0
              },
              j
            ))
          ] })
        }
      )
    ] }) })
  ] });
}
function SebaranJenisDept({ sebaran }) {
  const { statusLabels } = usePage().props;
  const statusKeys = Object.keys(sebaran.baris[0]?.per ?? {});
  const warna = (s) => rampMerah(statusKeys.indexOf(s), statusKeys.length);
  const chartConfig2 = Object.fromEntries(
    statusKeys.map((s) => [s, { label: statusLabels[s] ?? s, color: warna(s) }])
  );
  const totalStatus = {};
  for (const s of statusKeys) {
    totalStatus[s] = sebaran.baris.reduce((acc, b) => acc + (b.per[s] ?? 0), 0);
  }
  return /* @__PURE__ */ jsxs(Card, { className: "@container/card flex h-full flex-col", children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "bg-muted/40 border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { className: "text-sm font-bold", children: "Sebaran Dokumen Departemen" }),
      /* @__PURE__ */ jsx(CardDescription, { children: sebaran.total > 0 ? `${sebaran.total} dokumen, terbagi ke ${sebaran.baris.length} jenis` : "Belum ada dokumen di departemen ini" })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "flex flex-1 flex-col", children: sebaran.total === 0 ? /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
      /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-files", className: "size-6" }) }),
      /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada dokumen di departemen ini" })
    ] }) }) : /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-start justify-between gap-x-8 gap-y-4", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-start gap-x-8 gap-y-4", children: [
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("span", { className: "text-2xl font-semibold tabular-nums", children: [
              sebaran.persenTerbesar,
              "%"
            ] }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-xs", children: "Porsi jenis terbesar" })
          ] }),
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
              /* @__PURE__ */ jsx("span", { className: "text-2xl font-semibold tabular-nums", children: sebaran.total }),
              /* @__PURE__ */ jsx(
                LencanaDelta,
                {
                  arah: sebaran.arah,
                  delta: sebaran.delta,
                  deltaLabel: sebaran.deltaLabel
                }
              )
            ] }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-xs", children: "Dokumen dibuat · 30 hari" })
          ] })
        ] }),
        /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-x-5 gap-y-2", children: statusKeys.map((s) => /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-1", children: [
          /* @__PURE__ */ jsx(
            "span",
            {
              "aria-hidden": "true",
              className: "h-1.5 w-8 rounded-full",
              style: { backgroundColor: warna(s) }
            }
          ),
          /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs font-medium", children: statusLabels[s] ?? s }),
          /* @__PURE__ */ jsxs("span", { className: "text-xs font-semibold tabular-nums", children: [
            sebaran.total > 0 ? Math.round(totalStatus[s] / sebaran.total * 100) : 0,
            "%"
          ] })
        ] }, s)) })
      ] }),
      /* @__PURE__ */ jsx(
        ChartContainer,
        {
          config: chartConfig2,
          className: "mt-4 aspect-auto min-h-[260px] w-full flex-1",
          children: /* @__PURE__ */ jsxs(BarChart, { data: sebaran.baris, accessibilityLayer: true, children: [
            /* @__PURE__ */ jsx(CartesianGrid, { vertical: false, strokeDasharray: "3 3" }),
            /* @__PURE__ */ jsx(XAxis, { dataKey: "kode", tickLine: false, axisLine: false, tickMargin: 8 }),
            /* @__PURE__ */ jsx(YAxis, { tickLine: false, axisLine: false, tickMargin: 8, width: 32 }),
            /* @__PURE__ */ jsx(ChartTooltip, { content: /* @__PURE__ */ jsx(ChartTooltipContent, { indicator: "dot" }) }),
            statusKeys.map((s, i) => /* @__PURE__ */ jsx(
              Bar,
              {
                dataKey: `per.${s}`,
                name: statusLabels[s] ?? s,
                stackId: "a",
                maxBarSize: 44,
                fill: `var(--color-${s})`,
                radius: i === 0 ? [0, 0, 4, 4] : i === statusKeys.length - 1 ? [4, 4, 0, 0] : 0
              },
              s
            ))
          ] })
        }
      )
    ] }) })
  ] });
}
function Dashboard() {
  const { props } = usePage();
  const {
    user,
    greeting,
    hero,
    tiles,
    matrix,
    menungguDiMeja,
    masukanWidget,
    masukanTotal,
    distribusiWidget,
    sebaranDepartemen,
    sebaranJenisDept,
    performaPic,
    aktivitasTabel,
    aktivitasFilters,
    aktivitasDepartemen,
    aktivitasStatusOpsi,
    kalenderOff,
    offSaya,
    offAktif,
    isCreator,
    isPjo,
    isDeptHead,
    isMd,
    isNonStaff,
    activities,
    tren,
    sebaran,
    jenisList,
    jenisOff,
    urlOffStore
  } = props;
  const [durasi, setDurasi] = useState("bulan");
  const deret = tren[durasi];
  const adaMeja = menungguDiMeja.length > 0 || isCreator || isDeptHead || isPjo || isMd;
  const adaMasukan = masukanWidget.length > 0 || isNonStaff || isDeptHead || isPjo || isCreator || isMd;
  const duaKolom = adaMeja || adaMasukan;
  return /* @__PURE__ */ jsxs(AppLayout, { judul: "Dashboard", children: [
    /* @__PURE__ */ jsx(KartuSambutan, { greeting, nama: user.name, hero }),
    /* @__PURE__ */ jsxs("div", { className: "@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6", children: [
      /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2", children: /* @__PURE__ */ jsx(GrafikOverview, { deret, durasi, onDurasi: setDurasi }) }),
      /* @__PURE__ */ jsx(MeterTertinjau, { deret })
    ] }),
    /* @__PURE__ */ jsx(KartuStatistik, { tiles }),
    /* @__PURE__ */ jsxs("div", { className: "@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6", children: [
      distribusiWidget && /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2", children: /* @__PURE__ */ jsx(KartuDistribusi, { widget: distribusiWidget }) }),
      /* @__PURE__ */ jsx("div", { className: distribusiWidget ? "" : "@5xl/main:col-span-3", children: /* @__PURE__ */ jsx(KartuSebaran, { sebaran }) })
    ] }),
    (sebaranDepartemen || performaPic) && /* @__PURE__ */ jsxs("div", { className: "@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6", children: [
      sebaranDepartemen ? /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2", children: /* @__PURE__ */ jsx(PitaDepartemen, { sebaran: sebaranDepartemen }) }) : sebaranJenisDept ? /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2", children: /* @__PURE__ */ jsx(SebaranJenisDept, { sebaran: sebaranJenisDept }) }) : /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2 @5xl/main:block hidden" }),
      performaPic && /* @__PURE__ */ jsx(PerformaPic, { data: performaPic })
    ] }),
    aktivitasTabel && /* @__PURE__ */ jsx(
      AktivitasTerbaru,
      {
        tabel: aktivitasTabel,
        filters: aktivitasFilters ?? {},
        departemen: aktivitasDepartemen,
        statusOpsi: aktivitasStatusOpsi,
        jenisList
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6", children: [
      adaMeja ? /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2", children: /* @__PURE__ */ jsx(
        LacakStatus,
        {
          baris: menungguDiMeja,
          matrix,
          urlSemua: route("documents.index")
        }
      ) }) : duaKolom ? /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2 @5xl/main:block hidden" }) : null,
      /* @__PURE__ */ jsx("div", { className: duaKolom ? "" : "@5xl/main:col-span-3", children: /* @__PURE__ */ jsx(
        KartuKetersediaan,
        {
          kalender: kalenderOff,
          offSaya,
          offAktif,
          jenisOff,
          urlOffStore,
          meja: menungguDiMeja
        }
      ) })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "@5xl/main:grid-cols-3 grid grid-cols-1 gap-4 md:gap-6", children: [
      adaMasukan ? /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2", children: /* @__PURE__ */ jsx(
        KartuMasukan,
        {
          masukan: masukanWidget,
          total: masukanTotal,
          nonStaff: isNonStaff,
          urlLogMasukan: route("log.masukan"),
          urlBerlaku: route("documents.published")
        }
      ) }) : duaKolom ? /* @__PURE__ */ jsx("div", { className: "@5xl/main:col-span-2 @5xl/main:block hidden" }) : null,
      /* @__PURE__ */ jsx("div", { className: duaKolom ? "" : "@5xl/main:col-span-3", children: /* @__PURE__ */ jsx(KartuLog, { activities }) })
    ] })
  ] });
}
export {
  Dashboard as default
};
