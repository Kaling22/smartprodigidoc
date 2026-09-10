import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { usePage, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, I as Ikon, B as Badge, o as DropdownMenuItem } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription, e as EmptyContent } from "./empty-CkAl4IHy.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
import { P as PenyaringDokumen, s as saringDepartemen, b as saringStatus, a as saringJenis } from "./PenyaringDokumen-ZbRxHXvI.js";
import { S as StatusBadge } from "./StatusBadge-BV-SY8b9.js";
import { S as StripAksi } from "./StripAksi-BFgTXRBT.js";
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
function DocumentsIndex() {
  const {
    documents,
    filters,
    types,
    departments,
    showAllStatuses,
    statusOpsi,
    jenisBaru,
    prefix,
    statusLabels
  } = usePage().props;
  const kolom = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    {
      judul: "Judul",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-1 font-medium", children: [
        d.judul,
        d.masukan_sejawat ? /* @__PURE__ */ jsx(Link, { href: route("masukan-sejawat.show", d.id), title: "Masukan dari rekan sedepartemen", children: /* @__PURE__ */ jsxs(Badge, { variant: "secondary", children: [
          /* @__PURE__ */ jsx(Ikon, { nama: "bi-chat-left-text", className: "size-3" }),
          d.masukan_sejawat
        ] }) }) : null
      ] })
    },
    { judul: "Jenis", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.jenis ?? "—" }) },
    { judul: "Dept", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.dept ?? "—" }) },
    { judul: "Status", render: (d) => /* @__PURE__ */ jsx(StatusBadge, { status: d.status }) },
    { judul: "Pembuat", render: (d) => /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: d.pembuat ?? "—" }) },
    { judul: "Aksi", kelas: "w-px text-right whitespace-nowrap", render: (d) => /* @__PURE__ */ jsx(AksiBaris, { doc: d }) }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: showAllStatuses ? "Dokumen Saya" : "Status Dokumen",
      sub: showAllStatuses ? `Seluruh dokumen yang Anda buat, segala status — ${documents.total} dokumen.` : `Dokumen yang Anda buat atau di departemen Anda — ${documents.total} dokumen.`,
      aksi: jenisBaru ? /* @__PURE__ */ jsx(Button, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("documents.create", { type: jenisBaru }), children: [
        /* @__PURE__ */ jsx(Ikon, { nama: "bi-file-earmark-plus", className: "size-4" }),
        "Dokumen Baru"
      ] }) }) : null,
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("documents.index"),
            filters,
            prefix,
            pilihan: [
              ...departments.length > 0 ? [saringDepartemen(departments)] : [],
              saringStatus(statusOpsi, statusLabels),
              saringJenis(types),
              // Pemilih SUMBER hanya di halaman ini: "Dokumen Berlaku"
              // dan "Status Dokumen Staff" sengaja mencampur keduanya.
              {
                nama: "sumber",
                label: "Sumber",
                opsi: [["", "Semua"], ["wizard", "Dokumen SmartPro"], ["unggahan", "Dokumen Lama"]]
              }
            ]
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom,
            baris: documents.data,
            kunci: (d) => d.id,
            kosong: /* @__PURE__ */ jsxs(Empty, { className: "border-0", children: [
              /* @__PURE__ */ jsxs(EmptyHeader, { children: [
                /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(Ikon, { nama: "bi-inbox", className: "size-6" }) }),
                /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada dokumen" }),
                /* @__PURE__ */ jsx(EmptyDescription, { children: "Coba longgarkan penyaring, atau mulai dokumen baru." })
              ] }),
              jenisBaru ? /* @__PURE__ */ jsx(EmptyContent, { children: /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.create", { type: jenisBaru }), children: [
                /* @__PURE__ */ jsx(Ikon, { nama: "bi-file-earmark-plus", className: "size-4" }),
                "Buat dokumen baru"
              ] }) }) }) : null
            ] })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
      ] })
    }
  );
}
function AksiBaris({ doc }) {
  const [hapus, setHapus] = useState(false);
  return /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
    /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: [
      /* @__PURE__ */ jsx(Ikon, { nama: "bi-file-earmark-text", className: "size-4" }),
      "PDF"
    ] }) }),
    doc.boleh_edit_arsip ? /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.arsip.edit", doc.id), children: [
      /* @__PURE__ */ jsx(Ikon, { nama: "bi-pencil", className: "size-4" }),
      "Edit"
    ] }) }) : null,
    doc.milik && doc.status === "draft" ? /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Kirim Dokumen?",
          pesan: "Kirim dokumen ini untuk ditinjau? Setelah dikirim tidak bisa diedit (masih bisa Ditarik selama belum ditinjau).",
          tombolYa: "Ya, kirim",
          onKonfirmasi: () => router.post(route("documents.submit", doc.id), {}, { preserveScroll: true }),
          pemicu: /* @__PURE__ */ jsxs(Button, { size: "sm", children: [
            /* @__PURE__ */ jsx(Ikon, { nama: "bi-send", className: "size-4" }),
            "Kirim"
          ] })
        }
      ),
      /* @__PURE__ */ jsxs(StripAksi, { children: [
        /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("documents.edit", doc.id), children: [
          /* @__PURE__ */ jsx(Ikon, { nama: "bi-pencil", className: "size-4" }),
          "Edit"
        ] }) }),
        /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => setHapus(true), children: [
          /* @__PURE__ */ jsx(Ikon, { nama: "bi-trash", className: "size-4" }),
          "Hapus"
        ] })
      ] }),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Hapus Draft?",
          pesan: "Hapus draft ini? Tindakan tidak bisa dibatalkan.",
          tombolYa: "Ya, hapus",
          destruktif: true,
          buka: hapus,
          onUbahBuka: setHapus,
          onKonfirmasi: () => router.delete(route("documents.destroy", doc.id), { preserveScroll: true })
        }
      )
    ] }) : doc.milik && doc.status === "waiting_for_review" ? /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx(TombolLihat, { doc }),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Tarik Dokumen?",
          pesan: "Tarik dokumen dari antrian tinjauan? Dokumen kembali ke Draft.",
          tombolYa: "Ya, tarik",
          onKonfirmasi: () => router.post(route("documents.withdraw", doc.id), {}, { preserveScroll: true }),
          pemicu: /* @__PURE__ */ jsxs(Button, { variant: "secondary", size: "sm", children: [
            /* @__PURE__ */ jsx(Ikon, { nama: "bi-arrow-counterclockwise", className: "size-4" }),
            "Tarik"
          ] })
        }
      )
    ] }) : /* @__PURE__ */ jsx(TombolLihat, { doc })
  ] });
}
function TombolLihat({ doc }) {
  return /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.show", doc.id), children: [
    /* @__PURE__ */ jsx(Ikon, { nama: "bi-info-circle", className: "size-4" }),
    "Lihat"
  ] }) });
}
export {
  DocumentsIndex as default
};
