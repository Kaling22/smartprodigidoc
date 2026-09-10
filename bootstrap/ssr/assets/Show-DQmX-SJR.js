import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { ArrowLeft01Icon, InformationCircleIcon, Clock01Icon, RadioIcon, Message01Icon, RefreshCwIcon, FileEditIcon, PencilIcon, ViewIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { A as AppLayout, B as Badge, I as Ikon, a as Avatar } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, a as CardContent } from "./card-B3VJCD2M.js";
import { a as DialogMasukan, b as DialogRevisi, d as DaftarMasukan } from "./DialogAksi-D_7VYKsd.js";
import { P as PitaCakupan } from "./PitaCakupan-D6HiIzDA.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
import { R as RincianPembaca } from "./RincianPembaca-DlYicxjy.js";
import { S as StatusBadge } from "./StatusBadge-BV-SY8b9.js";
import "react";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./alert-BvkCPa_V.js";
import "./checkbox-DZTcz7W1.js";
import "./field-AJb7LB-s.js";
import "./label-emiz6fAI.js";
import "./input-B9Vuz8J-.js";
import "./textarea-CFf6776c.js";
import "./ConfirmDialog-C9h_aHIF.js";
import "./progress-CJN4SIW1.js";
function DocumentsShow() {
  const {
    document: doc,
    timeline,
    masukan,
    masukanBelumDitindak,
    bolehMembalasMasukan,
    bolehLihatMasukan,
    distribusi,
    distribusiRincian,
    ketersediaanPembuat
  } = usePage().props;
  const panelMasukan = bolehLihatMasukan || doc.boleh_beri_masukan || masukan.length > 0;
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: `Detail: ${doc.nomor}`,
      aksi: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }),
          "Lihat PDF"
        ] }) }),
        doc.arsip ? doc.boleh_edit_arsip ? /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.arsip.edit", doc.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PencilIcon, strokeWidth: 1.5, className: "size-4" }),
          "Perbaiki"
        ] }) }) : null : /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.edit", doc.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }),
          "Buka Form"
        ] }) })
      ] }),
      children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              onClick: () => window.history.back(),
              className: "text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline",
              children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: ArrowLeft01Icon,
                    strokeWidth: 1.5,
                    className: "size-3.5",
                    "aria-hidden": "true"
                  }
                ),
                "Kembali"
              ]
            }
          ),
          /* @__PURE__ */ jsx("h2", { className: "mt-1 text-lg font-bold", children: doc.judul }),
          /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-2", children: [
            /* @__PURE__ */ jsx(NomorDokumen, { doc }),
            /* @__PURE__ */ jsx(StatusBadge, { status: doc.status })
          ] })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "grid gap-4 lg:grid-cols-12", children: [
          /* @__PURE__ */ jsxs(Card, { className: "lg:col-span-5", children: [
            /* @__PURE__ */ jsx(CardHeader, { children: /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: InformationCircleIcon,
                  strokeWidth: 1.5,
                  className: "size-4",
                  "aria-hidden": "true"
                }
              ),
              "Informasi Dokumen"
            ] }) }),
            /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsxs("dl", { className: "grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm", children: [
              /* @__PURE__ */ jsx(Baris, { label: "Jenis", nilai: doc.jenis_nama }),
              /* @__PURE__ */ jsx(Baris, { label: "Departemen", nilai: doc.dept_nama }),
              /* @__PURE__ */ jsx(Baris, { label: "No. Revisi", nilai: String(doc.no_revisi) }),
              /* @__PURE__ */ jsx(Baris, { label: "Dibuat Oleh", nilai: doc.pembuat }),
              /* @__PURE__ */ jsx(Baris, { label: "Ditinjau Oleh", nilai: doc.peninjau }),
              /* @__PURE__ */ jsx(Baris, { label: "Disetujui Oleh", nilai: doc.penyetuju }),
              /* @__PURE__ */ jsx(Baris, { label: "Tgl Terbit", nilai: doc.terbit })
            ] }) })
          ] }),
          /* @__PURE__ */ jsxs(Card, { className: "lg:col-span-7", children: [
            /* @__PURE__ */ jsxs(CardHeader, { className: "flex items-center justify-between", children: [
              /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: Clock01Icon,
                    strokeWidth: 1.5,
                    className: "size-4",
                    "aria-hidden": "true"
                  }
                ),
                "Timeline Riwayat"
              ] }),
              timeline.length > 4 ? /* @__PURE__ */ jsxs(Badge, { variant: "secondary", children: [
                timeline.length,
                " peristiwa"
              ] }) : null
            ] }),
            /* @__PURE__ */ jsx(CardContent, { className: "max-h-56 overflow-y-auto", children: timeline.length === 0 ? /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "Belum ada riwayat." }) : timeline.map((log) => /* @__PURE__ */ jsx(Peristiwa, { log }, log.id)) })
          ] }),
          distribusi && distribusiRincian ? /* @__PURE__ */ jsxs(Card, { className: "lg:col-span-12", children: [
            /* @__PURE__ */ jsxs(CardHeader, { className: "flex flex-wrap items-center justify-between gap-2", children: [
              /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: RadioIcon,
                    strokeWidth: 1.5,
                    className: "size-4",
                    "aria-hidden": "true"
                  }
                ),
                "Distribusi"
              ] }),
              /* @__PURE__ */ jsx("div", { className: "min-w-56", children: /* @__PURE__ */ jsx(PitaCakupan, { c: distribusi }) })
            ] }),
            /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsx(
              RincianPembaca,
              {
                rincian: distribusiRincian,
                cakupan: distribusi,
                kosong: "Tak ada pengguna aktif lain di departemen ini, jadi tak ada yang bisa diukur."
              }
            ) })
          ] }) : null,
          panelMasukan ? /* @__PURE__ */ jsxs(Card, { className: "lg:col-span-12", children: [
            /* @__PURE__ */ jsxs(CardHeader, { className: "flex flex-wrap items-center justify-between gap-2", children: [
              /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: Message01Icon,
                    strokeWidth: 1.5,
                    className: "size-4",
                    "aria-hidden": "true"
                  }
                ),
                "Masukan Lapangan",
                masukanBelumDitindak.length > 0 ? /* @__PURE__ */ jsxs(Badge, { variant: "destructive", children: [
                  masukanBelumDitindak.length,
                  " belum ditindak"
                ] }) : null
              ] }),
              /* @__PURE__ */ jsxs("div", { className: "flex gap-2", children: [
                doc.boleh_beri_masukan ? /* @__PURE__ */ jsx(
                  DialogMasukan,
                  {
                    doc,
                    masukanSaya: masukan,
                    pemicu: /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", children: [
                      /* @__PURE__ */ jsx(
                        HugeiconsIcon,
                        {
                          icon: Message01Icon,
                          strokeWidth: 1.5,
                          className: "size-4"
                        }
                      ),
                      "Beri Masukan"
                    ] })
                  }
                ) : null,
                doc.boleh_revisi ? /* @__PURE__ */ jsx(
                  DialogRevisi,
                  {
                    doc,
                    masukanRevisi: masukanBelumDitindak,
                    jadwalPembuat: doc.pembuat_id ? ketersediaanPembuat[doc.pembuat_id] ?? null : null,
                    pemicu: /* @__PURE__ */ jsxs(Button, { variant: "secondary", size: "sm", children: [
                      /* @__PURE__ */ jsx(
                        HugeiconsIcon,
                        {
                          icon: RefreshCwIcon,
                          strokeWidth: 1.5,
                          className: "size-4"
                        }
                      ),
                      "Revisi"
                    ] })
                  }
                ) : null
              ] })
            ] }),
            /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsx(DaftarMasukan, { masukan, bolehMembalas: bolehMembalasMasukan }) })
          ] }) : null
        ] })
      ]
    }
  );
}
function Baris({ label, nilai }) {
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: label }),
    /* @__PURE__ */ jsx("dd", { children: nilai ?? "—" })
  ] });
}
const RONA = {
  primary: "bg-primary text-primary-foreground",
  info: "bg-chart-2 text-background",
  success: "bg-chart-5 text-background",
  warning: "bg-chart-3 text-background",
  danger: "bg-destructive text-white",
  secondary: "bg-muted-foreground text-background"
};
function Peristiwa({ log }) {
  const [label, ikon, rona] = log.meta;
  return /* @__PURE__ */ jsxs("div", { className: "relative flex gap-3 pb-4 last:pb-0", children: [
    /* @__PURE__ */ jsx(
      "span",
      {
        className: `flex size-7 shrink-0 items-center justify-center rounded-full ${RONA[rona] ?? RONA.secondary}`,
        children: /* @__PURE__ */ jsx(Ikon, { nama: ikon, className: "size-3.5" })
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
      /* @__PURE__ */ jsx("p", { className: "text-sm font-semibold", children: label }),
      /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground flex items-center gap-1.5 text-xs", children: [
        /* @__PURE__ */ jsx(Avatar, { nama: log.oleh, foto: log.foto, className: "size-5" }),
        log.oleh,
        " · ",
        log.waktu,
        " WITA"
      ] })
    ] })
  ] });
}
export {
  DocumentsShow as default
};
