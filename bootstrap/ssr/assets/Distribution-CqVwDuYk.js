import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { Alert02Icon, HierarchySquare01Icon, ChevronUpIcon, ChevronDownIcon, UserMultipleIcon, RadioIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { P as PitaCakupan } from "./PitaCakupan-D6HiIzDA.js";
import { D as DataTable } from "./DataTable-Cclynbfl.js";
import { N as NomorDokumen } from "./NomorDokumen-DTGTvDys.js";
import { P as PenyaringDokumen, a as saringJenis, s as saringDepartemen } from "./PenyaringDokumen-ZbRxHXvI.js";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./progress-CJN4SIW1.js";
import "./table-COSAIfdh.js";
import "./input-B9Vuz8J-.js";
import "./input-group-C5LPhh_3.js";
import "./label-emiz6fAI.js";
import "./select-Db_F_VlA.js";
function DocumentsDistribution() {
  const props = usePage().props;
  const adalahInformasi = props.sumber === "informasi";
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: `Distribusi ${adalahInformasi ? "Informasi" : "Dokumen"}`,
      sub: /* @__PURE__ */ jsxs(Fragment, { children: [
        adalahInformasi ? /* @__PURE__ */ jsxs(Fragment, { children: [
          "Seberapa jauh kebijakan, memo, dan poster sudah dibuka orang",
          " ",
          props.canAll ? "di 7 departemen" : "di departemen Anda",
          "."
        ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
          "Seberapa jauh dokumen Berlaku sudah dibaca orang",
          " ",
          props.canAll ? "di 7 departemen" : "di departemen Anda",
          "."
        ] }),
        " ",
        "Diurutkan dari yang ",
        /* @__PURE__ */ jsx("strong", { children: "paling sedikit" }),
        " dibaca."
      ] }),
      aksi: (
        /* Saklar sumber = dua TAUTAN biasa, bukan saklar klien: halaman
           ini toh dimuat ulang saat menyaring, dan tautannya bisa
           ditandai & dibagikan. */
        /* @__PURE__ */ jsxs("nav", { className: "flex gap-1", "aria-label": "Sumber distribusi", children: [
          /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", variant: adalahInformasi ? "outline" : "secondary", children: /* @__PURE__ */ jsx(Link, { href: route("documents.distribution"), children: "Dokumen Mutu" }) }),
          /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", variant: adalahInformasi ? "secondary" : "outline", children: /* @__PURE__ */ jsx(Link, { href: route("documents.distribution", { sumber: "informasi" }), children: "Informasi" }) })
        ] })
      ),
      children: [
        props.rendah > 0 ? /* @__PURE__ */ jsxs("p", { className: "border-chart-3/40 bg-chart-3/10 flex items-center gap-2 rounded-2xl border px-3 py-2 text-sm", children: [
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: Alert02Icon,
              strokeWidth: 1.5,
              className: "size-4 shrink-0",
              "aria-hidden": "true"
            }
          ),
          /* @__PURE__ */ jsxs("span", { children: [
            /* @__PURE__ */ jsxs("strong", { children: [
              props.rendah,
              " ",
              adalahInformasi ? "informasi" : "dokumen"
            ] }),
            " ",
            "baru dibaca kurang dari separuh orang yang seharusnya tahu."
          ] })
        ] }) : null,
        props.sumber === "informasi" ? /* @__PURE__ */ jsx(TabelInformasi, { ...props }) : /* @__PURE__ */ jsx(TabelDokumen, { ...props })
      ]
    }
  );
}
function TabelDokumen(props) {
  const pilihan = [saringJenis(props.jenis)];
  if (props.departments.length > 0) {
    pilihan.push(saringDepartemen(props.departments));
  }
  const kolom = [
    { judul: "No. Dokumen", urut: "nomor", render: (d) => /* @__PURE__ */ jsx(NomorDokumen, { doc: d }) },
    { judul: "Judul", render: (d) => d.judul },
    { judul: "Jenis", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.jenis ?? "—" }) },
    { judul: "Dept", render: (d) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: d.dept ?? "—" }) },
    { judul: "Cakupan", kelas: "min-w-44", render: (d) => /* @__PURE__ */ jsx(PitaCakupan, { c: d.cakupan }) },
    {
      judul: "Unduhan",
      kelas: "text-center",
      // Unduhan dipisah dari cakupan: cakupan = berapa ORANG, unduhan =
      // berapa KALI. Dokumen yang dibuka 40 kali oleh 2 orang belum
      // tersebar.
      render: (d) => /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-sm", children: [
        d.cakupan.unduhan,
        "×"
      ] })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (d) => /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", variant: "outline", title: "Rincian pembaca", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.show", d.id), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: UserMultipleIcon, strokeWidth: 1.5, className: "size-4" }),
        "Rincian"
      ] }) })
    }
  ];
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
      PenyaringDokumen,
      {
        url: route("documents.distribution"),
        filters: props.filters,
        prefix: props.prefix,
        pilihan
      }
    ) }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
      DataTable,
      {
        kolom,
        baris: props.documents,
        kunci: (d) => d.id,
        kosong: /* @__PURE__ */ jsx(Kosong, { teks: "Belum ada dokumen Berlaku yang cocok dengan filter ini." })
      }
    ) })
  ] });
}
function TabelInformasi(props) {
  const [terbuka, setTerbuka] = useState(null);
  const kolom = [
    { judul: "Nomor", render: (i) => /* @__PURE__ */ jsx("span", { className: "font-mono text-sm", children: i.nomor }) },
    {
      judul: "Judul",
      render: (i) => /* @__PURE__ */ jsxs(Fragment, { children: [
        i.judul,
        i.revisi ? /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-sm", children: [
          " · ",
          i.revisi
        ] }) : null
      ] })
    },
    { judul: "Kategori", render: (i) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: i.kategori }) },
    { judul: "Cakupan", kelas: "min-w-44", render: (i) => /* @__PURE__ */ jsx(PitaCakupan, { c: i.cakupan }) },
    {
      judul: "Unduhan",
      kelas: "text-center",
      render: (i) => /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-sm", children: [
        i.cakupan.unduhan,
        "×"
      ] })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      // Dua tombol, dua pertanyaan berbeda: penyingkap menjawab
      // "departemen mana yang tertinggal", Rincian menjawab "siapa
      // orangnya". Rincian ada untuk SEMUA yang berwenang.
      render: (i) => /* @__PURE__ */ jsxs("div", { className: "flex justify-end gap-1", children: [
        i.perDept.length > 0 ? /* @__PURE__ */ jsxs(
          Button,
          {
            size: "sm",
            variant: "outline",
            "aria-expanded": terbuka === i.id,
            onClick: () => setTerbuka(terbuka === i.id ? null : i.id),
            children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: HierarchySquare01Icon, strokeWidth: 1.5, className: "size-4" }),
              "Per Departemen",
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: terbuka === i.id ? ChevronUpIcon : ChevronDownIcon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              )
            ]
          }
        ) : null,
        /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", variant: "outline", title: "Siapa yang sudah & belum membuka", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.rincianInformasi", i.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: UserMultipleIcon, strokeWidth: 1.5, className: "size-4" }),
          "Rincian"
        ] }) })
      ] })
    }
  ];
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
      PenyaringDokumen,
      {
        url: route("documents.distribution"),
        filters: props.filters,
        contoh: "nomor kebijakan",
        tersembunyi: { sumber: "informasi" },
        pilihan: [
          {
            nama: "kategori",
            label: "Kategori",
            opsi: [["", "Semua"], ...Object.entries(props.kategoriOpsi)]
          }
        ]
      }
    ) }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
      DataTable,
      {
        kolom,
        baris: props.informasi,
        kunci: (i) => i.id,
        kosong: /* @__PURE__ */ jsx(Kosong, { teks: "Belum ada informasi berlaku yang cocok dengan filter ini." }),
        perluas: (i) => terbuka === i.id && i.perDept.length > 0 ? /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx("div", { className: "grid gap-2 py-1 md:grid-cols-2 xl:grid-cols-3", children: i.perDept.map((d) => /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
            /* @__PURE__ */ jsx(Badge, { variant: "outline", className: "min-w-18 justify-center", children: d.dept }),
            /* @__PURE__ */ jsx("div", { className: "flex-1", children: /* @__PURE__ */ jsx(PitaCakupan, { c: d, ringkas: true }) })
          ] }, d.dept)) }),
          /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-1 text-xs", children: "Rincian ini hanya menghitung orang yang punya departemen, jadi jumlahnya bisa lebih kecil daripada angka gabungan di kolom Cakupan." })
        ] }) : null
      }
    ) })
  ] });
}
function Kosong({ teks }) {
  return /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
    /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RadioIcon, strokeWidth: 1.5, className: "size-6" }) }),
    /* @__PURE__ */ jsx(EmptyTitle, { children: teks })
  ] }) });
}
export {
  DocumentsDistribution as default
};
