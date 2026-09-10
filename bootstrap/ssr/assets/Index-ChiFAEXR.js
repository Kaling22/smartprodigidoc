import { jsxs, jsx } from "react/jsx-runtime";
import { InformationCircleIcon, InboxIcon, CircleSlashIcon, FileEditIcon, Tick02Icon, ViewIcon, OctagonXIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, router, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { A as AppLayout, B as Badge, o as DropdownMenuItem, D as Dialog, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, g as DialogFooter } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError } from "./field-AJb7LB-s.js";
import { T as Textarea } from "./textarea-CFf6776c.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
import { S as StripAksi } from "./StripAksi-BFgTXRBT.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "sonner";
import "./label-emiz6fAI.js";
import "./table-COSAIfdh.js";
function NonaktifIndex() {
  const { documents } = usePage().props;
  const kolom = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    { judul: "Judul", render: (d) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: d.judul }) },
    { judul: "Jenis", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.jenis ?? "—" }) },
    { judul: "Dept", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.dept ?? "—" }) },
    { judul: "Pengaju", render: (d) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: d.pengaju ?? "—" }) },
    {
      judul: "Tahap",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex flex-col items-start gap-1", children: [
        /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: d.tahapLabel }),
        d.tahapTerakhir ? /* @__PURE__ */ jsx(
          Badge,
          {
            variant: "destructive",
            title: "Menyetujui di tahap ini langsung mematikan dokumen",
            children: "tahap terakhir"
          }
        ) : null
      ] })
    },
    {
      judul: "Alasan",
      kelas: "max-w-88",
      render: (d) => /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: d.alasan ?? "—" })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsx(AksiBaris, { doc: d })
    }
  ];
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: "Persetujuan Nonaktif",
      sub: /* @__PURE__ */ jsxs("span", { className: "flex flex-wrap items-center gap-1.5", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: CircleSlashIcon,
            strokeWidth: 1.5,
            className: "size-4",
            "aria-hidden": "true"
          }
        ),
        "Pengajuan mematikan dokumen Berlaku yang menunggu keputusan Anda."
      ] }),
      children: [
        /* @__PURE__ */ jsxs(Alert, { children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsxs(AlertDescription, { children: [
            "Pengajuan berjalan",
            " ",
            /* @__PURE__ */ jsx("strong", { children: "SH/DH departemen → Management Development → PJO" }),
            ". Dokumen tetap",
            " ",
            /* @__PURE__ */ jsx("strong", { children: "Berlaku" }),
            " selama pengajuan berjalan; nomornya baru dilepas setelah tahap terakhir menyetujui, dan penolakan di tahap mana pun mengembalikannya ke Berlaku."
          ] })
        ] }),
        /* @__PURE__ */ jsxs(Card, { children: [
          /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
            DataTable,
            {
              kolom,
              baris: documents.data,
              kunci: (d) => d.id,
              kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
                /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: InboxIcon,
                    strokeWidth: 1.5,
                    className: "size-6"
                  }
                ) }),
                /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada pengajuan nonaktif yang menunggu keputusan Anda." })
              ] }) })
            }
          ) }),
          /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
        ] })
      ]
    }
  );
}
function AksiBaris({ doc }) {
  const [tolak, setTolak] = useState(false);
  return /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
    /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "icon", title: "Lihat PDF", children: /* @__PURE__ */ jsx("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }) }) }),
    /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: "Setujui Nonaktif?",
        pesan: doc.tahapTerakhir ? `Ini tahap TERAKHIR: ${doc.nomor} langsung menjadi Tidak Berlaku dan nomornya dilepas.` : "Pengajuan diteruskan ke tahap berikutnya. Dokumen tetap Berlaku sampai keputusan terakhir.",
        tombolYa: "Ya, setujui",
        destruktif: doc.tahapTerakhir,
        onKonfirmasi: () => router.post(
          route("nonaktif.putuskan", doc.id),
          { keputusan: "setuju" },
          { preserveScroll: true }
        ),
        pemicu: /* @__PURE__ */ jsxs(Button, { variant: "secondary", size: "sm", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Tick02Icon, strokeWidth: 1.5, className: "size-4" }),
          "Setujui"
        ] })
      }
    ),
    /* @__PURE__ */ jsxs(StripAksi, { children: [
      /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("documents.show", doc.id), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }),
        "Lihat detail"
      ] }) }),
      /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => setTolak(true), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: OctagonXIcon, strokeWidth: 1.5, className: "size-4" }),
        "Tolak"
      ] })
    ] }),
    /* @__PURE__ */ jsx(DialogTolak, { doc, buka: tolak, onUbahBuka: setTolak })
  ] });
}
function DialogTolak({
  doc,
  buka,
  onUbahBuka
}) {
  const form = useForm({ keputusan: "tolak", alasan: "" });
  return /* @__PURE__ */ jsx(Dialog, { open: buka, onOpenChange: onUbahBuka, children: /* @__PURE__ */ jsxs(DialogContent, { children: [
    /* @__PURE__ */ jsxs(DialogHeader, { children: [
      /* @__PURE__ */ jsxs(DialogTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: OctagonXIcon, strokeWidth: 1.5, className: "size-4" }),
        "Tolak Pengajuan — ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: doc.nomor })
      ] }),
      /* @__PURE__ */ jsxs(DialogDescription, { children: [
        "Dokumen langsung kembali ",
        /* @__PURE__ */ jsx("strong", { children: "Berlaku" }),
        " dan pengaju dikabari alasannya."
      ] })
    ] }),
    /* @__PURE__ */ jsx(
      "form",
      {
        id: `tolak-${doc.id}`,
        onSubmit: (e) => {
          e.preventDefault();
          form.post(route("nonaktif.putuskan", doc.id), {
            preserveScroll: true,
            onSuccess: () => {
              onUbahBuka(false);
              form.reset();
            }
          });
        },
        children: /* @__PURE__ */ jsx(FieldGroup, { children: /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.alasan || void 0, children: [
          /* @__PURE__ */ jsxs(FieldLabel, { htmlFor: `alasan-${doc.id}`, children: [
            "Alasan penolakan ",
            /* @__PURE__ */ jsx("span", { className: "text-destructive", children: "*" })
          ] }),
          /* @__PURE__ */ jsx(
            Textarea,
            {
              id: `alasan-${doc.id}`,
              rows: 3,
              maxLength: 2e3,
              required: true,
              "aria-invalid": !!form.errors.alasan,
              placeholder: "mis. Pekerjaannya masih berjalan di shift malam.",
              value: form.data.alasan,
              onChange: (e) => form.setData("alasan", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: form.errors.alasan ? [{ message: form.errors.alasan }] : void 0
            }
          )
        ] }) })
      }
    ),
    /* @__PURE__ */ jsxs(DialogFooter, { children: [
      /* @__PURE__ */ jsx(Button, { type: "button", variant: "ghost", onClick: () => onUbahBuka(false), children: "Batal" }),
      /* @__PURE__ */ jsxs(
        Button,
        {
          type: "submit",
          form: `tolak-${doc.id}`,
          variant: "destructive",
          disabled: form.processing,
          children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: OctagonXIcon, strokeWidth: 1.5, className: "size-4" }),
            "Tolak Pengajuan"
          ]
        }
      )
    ] })
  ] }) });
}
export {
  NonaktifIndex as default
};
