import { jsxs, jsx } from "react/jsx-runtime";
import { InformationCircleIcon, CheckmarkCircle01Icon, FileEditIcon, LinkSquare01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, useForm, Link } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, b as CardHeader, c as CardTitle, e as CardFooter } from "./card-B3VJCD2M.js";
import { d as FieldGroup } from "./field-AJb7LB-s.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { b as barisLogBaru, R as RevisionLog } from "./RevisionLog-CNBSGoSn.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "sonner";
import "./label-emiz6fAI.js";
import "./input-B9Vuz8J-.js";
import "./textarea-CFf6776c.js";
function DocumentsArsipCatatan() {
  const { document: doc, baris, revisiKirim, errors } = usePage().props;
  const [konfirmasi, setKonfirmasi] = useState(false);
  const [logRows, setLogRows] = useState(
    () => Array.isArray(baris) && baris.length ? baris : [barisLogBaru()]
  );
  const form = useForm({
    edisi: doc.edisi,
    no_revisi: doc.no_revisi
  });
  const kirim = () => {
    form.transform((data) => ({ ...data, sections: { catatan_revisi: logRows } }));
    form.post(route("documents.arsip.catatan.store", doc.id));
  };
  const daftarGalat = Object.values(errors ?? {});
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: `Catatan Revisi Dokumen Lama: ${doc.nomor}`,
      sub: /* @__PURE__ */ jsxs("span", { className: "flex flex-wrap items-center gap-1.5", children: [
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: doc.nomor }),
        " — ",
        doc.judul,
        /* @__PURE__ */ jsx(Badge, { variant: "outline", children: doc.jenis }),
        /* @__PURE__ */ jsx(Badge, { variant: "outline", children: doc.dept })
      ] }),
      children: [
        /* @__PURE__ */ jsx("h2", { className: "text-lg font-bold", children: "Lembar Catatan Revisi" }),
        /* @__PURE__ */ jsxs(Alert, { children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsxs(AlertDescription, { children: [
            "Dokumen ini ",
            /* @__PURE__ */ jsx("strong", { children: "sudah Berlaku" }),
            ". Sesudah lembar ini tersimpan, isinya diketik ulang di wizard — berkas yang Anda unggah",
            " ",
            /* @__PURE__ */ jsx("strong", { children: "tak pernah ditimpa" }),
            " dan tetap jadi rujukan."
          ] })
        ] }),
        daftarGalat.length > 0 ? /* @__PURE__ */ jsx(Alert, { variant: "destructive", children: /* @__PURE__ */ jsx(AlertDescription, { children: /* @__PURE__ */ jsx("ul", { className: "list-disc pl-4", children: daftarGalat.map((e) => /* @__PURE__ */ jsx("li", { children: e }, e)) }) }) }) : null,
        /* @__PURE__ */ jsxs(
          "form",
          {
            onSubmit: (e) => {
              e.preventDefault();
              setKonfirmasi(true);
            },
            className: "grid gap-4 lg:grid-cols-12",
            children: [
              /* @__PURE__ */ jsx(Card, { className: "lg:col-span-7", children: /* @__PURE__ */ jsxs(CardContent, { children: [
                /* @__PURE__ */ jsx(FieldGroup, { children: /* @__PURE__ */ jsx(
                  RevisionLog,
                  {
                    rows: logRows,
                    onRows: setLogRows,
                    edisi: form.data.edisi,
                    noRevisi: form.data.no_revisi,
                    onEdisi: (n) => form.setData("edisi", n),
                    onNoRevisi: (n) => form.setData("no_revisi", n),
                    judul: doc.judul,
                    revisiKirim,
                    pesan: "",
                    tanggal: null,
                    onTanggal: () => {
                    }
                  }
                ) }),
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
                    "Simpan & Salin ke Web"
                  ] }),
                  /* @__PURE__ */ jsx(Button, { asChild: true, variant: "ghost", children: /* @__PURE__ */ jsx(Link, { href: route("documents.published"), children: "Nanti Saja" }) })
                ] }),
                /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-2 text-xs", children: '"Nanti Saja" tak membatalkan apa pun — dokumennya sudah terdaftar & Berlaku. Lembar ini bisa diisi belakangan lewat menu Dokumen Berlaku, tombol Perbaiki.' })
              ] }) }),
              /* @__PURE__ */ jsxs(Card, { className: "gap-0 py-0 lg:sticky lg:top-4 lg:col-span-5 lg:self-start", children: [
                /* @__PURE__ */ jsxs(CardHeader, { className: "flex items-center justify-between border-b px-4 py-3", children: [
                  /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2 text-sm", children: [
                    /* @__PURE__ */ jsx(
                      HugeiconsIcon,
                      {
                        icon: FileEditIcon,
                        strokeWidth: 1.5,
                        className: "size-4",
                        "aria-hidden": "true"
                      }
                    ),
                    "Berkas yang diunggah"
                  ] }),
                  /* @__PURE__ */ jsx(Button, { asChild: true, size: "sm", variant: "outline", children: /* @__PURE__ */ jsxs("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: [
                    /* @__PURE__ */ jsx(
                      HugeiconsIcon,
                      {
                        icon: LinkSquare01Icon,
                        strokeWidth: 1.5,
                        className: "size-4"
                      }
                    ),
                    "Buka"
                  ] }) })
                ] }),
                /* @__PURE__ */ jsx(CardContent, { className: "h-[78vh] p-0", children: /* @__PURE__ */ jsx(
                  "iframe",
                  {
                    title: "Berkas dokumen lama",
                    src: `${route("documents.pdf", doc.id)}#toolbar=1&navpanes=0&view=Fit`,
                    className: "size-full border-0"
                  }
                ) }),
                /* @__PURE__ */ jsx(CardFooter, { className: "text-muted-foreground border-t px-4 py-3 text-xs", children: "Berkas inilah yang isinya diketik ulang di wizard — telusuri halamannya di sini sambil mengetik." })
              ] })
            ]
          }
        ),
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            buka: konfirmasi,
            onUbahBuka: setKonfirmasi,
            judul: "Lanjutkan?",
            pesan: "Dokumen akan disalin ke wizard untuk diketik ulang. Berkas unggahan tetap bisa dilihat sebagai rujukan, dan sesudah dikirim dokumen langsung Berlaku.",
            tombolYa: "Ya, lanjutkan",
            onKonfirmasi: kirim
          }
        )
      ]
    }
  );
}
export {
  DocumentsArsipCatatan as default
};
