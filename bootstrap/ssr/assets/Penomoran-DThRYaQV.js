import { jsx, jsxs } from "react/jsx-runtime";
import { HashIcon, ViewIcon, ArrowRight01Icon, ArrowLeft01Icon, FloppyDiskIcon, Alert02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, Link } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { d as FieldGroup, F as Field, a as FieldLabel, b as FieldDescription, c as FieldError } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { S as Spinner } from "./spinner-dcS4h88c.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./label-emiz6fAI.js";
function PengaturanPenomoran({ prefix, namaSite }) {
  const { data, setData, put, processing, errors } = useForm({
    prefix,
    nama_site: namaSite
  });
  const [konfirmasi, setKonfirmasi] = useState(false);
  const contohBaru = `${data.prefix.toUpperCase() || "…"}-SOP-ICTMD-01`;
  function minta(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  return /* @__PURE__ */ jsx(AppLayout, { judul: "Penomoran Dokumen", children: /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:gap-6 lg:grid-cols-12", children: [
    /* @__PURE__ */ jsx("form", { onSubmit: minta, className: "lg:col-span-7", children: /* @__PURE__ */ jsxs(Card, { children: [
      /* @__PURE__ */ jsxs(CardHeader, { children: [
        /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: HashIcon,
              strokeWidth: 1.5,
              className: "text-primary size-4",
              "aria-hidden": "true"
            }
          ),
          "Setelan Site"
        ] }),
        /* @__PURE__ */ jsx(CardDescription, { children: "Prefix nomor dan nama site yang dipakai seluruh dokumen mutu — berlaku untuk semua jenis dokumen dan 7 departemen." })
      ] }),
      /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsxs(FieldGroup, { children: [
        /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.prefix || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "prefix", children: "Prefix Penomoran" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "prefix",
              required: true,
              maxLength: 20,
              className: "font-mono uppercase",
              value: data.prefix,
              onChange: (e) => setData("prefix", e.target.value),
              "aria-invalid": !!errors.prefix
            }
          ),
          /* @__PURE__ */ jsx(FieldDescription, { children: "Huruf, angka, dan garis pisah tunggal. Huruf kecil dinaikkan otomatis." }),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: errors.prefix ? [{ message: errors.prefix }] : void 0
            }
          )
        ] }),
        /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.nama_site || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "nama_site", children: "Nama Site" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "nama_site",
              required: true,
              maxLength: 60,
              className: "uppercase",
              value: data.nama_site,
              onChange: (e) => setData("nama_site", e.target.value),
              "aria-invalid": !!errors.nama_site
            }
          ),
          /* @__PURE__ */ jsxs(FieldDescription, { children: [
            "Tercetak pada judul daftar induk & kop ekspor:",
            " ",
            /* @__PURE__ */ jsxs("span", { className: "font-mono", children: [
              "DAFTAR INDUK DOKUMEN SOP PPA SITE",
              " ",
              data.nama_site.toUpperCase() || "…"
            ] })
          ] }),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: errors.nama_site ? [{ message: errors.nama_site }] : void 0
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "bg-muted/40 grid gap-2 rounded-2xl border p-3", children: [
          /* @__PURE__ */ jsxs("div", { className: "text-muted-foreground flex items-center gap-2 text-sm font-medium", children: [
            /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: ViewIcon,
                strokeWidth: 1.5,
                className: "size-4",
                "aria-hidden": "true"
              }
            ),
            "Pratinjau nomor dokumen baru"
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-3", children: [
            /* @__PURE__ */ jsxs("div", { children: [
              /* @__PURE__ */ jsx("div", { className: "text-muted-foreground text-[.72rem]", children: "Sekarang" }),
              /* @__PURE__ */ jsxs("code", { className: "text-muted-foreground text-sm", children: [
                prefix,
                "-SOP-ICTMD-01"
              ] })
            ] }),
            /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: ArrowRight01Icon,
                strokeWidth: 1.5,
                className: "text-muted-foreground size-4",
                "aria-hidden": "true"
              }
            ),
            /* @__PURE__ */ jsxs("div", { children: [
              /* @__PURE__ */ jsx("div", { className: "text-muted-foreground text-[.72rem]", children: "Setelah disimpan" }),
              /* @__PURE__ */ jsx("code", { className: "text-sm font-medium", children: contohBaru })
            ] })
          ] })
        ] })
      ] }) }),
      /* @__PURE__ */ jsxs(CardFooter, { className: "justify-end gap-2", children: [
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "ghost", children: /* @__PURE__ */ jsxs(Link, { href: route("dashboard"), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeft01Icon, strokeWidth: 1.5, className: "size-4" }),
          "Batal"
        ] }) }),
        /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
          processing ? /* @__PURE__ */ jsx(Spinner, {}) : /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FloppyDiskIcon, strokeWidth: 1.5, className: "size-4" }),
          "Simpan"
        ] }),
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            judul: "Ubah penomoran site?",
            pesan: "Setelan ini hanya berlaku untuk dokumen yang dibuat SETELAH disimpan. Nomor dokumen yang sudah ada tidak akan berubah.",
            tombolYa: "Ya, simpan",
            buka: konfirmasi,
            onUbahBuka: setKonfirmasi,
            onKonfirmasi: () => put(route("pengaturan.penomoran.simpan"))
          }
        )
      ] })
    ] }) }),
    /* @__PURE__ */ jsxs(Card, { className: "bg-muted/30 lg:col-span-5", children: [
      /* @__PURE__ */ jsx(CardHeader, { children: /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2 text-base", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: Alert02Icon,
            strokeWidth: 1.5,
            className: "text-chart-3 size-4",
            "aria-hidden": "true"
          }
        ),
        "Yang perlu diketahui"
      ] }) }),
      /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsxs("ul", { className: "text-muted-foreground grid list-disc gap-3 pl-4 text-sm", children: [
        /* @__PURE__ */ jsxs("li", { children: [
          "Setelan ini ",
          /* @__PURE__ */ jsx("strong", { children: "hanya berlaku untuk dokumen baru" }),
          ". Nomor dokumen yang sudah dibuat — apalagi yang sudah Berlaku — tidak ditulis ulang."
        ] }),
        /* @__PURE__ */ jsxs("li", { children: [
          "Penomoran ulang surut memang ",
          /* @__PURE__ */ jsx("strong", { children: "tidak disediakan" }),
          ": nomor dokumen mutu sudah beredar di luar sistem (laporan audit, dokumen lain yang merujuknya, cetakan di lapangan). Menulisnya ulang membuat rujukan itu menunjuk ke sesuatu yang tak lagi bernama sama."
        ] }),
        /* @__PURE__ */ jsxs("li", { children: [
          "Karena itu daftar induk akan memuat",
          " ",
          /* @__PURE__ */ jsx("strong", { children: "dua pola nomor sekaligus" }),
          " selama masa peralihan. Itu perilaku yang benar, bukan kesalahan."
        ] }),
        /* @__PURE__ */ jsxs("li", { children: [
          "Konsekuensinya: dokumen berprefix lama akan berlencana",
          " ",
          /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: "Nomor Lama" }),
          " — sebab lencana itu memang berarti “di luar pola yang berlaku sekarang”. Isinya tidak berubah dan tetap bisa dibuka seperti biasa."
        ] }),
        /* @__PURE__ */ jsxs("li", { children: [
          "Perubahan dicatat di",
          " ",
          /* @__PURE__ */ jsx(Link, { href: route("audit.index"), className: "underline", children: "Audit Log" }),
          " ",
          "lengkap dengan nilai sebelum & sesudahnya."
        ] })
      ] }) })
    ] })
  ] }) });
}
export {
  PengaturanPenomoran as default
};
