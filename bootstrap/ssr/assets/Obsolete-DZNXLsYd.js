import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { InboxIcon, CircleSlashIcon, ChevronDownIcon, ChevronRightIcon, ViewIcon, FileEditIcon, ArrowTurnBackwardIcon, Archive01Icon, Delete02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, B as Badge, o as DropdownMenuItem } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription } from "./empty-CkAl4IHy.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { D as DialogMusnahkan } from "./DialogAksi-D_7VYKsd.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
import { P as PenyaringDokumen, s as saringDepartemen } from "./PenyaringDokumen-ZbRxHXvI.js";
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
function DocumentsObsolete() {
  const { documents, filters, departments, prefix } = usePage().props;
  const [terbuka, setTerbuka] = useState([]);
  function alih(id) {
    setTerbuka((t) => t.includes(id) ? t.filter((x) => x !== id) : [...t, id]);
  }
  const kolom = [
    {
      judul: "No. Dokumen",
      urut: "nomor",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-1", children: [
        /* @__PURE__ */ jsx(NomorDokumen, { doc: d, dicoret: d.nomor_dilepas }),
        d.nomor_dilepas ? /* @__PURE__ */ jsx(
          Badge,
          {
            variant: "outline",
            title: "Nomor kembali ke kolam dan bisa dipakai dokumen baru",
            children: "Nomor dilepas"
          }
        ) : null
      ] })
    },
    { judul: "Judul", render: (d) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: d.judul }) },
    { judul: "Jenis", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.jenis ?? "—" }) },
    { judul: "Dept", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.dept ?? "—" }) },
    { judul: "Edisi", kelas: "text-center", render: (d) => d.edisi },
    { judul: "Revisi", urut: "revisi", kelas: "text-center", render: (d) => d.no_revisi },
    { judul: "Pembuat", render: (d) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: d.pembuat ?? "—" }) },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        d.versi.length > 1 ? /* @__PURE__ */ jsxs(
          Button,
          {
            variant: "outline",
            size: "sm",
            onClick: () => alih(d.id),
            "aria-expanded": terbuka.includes(d.id),
            children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: terbuka.includes(d.id) ? ChevronDownIcon : ChevronRightIcon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              ),
              d.versi.length,
              " versi"
            ]
          }
        ) : null,
        /* @__PURE__ */ jsx(TombolPdf, { doc: d }),
        /* @__PURE__ */ jsx(AksiBaris, { doc: d, induk: true, sendirian: d.versi.length === 1 })
      ] })
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Dokumen Tidak Berlaku",
      sub: /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-1.5", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: CircleSlashIcon,
            strokeWidth: 1.5,
            className: "size-4 shrink-0",
            "aria-hidden": "true"
          }
        ),
        "Dokumen yang sudah tidak berlaku, beserta versi lama yang digantikan revisi."
      ] }),
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("documents.obsolete"),
            filters,
            prefix,
            pilihan: departments.length > 0 ? [saringDepartemen(departments)] : []
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom,
            baris: documents.data,
            kunci: (d) => d.id,
            kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
              /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InboxIcon, strokeWidth: 1.5, className: "size-6" }) }),
              /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada dokumen tidak berlaku" }),
              /* @__PURE__ */ jsx(EmptyDescription, { children: "Coba longgarkan penyaring di atas." })
            ] }) }),
            perluas: (d) => d.versi.length > 1 && terbuka.includes(d.id) ? /* @__PURE__ */ jsx(TabelVersi, { grup: d }) : null
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: documents }) })
      ] })
    }
  );
}
function TombolPdf({ doc }) {
  return /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "icon", title: "Lihat PDF", children: /* @__PURE__ */ jsx("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }) }) });
}
function TabelVersi({ grup }) {
  return /* @__PURE__ */ jsxs("table", { className: "w-full text-sm", children: [
    /* @__PURE__ */ jsx("thead", { className: "text-muted-foreground", children: /* @__PURE__ */ jsxs("tr", { children: [
      /* @__PURE__ */ jsx("th", { className: "px-2 py-1 text-center font-normal", children: "Edisi" }),
      /* @__PURE__ */ jsx("th", { className: "px-2 py-1 text-center font-normal", children: "Revisi" }),
      /* @__PURE__ */ jsx("th", { className: "px-2 py-1 text-left font-normal", children: "Tanggal nonaktif" }),
      /* @__PURE__ */ jsx("th", { className: "px-2 py-1 text-left font-normal", children: "Sebab" }),
      /* @__PURE__ */ jsx("th", { className: "px-2 py-1 text-right font-normal", children: "Aksi" })
    ] }) }),
    /* @__PURE__ */ jsx("tbody", { children: grup.versi.map((v) => /* @__PURE__ */ jsxs("tr", { children: [
      /* @__PURE__ */ jsx("td", { className: "px-2 py-1 text-center", children: v.edisi }),
      /* @__PURE__ */ jsx("td", { className: "px-2 py-1 text-center", children: v.no_revisi }),
      /* @__PURE__ */ jsxs("td", { className: "px-2 py-1", children: [
        v.dinonaktifkan,
        " WITA"
      ] }),
      /* @__PURE__ */ jsx("td", { className: "px-2 py-1", children: /* @__PURE__ */ jsx(Badge, { variant: v.nomor_dilepas ? "outline" : "secondary", children: v.nomor_dilepas ? "Dinonaktifkan" : "Digantikan revisi" }) }),
      /* @__PURE__ */ jsx("td", { className: "px-2 py-1", children: /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(TombolPdf, { doc: v }),
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "icon", title: "Lihat detail", children: /* @__PURE__ */ jsx(Link, { href: route("documents.show", v.id), children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }) }) }),
        /* @__PURE__ */ jsx(AksiBaris, { doc: v, induk: false, sendirian: false })
      ] }) })
    ] }, v.id)) })
  ] });
}
function AksiBaris({
  doc,
  induk,
  sendirian
}) {
  const [jendela, setJendela] = useState(null);
  const bolehRollback = doc.boleh_rollback && (!induk || sendirian);
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsxs(StripAksi, { children: [
      induk ? /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("documents.show", doc.id), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ViewIcon, strokeWidth: 1.5, className: "size-4" }),
        "Lihat"
      ] }) }) : null,
      bolehRollback ? /* @__PURE__ */ jsxs(DropdownMenuItem, { onSelect: () => setJendela("rollback"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowTurnBackwardIcon, strokeWidth: 1.5, className: "size-4" }),
        "Rollback"
      ] }) : null,
      induk && doc.boleh_aktifkan ? /* @__PURE__ */ jsxs(DropdownMenuItem, { onSelect: () => setJendela("aktifkan"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowTurnBackwardIcon, strokeWidth: 1.5, className: "size-4" }),
        "Aktifkan"
      ] }) : null,
      induk && doc.boleh_arsipkan ? /* @__PURE__ */ jsxs(DropdownMenuItem, { onSelect: () => setJendela("arsipkan"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Archive01Icon, strokeWidth: 1.5, className: "size-4" }),
        "Arsipkan"
      ] }) : null,
      doc.boleh_musnahkan ? /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => setJendela("musnahkan"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }),
        "Musnahkan"
      ] }) : null
    ] }),
    bolehRollback ? /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: "Kembalikan ke Versi Ini?",
        pesan: `Isi Edisi ${doc.edisi} Revisi ${doc.no_revisi} akan disalin menjadi draft revisi baru. Dokumen yang berlaku sekarang tetap berlaku sampai revisi ini disahkan.`,
        tombolYa: "Ya, buat draft revisi",
        buka: jendela === "rollback",
        onUbahBuka: (b) => setJendela(b ? "rollback" : null),
        onKonfirmasi: () => router.post(route("documents.rollback", doc.id), {}, { preserveScroll: true })
      }
    ) : null,
    induk && doc.boleh_aktifkan ? /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: doc.nomor_bentrok ? "Nomor Bentrok" : "Aktifkan Kembali?",
        pesan: doc.nomor_bentrok ? doc.nomor_dilepas ? `Nomor ${doc.nomor_final} sudah dilepas saat dokumen ini dinonaktifkan. Aktifkan ${doc.judul} dengan NOMOR BARU (dibuat otomatis)?` : `Nomor ${doc.nomor_final} masih dipakai dokumen yang Berlaku. Aktifkan ${doc.judul} dengan NOMOR BARU (dibuat otomatis)?` : `Aktifkan kembali ${doc.nomor}? Dokumen akan kembali berstatus Berlaku.`,
        tombolYa: doc.nomor_bentrok ? "Ya, beri nomor baru" : "Ya, aktifkan",
        buka: jendela === "aktifkan",
        onUbahBuka: (b) => setJendela(b ? "aktifkan" : null),
        onKonfirmasi: () => router.post(
          route("documents.restoreObsolete", doc.id),
          doc.nomor_bentrok ? { nomor_baru: 1 } : {},
          { preserveScroll: true }
        )
      }
    ) : null,
    induk && doc.boleh_arsipkan ? /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: "Arsipkan Dokumen?",
        pesan: `Arsipkan ${doc.nomor}? Dokumen disingkirkan dari daftar, tetapi isi & nomornya tetap tersimpan dan bisa dipulihkan lewat basis data.`,
        tombolYa: "Ya, arsipkan",
        buka: jendela === "arsipkan",
        onUbahBuka: (b) => setJendela(b ? "arsipkan" : null),
        onKonfirmasi: () => router.delete(route("documents.destroy", doc.id), { preserveScroll: true })
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
  DocumentsObsolete as default
};
