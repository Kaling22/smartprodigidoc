import { jsx, jsxs } from "react/jsx-runtime";
import { ClipboardXIcon, Delete02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, router } from "@inertiajs/react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { P as Progress } from "./progress-CJN4SIW1.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
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
import "./table-COSAIfdh.js";
import "./input-B9Vuz8J-.js";
import "./input-group-C5LPhh_3.js";
import "./label-emiz6fAI.js";
import "./select-Db_F_VlA.js";
function JobExecutionsIndex() {
  const { pekerjaan, filters, statusLabels, bolehHapus } = usePage().props;
  const kolom = [
    {
      judul: "Nama Pekerjaan",
      render: (j) => /* @__PURE__ */ jsxs("span", { children: [
        /* @__PURE__ */ jsx("span", { className: "block font-medium", children: j.nama }),
        j.catatan ? /* @__PURE__ */ jsx(
          "span",
          {
            className: "text-muted-foreground block max-w-50 truncate text-sm",
            title: j.catatan,
            children: j.catatan
          }
        ) : null
      ] })
    },
    {
      judul: "JSA Acuan",
      render: (j) => /* @__PURE__ */ jsxs("span", { children: [
        /* @__PURE__ */ jsx("span", { className: "block text-sm font-medium", children: j.jsa_judul ?? "—" }),
        /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground block text-xs", children: [
          j.jsa_nomor ?? "—",
          j.jsa_dept ? ` · ${j.jsa_dept}` : ""
        ] })
      ] })
    },
    { judul: "Pelaksana", render: (j) => j.pelaksana ?? "—" },
    {
      judul: "NRP",
      render: (j) => /* @__PURE__ */ jsx(Badge, { variant: "secondary", className: "font-normal", children: j.nrp ?? "—" })
    },
    { judul: "Lokasi", render: (j) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: j.lokasi ?? "—" }) },
    {
      judul: "Tanggal",
      kelas: "whitespace-nowrap",
      render: (j) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: j.tanggal ?? "—" })
    },
    { judul: "Progres", kelas: "min-w-28", render: (j) => /* @__PURE__ */ jsx(BarProgres, { job: j }) },
    {
      judul: "Status",
      render: (j) => /* @__PURE__ */ jsx(Badge, { variant: rona(j.status), children: j.status_label })
    },
    ...bolehHapus ? [
      {
        judul: "Aksi",
        kelas: "w-px text-right whitespace-nowrap",
        render: (j) => /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            judul: "Hapus Riwayat Pekerjaan?",
            pesan: `Hapus riwayat pekerjaan "${j.nama}"? Data yang dihapus tidak dapat dikembalikan.`,
            tombolYa: "Ya, hapus",
            destruktif: true,
            onKonfirmasi: () => router.delete(route("job-executions.destroy", j.id), {
              preserveScroll: true
            }),
            pemicu: /* @__PURE__ */ jsx(Button, { variant: "outline", size: "icon", title: "Hapus riwayat", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }) })
          }
        )
      }
    ] : []
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Riwayat Pekerjaan JSA",
      sub: "Daftar pekerjaan lapangan yang menggunakan formulir JSA — se-departemen Anda.",
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("job-executions.index"),
            filters,
            labelCari: "Cari (nama pekerjaan / lokasi / user / JSA)",
            placeholderCari: "mis. pengelasan, Area 3B, GL-0001...",
            pilihan: [
              {
                nama: "status",
                label: "Status",
                opsi: [["", "Semua"], ...Object.entries(statusLabels)]
              },
              { nama: "tanggal_dari", label: "Tanggal dari", tipe: "tanggal" },
              { nama: "tanggal_sampai", label: "Tanggal sampai", tipe: "tanggal" }
            ]
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom,
            baris: pekerjaan.data,
            kunci: (j) => j.id,
            kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
              /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ClipboardXIcon, strokeWidth: 1.5, className: "size-6" }) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada riwayat pekerjaan" })
            ] }) })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: pekerjaan }) })
      ] })
    }
  );
}
function rona(status) {
  if (status === "selesai") return "default";
  if (status === "dibatalkan") return "outline";
  return "secondary";
}
function BarProgres({ job }) {
  if (job.status === "dibatalkan") {
    return /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: "—" });
  }
  const { selesai, total } = job.progres;
  const persen = total > 0 ? Math.round(selesai / total * 100) : 0;
  return /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5", children: [
    /* @__PURE__ */ jsx(Progress, { value: persen, className: "h-1.5 flex-1" }),
    /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-xs whitespace-nowrap", children: [
      selesai,
      "/",
      total
    ] })
  ] });
}
export {
  JobExecutionsIndex as default
};
