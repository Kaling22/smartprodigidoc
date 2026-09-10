import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { Archive01Icon, SparklesIcon, CircleArrowRight01Icon, InformationCircleIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, useForm, Link } from "@inertiajs/react";
import { useState, useRef } from "react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent } from "./card-B3VJCD2M.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { L as Label } from "./label-emiz6fAI.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { S as Switch } from "./switch-3nvfOMMe.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { A as AppLayout } from "./AppLayout-C74XVGrz.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "sonner";
function DocumentsCreate() {
  const {
    type,
    departments,
    defaultDept,
    canChooseDept,
    numberPreview,
    unggahanSaja,
    prefix,
    nomorSaran,
    errors
  } = usePage().props;
  const [arsip, setArsip] = useState(unggahanSaja);
  const [manual, setManual] = useState(false);
  const [konfirmasi, setKonfirmasi] = useState(false);
  const bentrokNomor = Boolean(errors?.doc_number && nomorSaran);
  const [bentrok, setBentrok] = useState(bentrokNomor);
  const nomorRef = useRef(null);
  const form = useForm({
    arsip: unggahanSaja ? 1 : 0,
    document_type_id: type.id,
    department_id: defaultDept,
    title: "",
    doc_number_manual: 0,
    doc_number: "",
    edisi: 1,
    no_revisi: 0,
    tanggal_efektif: "",
    berkas: null
  });
  const dept = departments.find((d) => d.id === form.data.department_id) ?? departments[0] ?? null;
  const opsiKirim = { onError: (e) => setBentrok(Boolean(e.doc_number)) };
  const kirim = () => form.post(arsip ? route("documents.arsip.store") : route("documents.store"), {
    forceFormData: arsip,
    ...opsiKirim
  });
  const pakaiNomorOtomatis = () => {
    setBentrok(false);
    if (arsip) {
      form.setData("doc_number", nomorSaran ?? "");
      nomorRef.current?.focus();
      return;
    }
    setManual(false);
    form.setData("doc_number_manual", 0);
    form.transform((d) => ({ ...d, doc_number_manual: 0, doc_number: "" }));
    form.post(route("documents.store"), opsiKirim);
  };
  const onSubmit = (e) => {
    e.preventDefault();
    if (arsip) setKonfirmasi(true);
    else kirim();
  };
  const daftarGalat = Object.values(errors ?? {});
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: `Dokumen Baru ${type.code}${unggahanSaja ? "" : " — Langkah 1"}`,
      remah: [{ label: "Dokumen", href: route("documents.index") }, { label: "Baru" }],
      sub: unggahanSaja ? /* @__PURE__ */ jsxs(Fragment, { children: [
        type.name,
        " didaftarkan dengan ",
        /* @__PURE__ */ jsx("strong", { children: "mengunggah berkas PDF-nya" }),
        " — tanpa pengisian berbab."
      ] }) : "Tentukan nomor, judul, dan departemen dokumen. Sudah punya berkas PDF dokumen lama? Nyalakan saklar di bawah.",
      children: /* @__PURE__ */ jsxs("div", { className: "grid gap-4 lg:grid-cols-3", children: [
        /* @__PURE__ */ jsx(Card, { className: "lg:col-span-2", children: /* @__PURE__ */ jsxs(CardContent, { children: [
          daftarGalat.length > 0 ? /* @__PURE__ */ jsx("ul", { className: "border-destructive/40 bg-destructive/10 text-destructive mb-3 list-inside list-disc rounded-xl border px-3 py-2 text-sm", children: daftarGalat.map((e) => /* @__PURE__ */ jsx("li", { children: e }, e)) }) : null,
          /* @__PURE__ */ jsxs("form", { onSubmit, children: [
            arsip ? /* @__PURE__ */ jsxs("p", { className: "border-chart-3/40 bg-chart-3/10 mb-3 flex items-start gap-2 rounded-xl border px-3 py-2 text-sm", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: Archive01Icon,
                  strokeWidth: 1.5,
                  className: "mt-0.5 size-4 shrink-0"
                }
              ),
              /* @__PURE__ */ jsxs("span", { children: [
                /* @__PURE__ */ jsx("strong", { children: unggahanSaja ? "Dokumen unggahan." : "Mode dokumen lama." }),
                " ",
                /* @__PURE__ */ jsx("strong", { children: "Langsung Berlaku" }),
                " tanpa tinjau–setuju; SH/DH diberi tahu dan pendaftarannya tercatat di Audit Log."
              ] })
            ] }) : null,
            unggahanSaja ? null : /* @__PURE__ */ jsxs("div", { className: "mb-4 flex items-center gap-2", children: [
              /* @__PURE__ */ jsx(
                Switch,
                {
                  id: "arsipToggle",
                  checked: arsip,
                  onCheckedChange: (v) => {
                    setArsip(v);
                    if (v) setManual(true);
                    form.setData("arsip", v ? 1 : 0);
                  }
                }
              ),
              /* @__PURE__ */ jsx(Label, { htmlFor: "arsipToggle", className: "text-sm font-semibold", children: "Ini dokumen lama — unggah berkas PDF-nya" })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "mb-4", children: [
              /* @__PURE__ */ jsx(Label, { className: "mb-1.5 text-sm font-semibold", children: "Jenis Dokumen" }),
              /* @__PURE__ */ jsx(Input, { value: `${type.code} — ${type.name}`, readOnly: true, disabled: true })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "mb-4", children: [
              /* @__PURE__ */ jsx(Label, { className: "mb-1.5 text-sm font-semibold", children: "Departemen" }),
              canChooseDept ? /* @__PURE__ */ jsxs(
                Select,
                {
                  value: form.data.department_id ? String(form.data.department_id) : void 0,
                  onValueChange: (v) => form.setData("department_id", Number(v)),
                  children: [
                    /* @__PURE__ */ jsx(SelectTrigger, { className: "w-full", children: /* @__PURE__ */ jsx(SelectValue, { placeholder: "— Pilih —" }) }),
                    /* @__PURE__ */ jsx(SelectContent, { children: departments.map((d) => /* @__PURE__ */ jsxs(SelectItem, { value: String(d.id), children: [
                      d.code,
                      " — ",
                      d.name
                    ] }, d.id)) })
                  ]
                }
              ) : /* @__PURE__ */ jsx(Input, { value: dept ? `${dept.code} — ${dept.name}` : "", readOnly: true, disabled: true })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "mb-4", children: [
              /* @__PURE__ */ jsx(Label, { className: "mb-1.5 text-sm font-semibold", htmlFor: "title", children: "Judul Dokumen" }),
              /* @__PURE__ */ jsx(
                Input,
                {
                  id: "title",
                  name: "title",
                  required: true,
                  value: form.data.title,
                  placeholder: "mis. Prosedur Backup Data Server",
                  onChange: (e) => form.setData("title", e.target.value)
                }
              )
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "mb-2", children: [
              /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between", children: [
                /* @__PURE__ */ jsx(Label, { className: "text-sm font-semibold", children: "Nomor Dokumen" }),
                arsip ? null : /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
                  /* @__PURE__ */ jsx(
                    Switch,
                    {
                      id: "manualToggle",
                      checked: manual,
                      onCheckedChange: (v) => {
                        setManual(v);
                        form.setData("doc_number_manual", v ? 1 : 0);
                      }
                    }
                  ),
                  /* @__PURE__ */ jsx(Label, { htmlFor: "manualToggle", className: "text-sm", children: "Input manual" })
                ] })
              ] }),
              manual || arsip ? /* @__PURE__ */ jsxs(Fragment, { children: [
                /* @__PURE__ */ jsx(
                  Input,
                  {
                    ref: nomorRef,
                    name: "doc_number",
                    value: form.data.doc_number,
                    placeholder: `mis. ${prefix}-SOP-ICTMD-05`,
                    onChange: (e) => form.setData("doc_number", e.target.value)
                  }
                ),
                /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-1 text-xs", children: arsip ? /* @__PURE__ */ jsxs(Fragment, { children: [
                  "Tulis nomor dokumen",
                  unggahanSaja ? "" : " lamanya",
                  " apa adanya. Nomor di luar pola resmi diterima dan ditandai badge",
                  " ",
                  /* @__PURE__ */ jsx("strong", { children: "Nomor Lama" }),
                  "."
                ] }) : "Pastikan nomor unik dan sesuai format." })
              ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
                /* @__PURE__ */ jsx(Input, { value: numberPreview, readOnly: true, disabled: true }),
                /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground mt-1 flex items-center gap-1.5 text-xs", children: [
                  /* @__PURE__ */ jsx(
                    HugeiconsIcon,
                    {
                      icon: SparklesIcon,
                      strokeWidth: 1.5,
                      className: "size-3.5 shrink-0"
                    }
                  ),
                  /* @__PURE__ */ jsxs("span", { children: [
                    /* @__PURE__ */ jsx("strong", { children: "Nomor sementara" }),
                    " — final dikunci setelah disetujui. Format: ",
                    prefix,
                    "-JENIS-DEPT-NN."
                  ] })
                ] })
              ] })
            ] }),
            arsip ? /* @__PURE__ */ jsxs(Fragment, { children: [
              /* @__PURE__ */ jsxs("div", { className: "mt-3 grid gap-3 md:grid-cols-3", children: [
                /* @__PURE__ */ jsxs("div", { children: [
                  /* @__PURE__ */ jsx(Label, { className: "mb-1.5 text-sm font-semibold", children: "Edisi" }),
                  /* @__PURE__ */ jsx(
                    Input,
                    {
                      type: "number",
                      name: "edisi",
                      min: 1,
                      max: 99,
                      value: form.data.edisi,
                      onChange: (e) => form.setData("edisi", Number(e.target.value))
                    }
                  )
                ] }),
                /* @__PURE__ */ jsxs("div", { children: [
                  /* @__PURE__ */ jsx(Label, { className: "mb-1.5 text-sm font-semibold", children: "No. Revisi" }),
                  /* @__PURE__ */ jsx(
                    Input,
                    {
                      type: "number",
                      name: "no_revisi",
                      min: 0,
                      max: 5,
                      value: form.data.no_revisi,
                      onChange: (e) => form.setData("no_revisi", Number(e.target.value))
                    }
                  ),
                  /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-1 text-xs", children: "0–5. Revisi ke-6 menaikkan Edisi." })
                ] }),
                /* @__PURE__ */ jsxs("div", { children: [
                  /* @__PURE__ */ jsx(Label, { className: "mb-1.5 text-sm font-semibold", children: "Tanggal Efektif" }),
                  /* @__PURE__ */ jsx(
                    Input,
                    {
                      type: "date",
                      name: "tanggal_efektif",
                      value: form.data.tanggal_efektif,
                      onChange: (e) => form.setData("tanggal_efektif", e.target.value)
                    }
                  ),
                  /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-1 text-xs", children: "Kosong = hari ini." })
                ] })
              ] }),
              /* @__PURE__ */ jsxs("div", { className: "mt-3", children: [
                /* @__PURE__ */ jsxs(Label, { className: "mb-1.5 text-sm font-semibold", children: [
                  "Berkas PDF ",
                  unggahanSaja ? type.name : "Dokumen Lama"
                ] }),
                /* @__PURE__ */ jsx(
                  Input,
                  {
                    type: "file",
                    name: "berkas",
                    accept: "application/pdf",
                    onChange: (e) => form.setData("berkas", e.target.files?.[0] ?? null)
                  }
                ),
                /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground mt-1 text-xs", children: [
                  "PDF saja, maksimal ",
                  /* @__PURE__ */ jsx("strong", { children: "40 MB" }),
                  " (batas server). Berkas disimpan privat — hanya bisa dibuka lewat tombol PDF oleh pengguna yang berhak."
                ] })
              ] })
            ] }) : null,
            /* @__PURE__ */ jsxs("div", { className: "mt-6 flex flex-wrap gap-2", children: [
              /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: form.processing, children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: arsip ? Archive01Icon : CircleArrowRight01Icon,
                    strokeWidth: 1.5
                  }
                ),
                arsip ? "Daftarkan & Berlakukan" : "Buat & Lanjut Pengisian"
              ] }),
              /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", children: /* @__PURE__ */ jsx(Link, { href: route("documents.index"), children: "Batal" }) })
            ] })
          ] }),
          /* @__PURE__ */ jsx(
            ConfirmDialog,
            {
              buka: konfirmasi,
              onUbahBuka: setKonfirmasi,
              judul: `Daftarkan ${unggahanSaja ? type.code : "Dokumen Lama"}?`,
              pesan: "Dokumen ini langsung BERLAKU tanpa melewati tinjau–setuju. Lanjutkan?",
              tombolYa: "Ya, daftarkan",
              onKonfirmasi: kirim
            }
          ),
          /* @__PURE__ */ jsx(
            ConfirmDialog,
            {
              buka: bentrok,
              onUbahBuka: (b) => {
                setBentrok(b);
                if (!b) nomorRef.current?.focus();
              },
              judul: "Nomor Sudah Dipakai",
              pesan: /* @__PURE__ */ jsxs(Fragment, { children: [
                "Nomor ",
                /* @__PURE__ */ jsx("strong", { children: form.data.doc_number || "—" }),
                " sudah dipakai dokumen lain. Nomor bebas berikutnya adalah ",
                /* @__PURE__ */ jsx("strong", { children: nomorSaran }),
                "."
              ] }),
              tombolBatal: "Ketik nomor lain",
              tombolYa: "Pakai nomor otomatis",
              onKonfirmasi: pakaiNomorOtomatis
            }
          )
        ] }) }),
        /* @__PURE__ */ jsx(Card, { className: "bg-muted/40 h-fit", children: /* @__PURE__ */ jsxs(CardContent, { children: [
          /* @__PURE__ */ jsxs("h2", { className: "text-muted-foreground flex items-center gap-1.5 font-semibold", children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
            "Tentang Penomoran"
          ] }),
          /* @__PURE__ */ jsx("p", { className: "text-muted-foreground mt-2 mb-2 text-sm", children: "Nomor mengikuti pola tetap dan bertambah otomatis per jenis + departemen:" }),
          /* @__PURE__ */ jsxs("code", { className: "mb-2 block text-sm", children: [
            prefix,
            "-SOP-ICTMD-01"
          ] }),
          /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground text-sm", children: [
            "Aktifkan ",
            /* @__PURE__ */ jsx("strong", { children: "Input manual" }),
            " hanya bila perlu menyesuaikan nomor lama."
          ] })
        ] }) })
      ] })
    }
  );
}
export {
  DocumentsCreate as default
};
