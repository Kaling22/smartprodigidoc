import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { FileEditIcon, CheckmarkCircle01Icon, ArrowTurnBackwardIcon, InformationCircleIcon, ArrowLeft01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, useForm, Link } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, b as CardHeader, c as CardTitle } from "./card-B3VJCD2M.js";
import { d as FieldGroup, F as Field, a as FieldLabel, b as FieldDescription, c as FieldError } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { A as AppLayout } from "./AppLayout-C74XVGrz.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "./label-emiz6fAI.js";
import "sonner";
function DocumentsArsipEdit() {
  const { document: doc, errors } = usePage().props;
  const [konfirmasi, setKonfirmasi] = useState(false);
  const form = useForm({
    doc_number: doc.nomor,
    title: doc.judul,
    edisi: doc.edisi,
    no_revisi: doc.no_revisi,
    tanggal_efektif: doc.tanggal_efektif ?? "",
    berkas: null
  });
  const kirim = () => form.put(route("documents.arsip.update", doc.id), { forceFormData: true });
  const daftarGalat = Object.values(errors ?? {});
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: `Perbaiki Dokumen Lama: ${doc.nomor}`,
      sub: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: doc.nomor }),
        " — ",
        doc.judul
      ] }),
      aksi: /* @__PURE__ */ jsx(Button, { asChild: true, variant: "ghost", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.published"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowLeft01Icon, strokeWidth: 1.5, className: "size-4" }),
        "Kembali"
      ] }) }),
      children: [
        /* @__PURE__ */ jsx("h2", { className: "text-lg font-bold", children: "Perbaiki Dokumen Lama" }),
        /* @__PURE__ */ jsxs("div", { className: "grid gap-4 lg:grid-cols-3", children: [
          /* @__PURE__ */ jsx(Card, { className: "lg:col-span-2", children: /* @__PURE__ */ jsxs(CardContent, { children: [
            daftarGalat.length > 0 ? /* @__PURE__ */ jsx(Alert, { variant: "destructive", className: "mb-6", children: /* @__PURE__ */ jsx(AlertDescription, { children: /* @__PURE__ */ jsx("ul", { className: "list-disc pl-4", children: daftarGalat.map((e) => /* @__PURE__ */ jsx("li", { children: e }, e)) }) }) }) : null,
            /* @__PURE__ */ jsxs(
              "form",
              {
                onSubmit: (e) => {
                  e.preventDefault();
                  setKonfirmasi(true);
                },
                children: [
                  /* @__PURE__ */ jsxs(FieldGroup, { children: [
                    /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:grid-cols-2", children: [
                      /* @__PURE__ */ jsxs(Field, { children: [
                        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "jenis", children: "Jenis Dokumen" }),
                        /* @__PURE__ */ jsx(
                          Input,
                          {
                            id: "jenis",
                            value: `${doc.jenis} — ${doc.jenis_nama}`,
                            readOnly: true,
                            disabled: true,
                            className: "bg-muted"
                          }
                        )
                      ] }),
                      /* @__PURE__ */ jsxs(Field, { children: [
                        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "dept", children: "Departemen" }),
                        /* @__PURE__ */ jsx(
                          Input,
                          {
                            id: "dept",
                            value: `${doc.dept} — ${doc.dept_nama}`,
                            readOnly: true,
                            disabled: true,
                            className: "bg-muted"
                          }
                        )
                      ] })
                    ] }),
                    /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors?.doc_number || void 0, children: [
                      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "doc_number", children: "Nomor Dokumen" }),
                      /* @__PURE__ */ jsx(
                        Input,
                        {
                          id: "doc_number",
                          name: "doc_number",
                          required: true,
                          className: "font-mono",
                          value: form.data.doc_number,
                          "aria-invalid": !!errors?.doc_number,
                          onChange: (e) => form.setData("doc_number", e.target.value)
                        }
                      ),
                      /* @__PURE__ */ jsxs(FieldDescription, { children: [
                        "Nomor lama diterima apa adanya; yang di luar pola resmi ditandai badge ",
                        /* @__PURE__ */ jsx("strong", { children: "Nomor Lama" }),
                        "."
                      ] }),
                      /* @__PURE__ */ jsx(
                        FieldError,
                        {
                          errors: errors?.doc_number ? [{ message: errors.doc_number }] : void 0
                        }
                      )
                    ] }),
                    /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors?.title || void 0, children: [
                      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "title", children: "Judul Dokumen" }),
                      /* @__PURE__ */ jsx(
                        Input,
                        {
                          id: "title",
                          name: "title",
                          required: true,
                          value: form.data.title,
                          "aria-invalid": !!errors?.title,
                          onChange: (e) => form.setData("title", e.target.value)
                        }
                      ),
                      /* @__PURE__ */ jsx(
                        FieldError,
                        {
                          errors: errors?.title ? [{ message: errors.title }] : void 0
                        }
                      )
                    ] }),
                    /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:grid-cols-3", children: [
                      /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors?.edisi || void 0, children: [
                        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "edisi", children: "Edisi" }),
                        /* @__PURE__ */ jsx(
                          Input,
                          {
                            id: "edisi",
                            name: "edisi",
                            type: "number",
                            min: 1,
                            max: 99,
                            value: form.data.edisi,
                            "aria-invalid": !!errors?.edisi,
                            onChange: (e) => form.setData("edisi", Number(e.target.value))
                          }
                        ),
                        /* @__PURE__ */ jsx(
                          FieldError,
                          {
                            errors: errors?.edisi ? [{ message: errors.edisi }] : void 0
                          }
                        )
                      ] }),
                      /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors?.no_revisi || void 0, children: [
                        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "no_revisi", children: "No. Revisi" }),
                        /* @__PURE__ */ jsx(
                          Input,
                          {
                            id: "no_revisi",
                            name: "no_revisi",
                            type: "number",
                            min: 0,
                            max: 4,
                            value: form.data.no_revisi,
                            "aria-invalid": !!errors?.no_revisi,
                            onChange: (e) => form.setData("no_revisi", Number(e.target.value))
                          }
                        ),
                        /* @__PURE__ */ jsx(
                          FieldError,
                          {
                            errors: errors?.no_revisi ? [{ message: errors.no_revisi }] : void 0
                          }
                        )
                      ] }),
                      /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors?.tanggal_efektif || void 0, children: [
                        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "tanggal_efektif", children: "Tanggal Efektif" }),
                        /* @__PURE__ */ jsx(
                          Input,
                          {
                            id: "tanggal_efektif",
                            name: "tanggal_efektif",
                            type: "date",
                            value: form.data.tanggal_efektif,
                            "aria-invalid": !!errors?.tanggal_efektif,
                            onChange: (e) => form.setData("tanggal_efektif", e.target.value)
                          }
                        ),
                        /* @__PURE__ */ jsx(
                          FieldError,
                          {
                            errors: errors?.tanggal_efektif ? [{ message: errors.tanggal_efektif }] : void 0
                          }
                        )
                      ] })
                    ] }),
                    /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors?.berkas || void 0, children: [
                      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "berkas", children: "Ganti Berkas PDF" }),
                      /* @__PURE__ */ jsx(
                        Input,
                        {
                          id: "berkas",
                          name: "berkas",
                          type: "file",
                          accept: "application/pdf",
                          "aria-invalid": !!errors?.berkas,
                          onChange: (e) => form.setData("berkas", e.target.files?.[0] ?? null)
                        }
                      ),
                      /* @__PURE__ */ jsxs(FieldDescription, { children: [
                        "Kosongkan bila berkasnya tidak diganti. PDF saja, maksimal",
                        " ",
                        /* @__PURE__ */ jsx("strong", { children: "40 MB" }),
                        ".",
                        " ",
                        /* @__PURE__ */ jsxs(
                          "a",
                          {
                            href: route("documents.pdf", doc.id),
                            target: "_blank",
                            rel: "noopener",
                            className: "text-foreground inline-flex items-center gap-1 underline",
                            children: [
                              /* @__PURE__ */ jsx(
                                HugeiconsIcon,
                                {
                                  icon: FileEditIcon,
                                  strokeWidth: 1.5,
                                  className: "size-3.5"
                                }
                              ),
                              "Lihat berkas sekarang"
                            ]
                          }
                        )
                      ] }),
                      /* @__PURE__ */ jsx(
                        FieldError,
                        {
                          errors: errors?.berkas ? [{ message: errors.berkas }] : void 0
                        }
                      )
                    ] })
                  ] }),
                  /* @__PURE__ */ jsxs("div", { className: "mt-6 flex flex-wrap gap-2", children: [
                    /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: form.processing, children: [
                      /* @__PURE__ */ jsx(
                        HugeiconsIcon,
                        {
                          icon: CheckmarkCircle01Icon,
                          strokeWidth: 1.5,
                          className: "size-4"
                        }
                      ),
                      "Simpan Perubahan"
                    ] }),
                    doc.berlembar_revisi ? /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", children: /* @__PURE__ */ jsxs(Link, { href: route("documents.arsip.catatan", doc.id), children: [
                      /* @__PURE__ */ jsx(
                        HugeiconsIcon,
                        {
                          icon: ArrowTurnBackwardIcon,
                          strokeWidth: 1.5,
                          className: "size-4"
                        }
                      ),
                      "Lembar Catatan Revisi"
                    ] }) }) : null,
                    /* @__PURE__ */ jsx(Button, { asChild: true, variant: "ghost", children: /* @__PURE__ */ jsx(Link, { href: route("documents.published"), children: "Batal" }) })
                  ] })
                ]
              }
            ),
            /* @__PURE__ */ jsx(
              ConfirmDialog,
              {
                buka: konfirmasi,
                onUbahBuka: setKonfirmasi,
                judul: "Simpan Perubahan?",
                pesan: "Simpan perubahan pada dokumen lama ini? Perubahannya tercatat di Audit Log.",
                tombolYa: "Ya, simpan",
                onKonfirmasi: kirim
              }
            )
          ] }) }),
          /* @__PURE__ */ jsxs(Card, { className: "bg-muted/40", children: [
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
              "Yang Perlu Diketahui"
            ] }) }),
            /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsxs("ul", { className: "text-muted-foreground list-outside list-disc space-y-1 pl-4 text-sm", children: [
              /* @__PURE__ */ jsxs("li", { children: [
                "Dokumen ini ",
                /* @__PURE__ */ jsx("strong", { children: "Berlaku" }),
                " — perbaikan di sini mengubah apa yang dilihat seluruh departemen seketika."
              ] }),
              /* @__PURE__ */ jsxs("li", { children: [
                "Setiap perubahan tercatat di ",
                /* @__PURE__ */ jsx("strong", { children: "Audit Log" }),
                " beserta nilai sebelum & sesudahnya."
              ] }),
              /* @__PURE__ */ jsx("li", { children: "Salah jenis atau departemen? Keduanya tak bisa diubah — musnahkan dokumennya lalu daftarkan ulang." }),
              /* @__PURE__ */ jsx("li", { children: "Berkas lama dihapus dari disk begitu penggantinya tersimpan." })
            ] }) })
          ] })
        ] })
      ]
    }
  );
}
export {
  DocumentsArsipEdit as default
};
