import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { RefreshCwIcon, Upload01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, Link } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { d as FieldGroup, F as Field, a as FieldLabel, b as FieldDescription, c as FieldError } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
function FormInformasi({
  kategori,
  label,
  kolom,
  ekstensi,
  prefix,
  edisiBaru,
  revisiBaru,
  induk
}) {
  const { data, setData, post, processing, errors } = useForm({
    kategori: induk ? "" : kategori,
    nomor: "",
    judul: induk?.judul ?? "",
    edisi: String(edisiBaru),
    no_revisi: String(revisiBaru),
    tanggal_efektif: induk?.tanggal_input ?? "",
    berkas: null
  });
  const [konfirmasi, setKonfirmasi] = useState(false);
  const punya = (k) => kolom.includes(k);
  const daftarGalat = Object.values(errors).filter(Boolean);
  function kirim() {
    post(
      induk ? route("informasi.perbarui.store", induk.id) : route("informasi.store"),
      { forceFormData: true }
    );
  }
  function ajukan(e) {
    e.preventDefault();
    if (induk) {
      setKonfirmasi(true);
      return;
    }
    kirim();
  }
  return /* @__PURE__ */ jsxs("form", { onSubmit: ajukan, className: "grid gap-6", children: [
    daftarGalat.length > 0 ? /* @__PURE__ */ jsx(Alert, { variant: "destructive", children: /* @__PURE__ */ jsx(AlertDescription, { children: /* @__PURE__ */ jsx("ul", { className: "list-disc pl-4", children: daftarGalat.map((pesan) => /* @__PURE__ */ jsx("li", { children: pesan }, pesan)) }) }) }) : null,
    /* @__PURE__ */ jsxs(FieldGroup, { children: [
      /* @__PURE__ */ jsxs(Field, { children: [
        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "kategori", children: "Kategori" }),
        /* @__PURE__ */ jsx(Input, { id: "kategori", value: label, readOnly: true, className: "bg-muted" })
      ] }),
      /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.nomor || void 0, children: [
        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "nomor", children: "Nomor Dokumen" }),
        induk ? /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx(Input, { id: "nomor", value: induk.nomor, readOnly: true, className: "bg-muted font-mono" }),
          /* @__PURE__ */ jsx(FieldDescription, { children: "Nomor diwarisi dari versi yang berlaku — versi baru memang bernomor sama." })
        ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "nomor",
              required: true,
              className: "font-mono",
              placeholder: `mis. ${prefix}-KBJ-01`,
              value: data.nomor,
              "aria-invalid": !!errors.nomor,
              onChange: (e) => setData("nomor", e.target.value)
            }
          ),
          /* @__PURE__ */ jsxs(FieldDescription, { children: [
            "Sudah ada dokumen bernomor ini? Pakai tombol ",
            /* @__PURE__ */ jsx("strong", { children: "Perbarui" }),
            " pada barisnya, bukan Tambah."
          ] })
        ] }),
        /* @__PURE__ */ jsx(FieldError, { errors: errors.nomor ? [{ message: errors.nomor }] : void 0 })
      ] }),
      /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.judul || void 0, children: [
        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "judul", children: "Judul" }),
        /* @__PURE__ */ jsx(
          Input,
          {
            id: "judul",
            required: true,
            value: data.judul,
            "aria-invalid": !!errors.judul,
            onChange: (e) => setData("judul", e.target.value)
          }
        ),
        /* @__PURE__ */ jsx(FieldError, { errors: errors.judul ? [{ message: errors.judul }] : void 0 })
      ] }),
      kolom.length > 0 ? /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:grid-cols-3", children: [
        punya("edisi") ? /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.edisi || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "edisi", children: "Edisi" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "edisi",
              name: "edisi",
              type: "number",
              min: 1,
              max: 99,
              required: true,
              value: data.edisi,
              "aria-invalid": !!errors.edisi,
              onChange: (e) => setData("edisi", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(FieldError, { errors: errors.edisi ? [{ message: errors.edisi }] : void 0 })
        ] }) : null,
        punya("no_revisi") ? /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.no_revisi || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "no_revisi", children: "No. Revisi" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "no_revisi",
              name: "no_revisi",
              type: "number",
              min: 0,
              max: 4,
              required: true,
              value: data.no_revisi,
              "aria-invalid": !!errors.no_revisi,
              onChange: (e) => setData("no_revisi", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: errors.no_revisi ? [{ message: errors.no_revisi }] : void 0
            }
          )
        ] }) : null,
        punya("tanggal_efektif") ? /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.tanggal_efektif || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "tanggal_efektif", children: "Tanggal Efektif" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "tanggal_efektif",
              type: "date",
              required: true,
              value: data.tanggal_efektif,
              "aria-invalid": !!errors.tanggal_efektif,
              onChange: (e) => setData("tanggal_efektif", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: errors.tanggal_efektif ? [{ message: errors.tanggal_efektif }] : void 0
            }
          )
        ] }) : null
      ] }) : null,
      /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.berkas || void 0, children: [
        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "berkas", children: "Berkas" }),
        /* @__PURE__ */ jsx(
          Input,
          {
            id: "berkas",
            type: "file",
            required: true,
            accept: ekstensi.map((e) => `.${e}`).join(","),
            "aria-invalid": !!errors.berkas,
            onChange: (e) => setData("berkas", e.target.files?.[0] ?? null)
          }
        ),
        /* @__PURE__ */ jsxs(FieldDescription, { children: [
          "Format: ",
          /* @__PURE__ */ jsx("strong", { children: ekstensi.join(", ").toUpperCase() }),
          ". Maksimal",
          " ",
          /* @__PURE__ */ jsx("strong", { children: "40 MB" }),
          " (batas server). Berkas disimpan privat — hanya bisa dibuka pengguna yang sudah masuk."
        ] }),
        /* @__PURE__ */ jsx(FieldError, { errors: errors.berkas ? [{ message: errors.berkas }] : void 0 })
      ] })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap gap-2", children: [
      /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: induk ? RefreshCwIcon : Upload01Icon,
            strokeWidth: 1.5,
            className: "size-4"
          }
        ),
        induk ? "Perbarui & Berlakukan" : "Unggah"
      ] }),
      /* @__PURE__ */ jsx(Button, { asChild: true, variant: "ghost", children: /* @__PURE__ */ jsx(Link, { href: route("informasi.index", { kategori }), children: "Batal" }) })
    ] }),
    induk ? /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: `Perbarui ${induk.nomor}?`,
        pesan: /* @__PURE__ */ jsxs(Fragment, { children: [
          "Versi yang sekarang berlaku (",
          induk.revisi ?? induk.judul,
          ") akan dipindahkan ke Riwayat, dan versi baru inilah yang ditampilkan dengan nomor ",
          induk.nomor,
          punya("no_revisi") ? ` — akan menjadi Edisi ${edisiBaru} Rev ${revisiBaru}` : "",
          "."
        ] }),
        tombolYa: "Ya, perbarui",
        buka: konfirmasi,
        onUbahBuka: setKonfirmasi,
        onKonfirmasi: kirim
      }
    ) : null
  ] });
}
export {
  FormInformasi as F
};
