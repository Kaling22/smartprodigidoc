import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { FolderOpenIcon, Message01Icon, Xls01Icon, FileEditIcon, PencilIcon, RefreshCwIcon, CircleSlashIcon, CancelCircleIcon, Delete02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, B as Badge, p as DropdownMenu, q as DropdownMenuTrigger, r as DropdownMenuContent, s as DropdownMenuLabel, o as DropdownMenuItem, I as Ikon } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription } from "./empty-CkAl4IHy.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { a as DialogMasukan, b as DialogRevisi, c as DialogNonaktif, D as DialogMusnahkan } from "./DialogAksi-D_7VYKsd.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
import { P as PenyaringDokumen, a as saringJenis, s as saringDepartemen } from "./PenyaringDokumen-ZbRxHXvI.js";
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
import "./table-COSAIfdh.js";
import "./alert-BvkCPa_V.js";
import "./checkbox-DZTcz7W1.js";
import "./field-AJb7LB-s.js";
import "./label-emiz6fAI.js";
import "./input-B9Vuz8J-.js";
import "./textarea-CFf6776c.js";
import "./input-group-C5LPhh_3.js";
import "./select-Db_F_VlA.js";
function DocumentsPublished() {
  const { documents, filters, departments, jenis, rupa, prefix, ketersediaanPembuat, auth } = usePage().props;
  const bolehExport = auth.can["document.publish"] || auth.can["document.review"] || auth.can["document.create"];
  const kolom = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    {
      judul: "Judul",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-1 font-medium", children: [
        d.judul,
        d.masukan_count ? /* @__PURE__ */ jsx(Link, { href: route("documents.show", d.id), children: /* @__PURE__ */ jsxs(Badge, { variant: "destructive", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Message01Icon, strokeWidth: 2, className: "size-3" }),
          d.masukan_count,
          " masukan"
        ] }) }) : null
      ] })
    },
    { judul: "Jenis", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.jenis ?? "—" }) },
    { judul: "Dept", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.dept ?? "—" }) },
    { judul: "No. Revisi", urut: "revisi", kelas: "text-center", render: (d) => d.no_revisi },
    { judul: "Status", render: (d) => /* @__PURE__ */ jsx(StatusBadge, { status: d.status }) },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsx(
        AksiBaris,
        {
          doc: d,
          jadwal: d.pembuat_id ? ketersediaanPembuat[d.pembuat_id] ?? null : null
        }
      )
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Dokumen Berlaku",
      sub: /* @__PURE__ */ jsxs(Fragment, { children: [
        "Dokumen aktif ",
        departments.length > 0 ? "di 7 departemen" : "di departemen Anda",
        " —",
        " ",
        documents.total,
        " dokumen.",
        " ",
        !auth.can["document.request_revision"] && !auth.can["beri-masukan"] ? /* @__PURE__ */ jsxs(Fragment, { children: [
          "Masukan lapangan & alasan revisi terkumpul di menu",
          " ",
          /* @__PURE__ */ jsx("strong", { children: "Log Dokumen" }),
          "."
        ] }) : null
      ] }),
      aksi: bolehExport ? /* @__PURE__ */ jsx(MenuExport, { jenis, rupa }) : null,
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("documents.published"),
            filters,
            prefix,
            pilihan: [
              saringJenis(jenis),
              ...departments.length > 0 ? [saringDepartemen(departments)] : []
            ]
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom,
            baris: documents.data,
            kunci: (d) => d.id,
            kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
              /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FolderOpenIcon, strokeWidth: 1.5, className: "size-6" }) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada dokumen Berlaku" }),
              /* @__PURE__ */ jsx(EmptyDescription, { children: "Coba longgarkan penyaring di atas." })
            ] }) })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
      ] })
    }
  );
}
function MenuExport({
  jenis,
  rupa
}) {
  return /* @__PURE__ */ jsxs(DropdownMenu, { children: [
    /* @__PURE__ */ jsx(DropdownMenuTrigger, { asChild: true, children: /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Xls01Icon, strokeWidth: 1.5, className: "size-4" }),
      "Export Excel"
    ] }) }),
    /* @__PURE__ */ jsxs(DropdownMenuContent, { align: "end", children: [
      /* @__PURE__ */ jsx(DropdownMenuLabel, { children: "Daftar Induk Dokumen" }),
      jenis.map((j) => /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs("a", { href: route("documents.export", j), children: [
        /* @__PURE__ */ jsx(Ikon, { nama: rupa[j]?.[0], className: "size-4" }),
        j
      ] }) }, j))
    ] })
  ] });
}
function AksiBaris({ doc, jadwal }) {
  const [jendela, setJendela] = useState(null);
  return /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
    /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }),
      "PDF"
    ] }) }),
    /* @__PURE__ */ jsxs(StripAksi, { children: [
      doc.boleh_edit_arsip ? /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("documents.arsip.edit", doc.id), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PencilIcon, strokeWidth: 1.5, className: "size-4" }),
        "Edit"
      ] }) }) : null,
      doc.boleh_beri_masukan ? /* @__PURE__ */ jsxs(DropdownMenuItem, { onSelect: () => setJendela("masukan"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Message01Icon, strokeWidth: 1.5, className: "size-4" }),
        "Beri Masukan"
      ] }) : null,
      doc.boleh_revisi ? /* @__PURE__ */ jsxs(DropdownMenuItem, { onSelect: () => setJendela("revisi"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RefreshCwIcon, strokeWidth: 1.5, className: "size-4" }),
        "Revisi"
      ] }) : null,
      doc.boleh_revisi ? /* @__PURE__ */ jsxs(DropdownMenuItem, { onSelect: () => setJendela("nonaktif"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CircleSlashIcon, strokeWidth: 1.5, className: "size-4" }),
        "Ajukan Nonaktif"
      ] }) : null,
      doc.boleh_batal_revisi ? /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => setJendela("batal"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CancelCircleIcon, strokeWidth: 1.5, className: "size-4" }),
        "Batalkan Revisi"
      ] }) : null,
      doc.boleh_musnahkan ? /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => setJendela("musnahkan"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }),
        "Musnahkan"
      ] }) : null
    ] }),
    doc.boleh_beri_masukan ? /* @__PURE__ */ jsx(
      DialogMasukan,
      {
        doc,
        masukanSaya: doc.masukan,
        buka: jendela === "masukan",
        onUbahBuka: (b) => setJendela(b ? "masukan" : null)
      }
    ) : null,
    doc.boleh_revisi ? /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx(
        DialogRevisi,
        {
          doc,
          masukanRevisi: doc.masukan,
          jadwalPembuat: jadwal,
          buka: jendela === "revisi",
          onUbahBuka: (b) => setJendela(b ? "revisi" : null)
        }
      ),
      /* @__PURE__ */ jsx(
        DialogNonaktif,
        {
          doc,
          buka: jendela === "nonaktif",
          onUbahBuka: (b) => setJendela(b ? "nonaktif" : null)
        }
      )
    ] }) : null,
    doc.boleh_batal_revisi ? /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: "Batalkan Revisi?",
        pesan: `Batalkan revisi? Versi baru dibuang dan versi lama (${doc.nomor}) kembali Berlaku.`,
        tombolYa: "Ya, batalkan",
        destruktif: true,
        buka: jendela === "batal",
        onUbahBuka: (b) => setJendela(b ? "batal" : null),
        onKonfirmasi: () => router.post(route("documents.cancelRevisionB", doc.id), {}, { preserveScroll: true })
      }
    ) : null,
    doc.boleh_musnahkan ? /* @__PURE__ */ jsx(
      DialogMusnahkan,
      {
        doc,
        buka: jendela === "musnahkan",
        onUbahBuka: (b) => setJendela(b ? "musnahkan" : null)
      }
    ) : null
  ] });
}
export {
  DocumentsPublished as default
};
