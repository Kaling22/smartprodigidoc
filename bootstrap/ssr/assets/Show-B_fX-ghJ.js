import { jsxs, jsx } from "react/jsx-runtime";
import { UserMultipleIcon, InformationCircleIcon, CheckmarkBadge01Icon, CheckmarkCircle01Icon, ArrowTurnBackwardIcon, FileEditIcon, LinkSquare01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, router } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, a as CardContent } from "./card-B3VJCD2M.js";
import { d as FieldGroup, F as Field, a as FieldLabel, b as FieldDescription, c as FieldError } from "./field-AJb7LB-s.js";
import { T as Textarea } from "./textarea-CFf6776c.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "sonner";
import "./label-emiz6fAI.js";
function ApprovalsShow() {
  const { document: doc, errors } = usePage().props;
  const [alasan, setAlasan] = useState("");
  const [keputusan, setKeputusan] = useState(null);
  const kirim = (decision) => router.post(route("approvals.store", doc.id), { decision, summary: alasan });
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: `Persetujuan: ${doc.nomor}`,
      remah: [
        { label: "Kembali ke antrian", href: route("approvals.index") },
        { label: doc.nomor }
      ],
      sub: /* @__PURE__ */ jsxs("span", { className: "flex flex-wrap items-center gap-1.5", children: [
        /* @__PURE__ */ jsx("span", { className: "font-medium", children: doc.judul }),
        /* @__PURE__ */ jsx("span", { className: "text-primary font-mono text-sm", children: doc.nomor }),
        /* @__PURE__ */ jsx(Badge, { variant: "outline", children: doc.dept ?? "—" })
      ] }),
      aksi: /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs("a", { href: route("documents.pdf", doc.id), target: "_blank", rel: "noopener", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FileEditIcon, strokeWidth: 1.5, className: "size-4" }),
        "Lihat PDF"
      ] }) }),
      children: [
        /* @__PURE__ */ jsxs("div", { className: "grid gap-4 lg:grid-cols-12", children: [
          /* @__PURE__ */ jsxs("div", { className: "grid content-start gap-4 lg:col-span-5", children: [
            /* @__PURE__ */ jsxs(Card, { children: [
              /* @__PURE__ */ jsx(CardHeader, { children: /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: UserMultipleIcon,
                    strokeWidth: 1.5,
                    className: "size-4",
                    "aria-hidden": "true"
                  }
                ),
                "Rantai Dokumen"
              ] }) }),
              /* @__PURE__ */ jsxs(CardContent, { children: [
                /* @__PURE__ */ jsxs("dl", { className: "grid grid-cols-3 gap-y-2 text-sm", children: [
                  /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: "Dibuat Oleh" }),
                  /* @__PURE__ */ jsxs("dd", { className: "col-span-2", children: [
                    doc.pembuat ?? "—",
                    doc.pembuatNrp ? ` (${doc.pembuatNrp})` : ""
                  ] }),
                  /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: "Ditinjau Oleh" }),
                  /* @__PURE__ */ jsx("dd", { className: "col-span-2", children: doc.peninjau ?? "—" }),
                  /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: "Departemen" }),
                  /* @__PURE__ */ jsx("dd", { className: "col-span-2", children: doc.departemen ?? "—" }),
                  /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: "Edisi / Revisi" }),
                  /* @__PURE__ */ jsxs("dd", { className: "col-span-2", children: [
                    "Edisi ",
                    doc.edisi,
                    " · Revisi ",
                    doc.noRevisi
                  ] })
                ] }),
                /* @__PURE__ */ jsxs(Alert, { className: "mt-4", children: [
                  /* @__PURE__ */ jsx(
                    HugeiconsIcon,
                    {
                      icon: InformationCircleIcon,
                      strokeWidth: 1.5,
                      className: "size-4"
                    }
                  ),
                  /* @__PURE__ */ jsxs(AlertDescription, { children: [
                    "Tinjau isi lengkap lewat tombol ",
                    /* @__PURE__ */ jsx("strong", { children: "Lihat PDF" }),
                    ". Penilaian per-item sudah dilakukan peninjau."
                  ] })
                ] })
              ] })
            ] }),
            /* @__PURE__ */ jsxs(Card, { children: [
              /* @__PURE__ */ jsx(CardHeader, { children: /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: CheckmarkBadge01Icon,
                    strokeWidth: 1.5,
                    className: "size-4",
                    "aria-hidden": "true"
                  }
                ),
                "Keputusan"
              ] }) }),
              /* @__PURE__ */ jsxs(CardContent, { className: "grid gap-4", children: [
                errors.doc_number ? /* @__PURE__ */ jsx(Alert, { variant: "destructive", children: /* @__PURE__ */ jsx(AlertDescription, { children: errors.doc_number }) }) : null,
                /* @__PURE__ */ jsx(FieldGroup, { children: /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.summary || void 0, children: [
                  /* @__PURE__ */ jsxs(FieldLabel, { htmlFor: "alasan", children: [
                    "Catatan / Alasan",
                    " ",
                    /* @__PURE__ */ jsx("span", { className: "text-muted-foreground font-normal", children: "(wajib bila mengajukan revisi)" })
                  ] }),
                  /* @__PURE__ */ jsx(
                    Textarea,
                    {
                      id: "alasan",
                      rows: 3,
                      placeholder: "Alasan revisi / catatan persetujuan...",
                      "aria-invalid": !!errors.summary,
                      value: alasan,
                      onChange: (e) => setAlasan(e.target.value)
                    }
                  ),
                  /* @__PURE__ */ jsx(FieldDescription, { children: "Catatan ini tampil di form revisi pembuat." }),
                  /* @__PURE__ */ jsx(
                    FieldError,
                    {
                      errors: errors.summary ? [{ message: errors.summary }] : void 0
                    }
                  )
                ] }) }),
                /* @__PURE__ */ jsxs("div", { className: "grid gap-2", children: [
                  /* @__PURE__ */ jsxs(Button, { onClick: () => setKeputusan("approve"), children: [
                    /* @__PURE__ */ jsx(
                      HugeiconsIcon,
                      {
                        icon: CheckmarkCircle01Icon,
                        strokeWidth: 1.5,
                        className: "size-4"
                      }
                    ),
                    "Setujui (Berlaku)"
                  ] }),
                  /* @__PURE__ */ jsxs(Button, { variant: "outline", onClick: () => setKeputusan("reject"), children: [
                    /* @__PURE__ */ jsx(
                      HugeiconsIcon,
                      {
                        icon: ArrowTurnBackwardIcon,
                        strokeWidth: 1.5,
                        className: "size-4"
                      }
                    ),
                    "Kembalikan untuk Revisi"
                  ] })
                ] })
              ] })
            ] })
          ] }),
          /* @__PURE__ */ jsxs(Card, { className: "gap-0 py-0 lg:sticky lg:top-4 lg:col-span-7 lg:self-start", children: [
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
                "Pratinjau PDF"
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
                title: `Pratinjau ${doc.nomor}`,
                src: `${route("documents.pdf", doc.id)}#toolbar=1&navpanes=0&view=Fit`,
                className: "size-full border-0"
              }
            ) })
          ] })
        ] }),
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            buka: keputusan !== null,
            onUbahBuka: (b) => b ? null : setKeputusan(null),
            judul: keputusan === "reject" ? "Kembalikan untuk Revisi?" : "Setujui Dokumen?",
            pesan: keputusan === "reject" ? "Kembalikan dokumen ke pembuat untuk revisi? Pastikan alasan/catatan sudah diisi." : "Setujui dokumen ini menjadi Berlaku? Seluruh tanda tangan akan bercap APPROVED.",
            tombolYa: keputusan === "reject" ? "Ya, kembalikan" : "Ya, setujui",
            onKonfirmasi: () => {
              if (keputusan) kirim(keputusan);
              setKeputusan(null);
            }
          }
        )
      ]
    }
  );
}
export {
  ApprovalsShow as default
};
