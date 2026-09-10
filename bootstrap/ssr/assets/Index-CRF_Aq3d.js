import { jsx, jsxs } from "react/jsx-runtime";
import { router, Link } from "@inertiajs/react";
import { A as AppLayout, I as Ikon, i as ItemGroup, j as Item, k as ItemMedia, l as ItemContent, m as ItemTitle, n as ItemDescription, t as ItemActions, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription } from "./empty-CkAl4IHy.js";
import { P as Paginasi } from "./DataTable-Cclynbfl.js";
import { P as PenyaringDokumen } from "./PenyaringDokumen-ZbRxHXvI.js";
import "react";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "@hugeicons/react";
import "@hugeicons/core-free-icons";
import "./table-COSAIfdh.js";
import "./input-B9Vuz8J-.js";
import "./input-group-C5LPhh_3.js";
import "./label-emiz6fAI.js";
import "./select-Db_F_VlA.js";
function NotificationsIndex({
  notifikasi,
  filters,
  kategoriOpsi,
  belumDibaca
}) {
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Notifikasi",
      sub: "Ikuti perkembangan dokumen yang menyangkut Anda.",
      aksi: belumDibaca > 0 ? /* @__PURE__ */ jsxs(
        Button,
        {
          variant: "outline",
          onClick: () => router.post(route("notifications.readAll"), {}, { preserveScroll: true }),
          children: [
            /* @__PURE__ */ jsx(Ikon, { nama: "bi-check-circle", className: "size-4" }),
            "Tandai Semua Dibaca"
          ]
        }
      ) : null,
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("notifications.index"),
            filters,
            contoh: "perlu ditinjau",
            labelCari: "Cari notifikasi",
            placeholderCari: "Cari notifikasi…",
            pilihan: [
              {
                nama: "status",
                label: "Status",
                opsi: [
                  ["", "Semua"],
                  ["belum", "Belum dibaca"],
                  ["sudah", "Sudah dibaca"]
                ]
              },
              {
                nama: "kategori",
                label: "Kategori",
                opsi: [["", "Semua"], ...kategoriOpsi.map((k) => [k, k])]
              }
            ]
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: notifikasi.data.length === 0 ? /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
          /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-bell", className: "size-6" }) }),
          /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada notifikasi" }),
          /* @__PURE__ */ jsx(EmptyDescription, { children: "Coba longgarkan penyaring." })
        ] }) }) : /* @__PURE__ */ jsx(ItemGroup, { className: "gap-0 px-4", children: notifikasi.data.map((n, i) => /* @__PURE__ */ jsx(Baris, { n, terakhir: i === notifikasi.data.length - 1 }, n.id)) }) }),
        notifikasi.data.length > 0 ? /* @__PURE__ */ jsx("div", { className: "border-t px-6 py-4", children: /* @__PURE__ */ jsx(Paginasi, { paginator: notifikasi }) }) : null
      ] })
    }
  );
}
function Baris({ n, terakhir }) {
  return /* @__PURE__ */ jsx(Item, { asChild: true, className: terakhir ? "" : "border-b", children: /* @__PURE__ */ jsxs(Link, { href: route("notifications.open", n.id), children: [
    /* @__PURE__ */ jsx(ItemMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: n.ikon, className: "size-4" }) }),
    /* @__PURE__ */ jsxs(ItemContent, { children: [
      /* @__PURE__ */ jsxs(ItemTitle, { children: [
        n.judul,
        !n.dibaca ? /* @__PURE__ */ jsx("span", { "aria-hidden": "true", className: "bg-primary size-2 shrink-0 rounded-full" }) : null
      ] }),
      /* @__PURE__ */ jsx(ItemDescription, { children: n.pesan })
    ] }),
    /* @__PURE__ */ jsxs(ItemActions, { children: [
      /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: n.kategori }),
      n.waktu ? /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs whitespace-nowrap", children: n.waktu }) : null
    ] })
  ] }) });
}
export {
  NotificationsIndex as default
};
