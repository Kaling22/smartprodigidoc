import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { InboxIcon, Upload01Icon, ChevronRightIcon, HistoryIcon, Delete02Icon, LinkSquare01Icon, Image01Icon, File01Icon, RefreshCwIcon, FileMinusIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, I as Ikon, B as Badge, o as DropdownMenuItem, D as Dialog, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription, e as EmptyContent } from "./empty-CkAl4IHy.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { P as PenyaringDokumen } from "./PenyaringDokumen-ZbRxHXvI.js";
import { S as StripAksi } from "./StripAksi-BFgTXRBT.js";
import { c as cn } from "../uji-render.js";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "next-themes";
import "./table-COSAIfdh.js";
import "./input-B9Vuz8J-.js";
import "./input-group-C5LPhh_3.js";
import "./label-emiz6fAI.js";
import "./select-Db_F_VlA.js";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
function InformasiIndex() {
  const { kategori, label, ikon, kolom, kelola, daftar, filters, prefix } = usePage().props;
  const [terbuka, setTerbuka] = useState([]);
  const alih = (id) => setTerbuka((s) => s.includes(id) ? s.filter((x) => x !== id) : [...s, id]);
  const adaRevisi = kolom.includes("edisi");
  const adaTanggal = kolom.includes("tanggal_efektif");
  const kolomTabel = [
    {
      judul: /* @__PURE__ */ jsx("span", { className: "sr-only", children: "Riwayat" }),
      kelas: "w-10",
      render: (i) => i.riwayat.length > 0 ? /* @__PURE__ */ jsx(
        Button,
        {
          variant: "ghost",
          size: "icon",
          "aria-expanded": terbuka.includes(i.id),
          title: terbuka.includes(i.id) ? "Sembunyikan riwayat" : `Lihat ${i.riwayat.length} versi lama`,
          onClick: () => alih(i.id),
          children: /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: ChevronRightIcon,
              strokeWidth: 1.5,
              className: cn(
                "size-4 transition-transform",
                terbuka.includes(i.id) && "rotate-90"
              )
            }
          )
        }
      ) : null
    },
    { judul: "Nomor", render: (i) => /* @__PURE__ */ jsx("span", { className: "font-mono text-sm", children: i.nomor }) },
    { judul: "Judul", render: (i) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: i.judul }) },
    ...adaRevisi ? [
      {
        judul: "Edisi / Rev",
        kelas: "text-center",
        render: (i) => `${i.edisi ?? "—"} / ${i.no_revisi ?? "—"}`
      }
    ] : [],
    ...adaTanggal ? [
      {
        judul: "Tgl Efektif",
        render: (i) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: i.tanggal ?? "—" })
      }
    ] : [],
    {
      judul: "Diunggah",
      render: (i) => /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground text-sm", children: [
        i.diunggah,
        /* @__PURE__ */ jsx("span", { className: "block text-xs", children: i.oleh ?? "—" })
      ] })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (i) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        i.riwayat.length > 0 ? /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", onClick: () => alih(i.id), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: HistoryIcon, strokeWidth: 1.5, className: "size-4" }),
          "Riwayat (",
          i.riwayat.length,
          ")"
        ] }) : null,
        /* @__PURE__ */ jsx(TombolBuka, { versi: i }),
        kelola ? /* @__PURE__ */ jsx(AksiKelola, { info: i }) : null
      ] })
    }
  ];
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: label,
      sub: /* @__PURE__ */ jsxs("span", { className: "flex items-start gap-1.5", children: [
        /* @__PURE__ */ jsx(Ikon, { nama: ikon, className: "mt-0.5 size-4 shrink-0" }),
        "Dokumen informasi yang berlaku — terbuka untuk semua departemen dan semua jabatan. Versi lama tersimpan sebagai riwayat di bawah barisnya masing-masing."
      ] }),
      aksi: kelola ? /* @__PURE__ */ jsx(Button, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("informasi.create", { kategori }), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Upload01Icon, strokeWidth: 1.5, className: "size-4" }),
        "Tambah ",
        label
      ] }) }) : null,
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
          PenyaringDokumen,
          {
            url: route("informasi.index"),
            filters,
            contoh: `${prefix}-KBJ`,
            tersembunyi: { kategori }
          }
        ) }),
        /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
          DataTable,
          {
            kolom: kolomTabel,
            baris: daftar.data,
            kunci: (i) => i.id,
            perluas: (i) => terbuka.includes(i.id) && i.riwayat.length > 0 ? /* @__PURE__ */ jsx(Riwayat, { info: i, kelola }) : null,
            kosong: /* @__PURE__ */ jsxs(Empty, { className: "border-0", children: [
              /* @__PURE__ */ jsxs(EmptyHeader, { children: [
                /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: InboxIcon,
                    strokeWidth: 1.5,
                    className: "size-6"
                  }
                ) }),
                /* @__PURE__ */ jsxs(EmptyTitle, { children: [
                  "Belum ada ",
                  label
                ] }),
                /* @__PURE__ */ jsx(EmptyDescription, { children: "Coba longgarkan penyaring di atas." })
              ] }),
              kelola ? /* @__PURE__ */ jsx(EmptyContent, { children: /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("informasi.create", { kategori }), children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: Upload01Icon,
                    strokeWidth: 1.5,
                    className: "size-4"
                  }
                ),
                "Unggah yang pertama"
              ] }) }) }) : null
            ] })
          }
        ) }),
        /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: daftar }) })
      ] })
    }
  );
}
function TombolBuka({ versi, ringkas = false }) {
  return /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs("a", { href: route("informasi.file", versi.id), target: "_blank", rel: "noopener", children: [
    /* @__PURE__ */ jsx(
      HugeiconsIcon,
      {
        icon: ringkas ? LinkSquare01Icon : versi.gambar ? Image01Icon : File01Icon,
        strokeWidth: 1.5,
        className: "size-4"
      }
    ),
    "Buka"
  ] }) });
}
function AksiKelola({ info }) {
  const [hapus2, setHapus] = useState(false);
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsxs(StripAksi, { children: [
      /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("informasi.perbarui", info.id), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RefreshCwIcon, strokeWidth: 1.5, className: "size-4" }),
        "Perbarui"
      ] }) }),
      /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => setHapus(true), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }),
        "Hapus"
      ] })
    ] }),
    /* @__PURE__ */ jsx(DialogHapus, { info, buka: hapus2, onUbahBuka: setHapus })
  ] });
}
function Riwayat({ info, kelola }) {
  return /* @__PURE__ */ jsxs("div", { className: "border-border ml-1 border-l-[3px] pl-3", children: [
    /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground mb-2 flex items-center gap-1.5 text-sm font-semibold", children: [
      /* @__PURE__ */ jsx(
        HugeiconsIcon,
        {
          icon: HistoryIcon,
          strokeWidth: 1.5,
          className: "size-4",
          "aria-hidden": "true"
        }
      ),
      "Versi lama ",
      info.nomor
    ] }),
    info.riwayat.map((lama) => /* @__PURE__ */ jsxs(
      "div",
      {
        className: "flex flex-wrap items-center justify-between gap-2 border-b py-1 last:border-b-0",
        children: [
          /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
            lama.judul,
            lama.revisi ? /* @__PURE__ */ jsx(Badge, { variant: "secondary", className: "ml-2", children: lama.revisi }) : null,
            /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground block text-xs", children: [
              "Diunggah ",
              lama.diunggah_lengkap,
              " WITA · ",
              lama.oleh ?? "—"
            ] })
          ] }),
          /* @__PURE__ */ jsxs("span", { className: "flex gap-2", children: [
            /* @__PURE__ */ jsx(TombolBuka, { versi: lama, ringkas: true }),
            kelola ? /* @__PURE__ */ jsx(
              ConfirmDialog,
              {
                judul: "Hapus Versi Lama?",
                pesan: `Hapus versi lama ini dari riwayat ${info.nomor}? Versi yang sedang berlaku tidak tersentuh.`,
                tombolYa: "Ya, hapus",
                destruktif: true,
                onKonfirmasi: () => hapus(lama.id, "versi"),
                pemicu: /* @__PURE__ */ jsx(Button, { variant: "outline", size: "icon", title: "Hapus versi lama", children: /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: Delete02Icon,
                    strokeWidth: 1.5,
                    className: "size-4"
                  }
                ) })
              }
            ) : null
          ] })
        ]
      },
      lama.id
    ))
  ] });
}
function hapus(id, cakupan) {
  router.delete(route("informasi.destroy", id), {
    data: { cakupan },
    preserveScroll: true
  });
}
function DialogHapus({
  info,
  buka,
  onUbahBuka
}) {
  const jumlah = info.riwayat.length;
  const punyaRiwayat = jumlah > 0;
  return /* @__PURE__ */ jsx(Dialog, { open: buka, onOpenChange: onUbahBuka, children: /* @__PURE__ */ jsxs(DialogContent, { children: [
    /* @__PURE__ */ jsxs(DialogHeader, { children: [
      /* @__PURE__ */ jsxs(DialogTitle, { className: "flex items-center gap-1.5", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: Delete02Icon,
            strokeWidth: 1.5,
            className: "size-4",
            "aria-hidden": "true"
          }
        ),
        "Hapus ",
        info.nomor
      ] }),
      /* @__PURE__ */ jsxs(DialogDescription, { children: [
        /* @__PURE__ */ jsx("span", { className: "font-semibold", children: info.judul }),
        info.revisi ? ` — ${info.revisi}` : "",
        punyaRiwayat ? `. Nomor ini punya ${jumlah} versi lama di riwayatnya.` : ""
      ] })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "grid gap-2", children: [
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: punyaRiwayat ? "Hapus Versi Ini?" : `Hapus ${info.nomor}?`,
          pesan: punyaRiwayat ? `Versi ini dihapus, dan versi riwayat TERBARU naik menggantikannya sehingga nomor ${info.nomor} tetap punya dokumen.` : `Hapus ${info.nomor}? Nomor ini tidak punya versi lain, jadi ia hilang dari daftar sampai diunggah lagi.`,
          tombolYa: "Ya, hapus",
          destruktif: true,
          onKonfirmasi: () => {
            onUbahBuka(false);
            hapus(info.id, "versi");
          },
          pemicu: /* @__PURE__ */ jsxs(
            Button,
            {
              variant: punyaRiwayat ? "outline" : "destructive",
              className: "h-auto w-full justify-start py-2 text-left",
              children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: FileMinusIcon,
                    strokeWidth: 1.5,
                    className: "size-4"
                  }
                ),
                /* @__PURE__ */ jsxs("span", { children: [
                  /* @__PURE__ */ jsx("span", { className: "block font-semibold", children: punyaRiwayat ? "Hapus versi ini saja" : "Hapus dokumen ini" }),
                  /* @__PURE__ */ jsx("span", { className: "block text-xs font-normal opacity-80", children: punyaRiwayat ? "Riwayat terbaru naik jadi berlaku." : "Tidak ada riwayat yang ikut terhapus." })
                ] })
              ]
            }
          )
        }
      ),
      punyaRiwayat ? /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Hapus Seluruhnya?",
          pesan: `SELURUH versi bernomor ${info.nomor} dihapus — versi berlaku dan ${jumlah} versi riwayatnya. Nomor ini hilang dari daftar sampai diunggah lagi.`,
          tombolYa: "Ya, hapus semuanya",
          destruktif: true,
          onKonfirmasi: () => {
            onUbahBuka(false);
            hapus(info.id, "semua");
          },
          pemicu: /* @__PURE__ */ jsxs(
            Button,
            {
              variant: "destructive",
              className: "h-auto w-full justify-start py-2 text-left",
              children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: Delete02Icon,
                    strokeWidth: 1.5,
                    className: "size-4"
                  }
                ),
                /* @__PURE__ */ jsxs("span", { children: [
                  /* @__PURE__ */ jsx("span", { className: "block font-semibold", children: "Hapus seluruhnya, riwayat sekalian" }),
                  /* @__PURE__ */ jsxs("span", { className: "block text-xs font-normal opacity-80", children: [
                    jumlah + 1,
                    " versi dibuang."
                  ] })
                ] })
              ]
            }
          )
        }
      ) : null
    ] })
  ] }) });
}
export {
  InformasiIndex as default
};
