import { jsxs, jsx } from "react/jsx-runtime";
import { InboxIcon, ReplyIcon, RefreshCwIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, B as Badge, D as Dialog, b as DialogTrigger, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, g as DialogFooter } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError } from "./field-AJb7LB-s.js";
import { T as Textarea } from "./textarea-CFf6776c.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { b as DialogRevisi } from "./DialogAksi-D_7VYKsd.js";
import { s as saringDepartemen, P as PenyaringDokumen } from "./PenyaringDokumen-ZbRxHXvI.js";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./label-emiz6fAI.js";
import "./table-COSAIfdh.js";
import "./alert-BvkCPa_V.js";
import "./checkbox-DZTcz7W1.js";
import "./input-B9Vuz8J-.js";
import "./input-group-C5LPhh_3.js";
import "./select-Db_F_VlA.js";
function LogMasukan() {
  const { masukan, revisi, filters, departments, statusOpsi, bolehMembalas, ketersediaanPembuat } = usePage().props;
  const [revisiBuka, setRevisiBuka] = useState(null);
  const pilihan = [
    { nama: "status", label: "Status", opsi: [["", "Semua"], ...Object.entries(statusOpsi)] },
    ...departments.length > 0 ? [saringDepartemen(departments)] : []
  ];
  const kolom = [
    { judul: "No. Masukan", render: (m) => /* @__PURE__ */ jsx("span", { className: "font-mono text-sm", children: m.nomor }) },
    {
      judul: "Dokumen",
      render: (m) => m.dokumen ? /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
        /* @__PURE__ */ jsx(
          Link,
          {
            href: route("documents.show", m.dokumen.id),
            className: "font-semibold hover:underline",
            children: m.dokumen.nomor
          }
        ),
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground block", children: m.dokumen.judul })
      ] }) : /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "—" })
    },
    { judul: "Isi", kelas: "max-w-88", render: (m) => /* @__PURE__ */ jsx(IsiMasukan, { m }) },
    { judul: "Pengirim", render: (m) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: m.oleh ?? "—" }) },
    {
      judul: "Tanggal",
      kelas: "whitespace-nowrap",
      render: (m) => /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
        m.waktu,
        " WITA"
      ] })
    },
    { judul: "Status", render: (m) => /* @__PURE__ */ jsx(Badge, { variant: rona(m.status), children: m.status_label }) },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (m) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        m.boleh_balas ? /* @__PURE__ */ jsx(DialogBalas, { m }) : null,
        m.boleh_revisi && m.dokumen ? /* @__PURE__ */ jsx(TombolRevisi, { id: m.dokumen.id, onBuka: setRevisiBuka }) : null
      ] })
    }
  ];
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: "Masukan Lapangan",
      sub: `Masukan orang lapangan atas dokumen Berlaku ${departments.length > 0 ? "di 7 departemen" : "di departemen Anda"}. ${bolehMembalas ? "Balas untuk menutup, atau Revisi untuk menjadikannya bahan perbaikan." : "Ditindaklanjuti penyusun dokumen (GL) atau Management Development."}`,
      children: [
        /* @__PURE__ */ jsxs(Card, { children: [
          /* @__PURE__ */ jsx(CardHeader, { className: "border-b", children: /* @__PURE__ */ jsx(
            PenyaringDokumen,
            {
              url: route("log.masukan"),
              filters,
              labelCari: "Cari (isi / nomor masukan)",
              placeholderCari: "mis. MSK-2026 atau kata di isinya...",
              pilihan
            }
          ) }),
          /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
            DataTable,
            {
              kolom,
              baris: masukan.data,
              kunci: (m) => m.id,
              kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
                /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InboxIcon, strokeWidth: 1.5, className: "size-6" }) }),
                /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada masukan lapangan" })
              ] }) })
            }
          ) }),
          /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: masukan }) })
        ] }),
        revisi.map((r) => /* @__PURE__ */ jsx(
          JendelaRevisi,
          {
            r,
            jadwal: r.pembuat_id ? ketersediaanPembuat[r.pembuat_id] ?? null : null,
            buka: revisiBuka === r.id,
            onUbahBuka: (b) => setRevisiBuka(b ? r.id : null)
          },
          r.id
        ))
      ]
    }
  );
}
function TombolRevisi({ id, onBuka }) {
  return /* @__PURE__ */ jsxs(Button, { variant: "secondary", size: "sm", onClick: () => onBuka(id), children: [
    /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RefreshCwIcon, strokeWidth: 1.5, className: "size-4" }),
    "Revisi"
  ] });
}
function JendelaRevisi({
  r,
  jadwal,
  buka,
  onUbahBuka
}) {
  return /* @__PURE__ */ jsx(
    DialogRevisi,
    {
      doc: { id: r.id, nomor: r.nomor, janji_revisi: r.janji_revisi },
      masukanRevisi: r.masukan,
      tercentang: r.tercentang,
      jadwalPembuat: jadwal,
      buka,
      onUbahBuka
    }
  );
}
function IsiMasukan({ m }) {
  return /* @__PURE__ */ jsxs("div", { className: "text-sm", children: [
    m.isi.length <= 120 ? m.isi : /* @__PURE__ */ jsxs("details", { children: [
      /* @__PURE__ */ jsxs("summary", { className: "cursor-pointer list-none [&::-webkit-details-marker]:hidden", children: [
        m.isi.slice(0, 120),
        "…",
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs", children: " selengkapnya" })
      ] }),
      /* @__PURE__ */ jsx("div", { className: "mt-1", children: m.isi })
    ] }),
    m.balasan ? (
      /* Pita kutipan, bukan kartu — bentuknya disamakan dengan
         `v2/DaftarMasukan` supaya balasan tampak sama di mana pun ia
         muncul. Karena itu `rounded-r` di sini TIDAK ikut naik ke
         `rounded-2xl`. */
      /* @__PURE__ */ jsxs("div", { className: "bg-muted mt-2 rounded-r border-l-4 px-2 py-1", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: ReplyIcon,
            strokeWidth: 1.5,
            className: "inline size-3.5",
            "aria-hidden": "true"
          }
        ),
        " ",
        m.balasan,
        m.pembalas ? /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
          " — ",
          m.pembalas
        ] }) : null
      ] })
    ) : null
  ] });
}
function rona(status) {
  if (status === "baru") return "destructive";
  if (status === "diadopsi") return "default";
  return "secondary";
}
function DialogBalas({ m }) {
  const [buka, setBuka] = useState(false);
  const [konfirmasi, setKonfirmasi] = useState(false);
  const form = useForm({ balasan: "" });
  function ajukan(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  return /* @__PURE__ */ jsxs(Dialog, { open: buka, onOpenChange: setBuka, children: [
    /* @__PURE__ */ jsx(DialogTrigger, { asChild: true, children: /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ReplyIcon, strokeWidth: 1.5, className: "size-4" }),
      "Balas"
    ] }) }),
    /* @__PURE__ */ jsxs(DialogContent, { children: [
      /* @__PURE__ */ jsxs(DialogHeader, { children: [
        /* @__PURE__ */ jsxs(DialogTitle, { className: "flex items-center gap-1.5", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ReplyIcon, strokeWidth: 1.5, className: "size-4", "aria-hidden": "true" }),
          "Balas — ",
          /* @__PURE__ */ jsx("span", { className: "font-mono", children: m.nomor })
        ] }),
        /* @__PURE__ */ jsx(DialogDescription, { children: "Masukan ini akan DITUTUP; pengirimnya diberi tahu." })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "rounded-2xl border p-2 text-sm", children: [
        m.isi,
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground block text-xs", children: m.oleh ?? "—" })
      ] }),
      /* @__PURE__ */ jsx("form", { id: `balas-${m.id}`, onSubmit: ajukan, children: /* @__PURE__ */ jsx(FieldGroup, { children: /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.balasan || void 0, children: [
        /* @__PURE__ */ jsxs(FieldLabel, { htmlFor: `balasan-${m.id}`, children: [
          "Balasan untuk pengirim ",
          /* @__PURE__ */ jsx("span", { className: "text-destructive", children: "*" })
        ] }),
        /* @__PURE__ */ jsx(
          Textarea,
          {
            id: `balasan-${m.id}`,
            rows: 3,
            maxLength: 2e3,
            required: true,
            placeholder: "mis. sudah sesuai standar terbaru.",
            value: form.data.balasan,
            "aria-invalid": !!form.errors.balasan,
            onChange: (e) => form.setData("balasan", e.target.value)
          }
        ),
        /* @__PURE__ */ jsx(
          FieldError,
          {
            errors: form.errors.balasan ? [{ message: form.errors.balasan }] : void 0
          }
        )
      ] }) }) }),
      /* @__PURE__ */ jsxs(DialogFooter, { children: [
        /* @__PURE__ */ jsx(Button, { type: "button", variant: "ghost", onClick: () => setBuka(false), children: "Batal" }),
        /* @__PURE__ */ jsxs(
          Button,
          {
            type: "submit",
            form: `balas-${m.id}`,
            variant: "secondary",
            disabled: form.processing,
            children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ReplyIcon, strokeWidth: 1.5, className: "size-4" }),
              "Balas & Tutup"
            ]
          }
        )
      ] }),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Balas & Tutup?",
          pesan: `Tutup masukan ${m.nomor} tanpa revisi dan kirim balasan ke pengirimnya?`,
          tombolYa: "Ya, kirim balasan",
          buka: konfirmasi,
          onUbahBuka: setKonfirmasi,
          onKonfirmasi: () => form.post(route("documents.feedback.respond", m.id), {
            preserveScroll: true,
            onSuccess: () => {
              setBuka(false);
              form.reset();
            }
          })
        }
      )
    ] })
  ] });
}
export {
  LogMasukan as default
};
