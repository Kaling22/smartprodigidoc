import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { RoboticIcon, RepeatIcon, ShieldKeyIcon, FloppyDiskIcon, PlugSocketIcon, Archive01Icon, InformationCircleIcon, PulseIcon, Mail01Icon, SentIcon, EraserIcon, RefreshCwIcon, OctagonAlertIcon, Delete02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { A as AppLayout, S as Separator, B as Badge, D as Dialog, b as DialogTrigger, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, g as DialogFooter, h as DialogClose } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { C as Checkbox } from "./checkbox-DZTcz7W1.js";
import { d as FieldGroup, F as Field, a as FieldLabel, b as FieldDescription, c as FieldError } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { S as Spinner } from "./spinner-dcS4h88c.js";
import { S as Switch } from "./switch-3nvfOMMe.js";
import { T as Table, d as TableBody, b as TableRow, e as TableCell } from "./table-COSAIfdh.js";
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
const TANPA = "*";
function PengaturanSistem({
  ai,
  keyTopeng,
  cadanganKeyTopeng,
  penyedia,
  kesehatan,
  arsipGabungAktif
}) {
  return /* @__PURE__ */ jsxs(AppLayout, { judul: "Konfigurasi Sistem", children: [
    /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:gap-6 lg:grid-cols-12", children: [
      /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:gap-6 lg:col-span-7", children: [
        /* @__PURE__ */ jsx(
          KartuAi,
          {
            ai,
            keyTopeng,
            cadanganKeyTopeng,
            penyedia
          }
        ),
        /* @__PURE__ */ jsx(KartuUjiAi, {}),
        /* @__PURE__ */ jsx(KartuArsipGabung, { aktif: arsipGabungAktif })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:gap-6 lg:col-span-5", children: [
        /* @__PURE__ */ jsx(KartuKesehatan, { kesehatan }),
        /* @__PURE__ */ jsx(KartuEmail, { kesehatan }),
        /* @__PURE__ */ jsx(KartuPemeliharaan, {})
      ] })
    ] }),
    /* @__PURE__ */ jsx(ZonaBerbahaya, { jumlahDokumen: kesehatan.dokumen_semua })
  ] });
}
function KartuArsipGabung({ aktif }) {
  const { data, setData, put, processing } = useForm({ enabled: aktif });
  const [konfirmasi, setKonfirmasi] = useState(false);
  function minta(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  return /* @__PURE__ */ jsx("form", { onSubmit: minta, children: /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: Archive01Icon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Dokumen Lama — Gabung PDF"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Menentukan apa yang terjadi begitu lembar Catatan Revisi dokumen lama (SOP/SP/IK) disimpan." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsxs(Field, { orientation: "horizontal", children: [
      /* @__PURE__ */ jsx(
        Switch,
        {
          id: "arsip-gabung-enabled",
          checked: data.enabled,
          onCheckedChange: (v) => setData("enabled", v)
        }
      ),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-1", children: [
        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "arsip-gabung-enabled", children: "Potong halaman 1-2 & ganti dengan Cover + Catatan Revisi" }),
        /* @__PURE__ */ jsx(FieldDescription, { children: "Aktif (bawaan): begitu lembar Catatan Revisi disimpan, halaman 1-2 berkas PDF asli langsung diganti dengan Cover dan Catatan Revisi hasil cetak sistem — dokumen tetap Berlaku, tanpa mengetik ulang isinya di wizard. Nonaktif: kembali ke alur lama — isi dokumen diketik ulang di wizard dan berkas unggahan tinggal jadi rujukan." })
      ] })
    ] }) }),
    /* @__PURE__ */ jsxs(CardFooter, { className: "flex-wrap items-center justify-between gap-2", children: [
      /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground flex items-center gap-1.5 text-sm", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: InformationCircleIcon,
            strokeWidth: 1.5,
            className: "size-4",
            "aria-hidden": "true"
          }
        ),
        "Berkas asli tak pernah ditimpa — potongannya selalu dihitung ulang dari unggahan pertama."
      ] }),
      /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
        processing ? /* @__PURE__ */ jsx(Spinner, {}) : /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FloppyDiskIcon, strokeWidth: 1.5, className: "size-4" }),
        "Simpan"
      ] }),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Simpan setelan Dokumen Lama?",
          pesan: data.enabled ? "Dokumen lama berikutnya yang lembar Catatan Revisinya disimpan akan langsung memotong halaman 1-2 PDF asli." : "Dokumen lama berikutnya akan kembali diketik ulang di wizard sesudah lembar Catatan Revisi disimpan.",
          tombolYa: "Ya, simpan",
          buka: konfirmasi,
          onUbahBuka: setKonfirmasi,
          onKonfirmasi: () => put(route("pengaturan.sistem.arsip-gabung"))
        }
      )
    ] })
  ] }) });
}
function KartuAi({
  ai,
  keyTopeng,
  cadanganKeyTopeng,
  penyedia
}) {
  const { data, setData, put, processing, errors } = useForm({
    enabled: ai.enabled,
    provider: ai.provider ?? "",
    model: ai.model ?? "",
    key: "",
    hapus_key: false,
    cadangan_provider: ai.cadangan_provider ?? "",
    cadangan_model: ai.cadangan_model ?? "",
    cadangan_key: "",
    hapus_cadangan_key: false
  });
  const [konfirmasi, setKonfirmasi] = useState(false);
  function minta(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  return /* @__PURE__ */ jsx("form", { onSubmit: minta, children: /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: RoboticIcon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Bantuan AI"
      ] }),
      /* @__PURE__ */ jsxs(CardDescription, { children: [
        "AI hanya ",
        /* @__PURE__ */ jsx("strong", { children: "membantu" }),
        " peninjau — ia tak pernah meloloskan atau menolak dokumen sendiri."
      ] })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsxs(FieldGroup, { children: [
      /* @__PURE__ */ jsxs(Field, { orientation: "horizontal", children: [
        /* @__PURE__ */ jsx(
          Switch,
          {
            id: "ai-enabled",
            checked: data.enabled,
            onCheckedChange: (v) => setData("enabled", v)
          }
        ),
        /* @__PURE__ */ jsxs("div", { className: "grid gap-1", children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "ai-enabled", children: "Aktifkan bantuan AI" }),
          /* @__PURE__ */ jsx(FieldDescription, { children: "Dimatikan, panel AI hilang dari layar tinjau dan sisanya berjalan seperti biasa — peninjauan tak pernah bergantung padanya." })
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-4 sm:grid-cols-12", children: [
        /* @__PURE__ */ jsxs(Field, { className: "sm:col-span-5", "data-invalid": !!errors.provider || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "provider", children: "Penyedia" }),
          /* @__PURE__ */ jsxs(Select, { value: data.provider, onValueChange: (v) => setData("provider", v), children: [
            /* @__PURE__ */ jsx(
              SelectTrigger,
              {
                id: "provider",
                className: "w-full",
                "aria-invalid": !!errors.provider,
                children: /* @__PURE__ */ jsx(SelectValue, { placeholder: "— Pilih —" })
              }
            ),
            /* @__PURE__ */ jsx(SelectContent, { children: Object.entries(penyedia).map(([kunci, label]) => /* @__PURE__ */ jsx(SelectItem, { value: kunci, children: label }, kunci)) })
          ] }),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: errors.provider ? [{ message: errors.provider }] : void 0
            }
          )
        ] }),
        /* @__PURE__ */ jsxs(Field, { className: "sm:col-span-7", "data-invalid": !!errors.model || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "model", children: "Model" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "model",
              required: true,
              maxLength: 120,
              className: "font-mono",
              placeholder: "mis. gemini-2.0-flash",
              value: data.model,
              onChange: (e) => setData("model", e.target.value),
              "aria-invalid": !!errors.model
            }
          ),
          /* @__PURE__ */ jsx(FieldError, { errors: errors.model ? [{ message: errors.model }] : void 0 })
        ] })
      ] }),
      /* @__PURE__ */ jsx(
        IsianKunci,
        {
          id: "key",
          label: "Kunci API",
          topeng: keyTopeng,
          nilai: data.key,
          onUbah: (v) => setData("key", v),
          hapus: data.hapus_key,
          onUbahHapus: (v) => setData("hapus_key", v),
          galat: errors.key,
          petunjukKosong: /* @__PURE__ */ jsxs(Fragment, { children: [
            "Belum ada kunci tersimpan di basis data — sistem memakai nilai dari",
            " ",
            /* @__PURE__ */ jsx("span", { className: "font-mono", children: ".env" }),
            " bila ada."
          ] })
        }
      ),
      /* @__PURE__ */ jsx(Separator, {}),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-1", children: [
        /* @__PURE__ */ jsxs("div", { className: "text-muted-foreground flex items-center gap-2 text-sm font-semibold", children: [
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: RepeatIcon,
              strokeWidth: 1.5,
              className: "size-4",
              "aria-hidden": "true"
            }
          ),
          "Penyedia cadangan ",
          /* @__PURE__ */ jsx("span", { className: "font-normal", children: "(opsional)" })
        ] }),
        /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "Dipakai hanya bila panggilan ke penyedia utama gagal. Kosongkan penyedianya untuk mematikan cadangan." })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-4 sm:grid-cols-12", children: [
        /* @__PURE__ */ jsxs(
          Field,
          {
            className: "sm:col-span-5",
            "data-invalid": !!errors.cadangan_provider || void 0,
            children: [
              /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "cadangan_provider", children: "Penyedia cadangan" }),
              /* @__PURE__ */ jsxs(
                Select,
                {
                  value: data.cadangan_provider || TANPA,
                  onValueChange: (v) => setData("cadangan_provider", v === TANPA ? "" : v),
                  children: [
                    /* @__PURE__ */ jsx(
                      SelectTrigger,
                      {
                        id: "cadangan_provider",
                        className: "w-full",
                        "aria-invalid": !!errors.cadangan_provider,
                        children: /* @__PURE__ */ jsx(SelectValue, {})
                      }
                    ),
                    /* @__PURE__ */ jsxs(SelectContent, { children: [
                      /* @__PURE__ */ jsx(SelectItem, { value: TANPA, children: "— tanpa cadangan —" }),
                      Object.entries(penyedia).map(([kunci, label]) => /* @__PURE__ */ jsx(SelectItem, { value: kunci, children: label }, kunci))
                    ] })
                  ]
                }
              ),
              /* @__PURE__ */ jsx(
                FieldError,
                {
                  errors: errors.cadangan_provider ? [{ message: errors.cadangan_provider }] : void 0
                }
              )
            ]
          }
        ),
        /* @__PURE__ */ jsxs(
          Field,
          {
            className: "sm:col-span-7",
            "data-invalid": !!errors.cadangan_model || void 0,
            children: [
              /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "cadangan_model", children: "Model cadangan" }),
              /* @__PURE__ */ jsx(
                Input,
                {
                  id: "cadangan_model",
                  maxLength: 120,
                  className: "font-mono",
                  value: data.cadangan_model,
                  onChange: (e) => setData("cadangan_model", e.target.value),
                  "aria-invalid": !!errors.cadangan_model
                }
              ),
              /* @__PURE__ */ jsx(
                FieldError,
                {
                  errors: errors.cadangan_model ? [{ message: errors.cadangan_model }] : void 0
                }
              )
            ]
          }
        ),
        /* @__PURE__ */ jsx("div", { className: "sm:col-span-12", children: /* @__PURE__ */ jsx(
          IsianKunci,
          {
            id: "cadangan_key",
            label: "Kunci API cadangan",
            topeng: cadanganKeyTopeng,
            nilai: data.cadangan_key,
            onUbah: (v) => setData("cadangan_key", v),
            hapus: data.hapus_cadangan_key,
            onUbahHapus: (v) => setData("hapus_cadangan_key", v),
            galat: errors.cadangan_key
          }
        ) })
      ] })
    ] }) }),
    /* @__PURE__ */ jsxs(CardFooter, { className: "flex-wrap items-center justify-between gap-2", children: [
      /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground flex items-center gap-1.5 text-sm", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: ShieldKeyIcon,
            strokeWidth: 1.5,
            className: "size-4",
            "aria-hidden": "true"
          }
        ),
        "Kunci disimpan tersandi; audit log mencatat perubahannya tanpa isinya."
      ] }),
      /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
        processing ? /* @__PURE__ */ jsx(Spinner, {}) : /* @__PURE__ */ jsx(HugeiconsIcon, { icon: FloppyDiskIcon, strokeWidth: 1.5, className: "size-4" }),
        "Simpan"
      ] }),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Simpan setelan AI?",
          pesan: "Setelan AI berlaku untuk peninjauan berikutnya. Kunci API yang dikosongkan tidak akan terhapus.",
          tombolYa: "Ya, simpan",
          buka: konfirmasi,
          onUbahBuka: setKonfirmasi,
          onKonfirmasi: () => put(route("pengaturan.sistem.ai"))
        }
      )
    ] })
  ] }) });
}
function IsianKunci({
  id,
  label,
  topeng,
  nilai,
  onUbah,
  hapus,
  onUbahHapus,
  galat,
  petunjukKosong
}) {
  return /* @__PURE__ */ jsxs(Field, { "data-invalid": !!galat || void 0, children: [
    /* @__PURE__ */ jsx(FieldLabel, { htmlFor: id, children: label }),
    /* @__PURE__ */ jsx(
      Input,
      {
        id,
        type: "password",
        maxLength: 400,
        autoComplete: "new-password",
        className: "font-mono",
        placeholder: topeng ?? "belum disetel",
        value: nilai,
        onChange: (e) => onUbah(e.target.value),
        "aria-invalid": !!galat
      }
    ),
    /* @__PURE__ */ jsx(FieldDescription, { children: topeng ? /* @__PURE__ */ jsxs(Fragment, { children: [
      "Tersimpan & tersandi: ",
      /* @__PURE__ */ jsx("span", { className: "font-mono", children: topeng }),
      ". Kosongkan untuk mempertahankannya."
    ] }) : petunjukKosong }),
    topeng ? /* @__PURE__ */ jsxs(Field, { orientation: "horizontal", children: [
      /* @__PURE__ */ jsx(
        Checkbox,
        {
          id: `hapus-${id}`,
          checked: hapus,
          onCheckedChange: (v) => onUbahHapus(v === true)
        }
      ),
      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: `hapus-${id}`, className: "text-destructive font-normal", children: "Hapus kunci yang tersimpan" })
    ] }) : null,
    /* @__PURE__ */ jsx(FieldError, { errors: galat ? [{ message: galat }] : void 0 })
  ] });
}
function KartuUjiAi() {
  return /* @__PURE__ */ jsx(Card, { children: /* @__PURE__ */ jsxs(CardContent, { className: "flex flex-wrap items-center justify-between gap-3", children: [
    /* @__PURE__ */ jsxs("p", { className: "text-sm", children: [
      /* @__PURE__ */ jsx("span", { className: "font-semibold", children: "Uji koneksi AI." }),
      " Memanggil penyedia sekali tanpa membangkitkan tinjauan — menjawab “kuncinya hidup atau tidak” dalam hitungan detik, bukan menit. Yang diuji adalah setelan yang sudah",
      " ",
      /* @__PURE__ */ jsx("strong", { children: "tersimpan" }),
      "."
    ] }),
    /* @__PURE__ */ jsx(
      TombolAksi,
      {
        url: route("pengaturan.sistem.uji-ai"),
        ikon: PlugSocketIcon,
        label: "Uji koneksi AI",
        judul: "Uji koneksi AI?",
        pesan: "Satu panggilan ke penyedia AI, tanpa biaya tinjauan. Batas 6 kali per menit.",
        tombolYa: "Ya, uji"
      }
    )
  ] }) });
}
function KartuKesehatan({ kesehatan }) {
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: PulseIcon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Kesehatan Sistem"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Keadaan server & isi basis data." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx("div", { className: "overflow-x-auto", children: /* @__PURE__ */ jsx(Table, { children: /* @__PURE__ */ jsxs(TableBody, { children: [
      /* @__PURE__ */ jsx(BarisKesehatan, { label: "PHP", mono: true, children: kesehatan.php }),
      /* @__PURE__ */ jsx(BarisKesehatan, { label: "Laravel", mono: true, children: kesehatan.laravel }),
      /* @__PURE__ */ jsx(BarisKesehatan, { label: "Zona waktu", mono: true, children: kesehatan.zona }),
      /* @__PURE__ */ jsx(BarisKesehatan, { label: "Mode debug", children: kesehatan.debug ? /* @__PURE__ */ jsx(Badge, { variant: "destructive", children: "Menyala — matikan di server produksi" }) : /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: "Mati" }) }),
      /* @__PURE__ */ jsxs(BarisKesehatan, { label: "Lampiran foto", children: [
        (kesehatan.lampiran_bytes / 1048576).toFixed(1),
        " MB",
        " ",
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-[.72rem]", children: "(diperbarui tiap 5 menit)" })
      ] }),
      /* @__PURE__ */ jsxs(BarisKesehatan, { label: "Dokumen", children: [
        kesehatan.dokumen,
        " total · ",
        kesehatan.dokumen_berlaku,
        " Berlaku"
      ] }),
      /* @__PURE__ */ jsx(BarisKesehatan, { label: "Pengguna aktif", children: kesehatan.pengguna }),
      kesehatan.ai.map((p) => /* @__PURE__ */ jsxs(BarisKesehatan, { label: `AI ${p.label}`, children: [
        /* @__PURE__ */ jsxs("span", { className: "font-mono text-sm", children: [
          p.provider ?? "—",
          " / ",
          p.model ?? "—"
        ] }),
        " ",
        "·",
        " ",
        /* @__PURE__ */ jsx(
          "span",
          {
            className: p.status.endsWith("kosong") ? "text-destructive font-medium" : "text-muted-foreground",
            children: p.status
          }
        )
      ] }, p.label))
    ] }) }) }) })
  ] });
}
function BarisKesehatan({
  label,
  mono = false,
  children
}) {
  return /* @__PURE__ */ jsxs(TableRow, { children: [
    /* @__PURE__ */ jsx(TableCell, { className: "text-muted-foreground w-40 text-sm", children: label }),
    /* @__PURE__ */ jsx(TableCell, { className: mono ? "font-mono text-sm" : "text-sm", children })
  ] });
}
function KartuEmail({ kesehatan }) {
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: Mail01Icon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Pengiriman Email"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Kanal notifikasi penting; lonceng tak terpengaruh." })
    ] }),
    /* @__PURE__ */ jsxs(CardContent, { className: "grid gap-3", children: [
      /* @__PURE__ */ jsxs("dl", { className: "grid grid-cols-[5rem_1fr] gap-x-3 gap-y-1 text-sm", children: [
        /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: "Mailer" }),
        /* @__PURE__ */ jsx("dd", { className: "font-mono", children: kesehatan.mailer }),
        /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: "Host" }),
        /* @__PURE__ */ jsx("dd", { className: "font-mono break-all", children: kesehatan.mail_host ?? "—" }),
        /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: "Pengirim" }),
        /* @__PURE__ */ jsx("dd", { className: "font-mono break-all", children: kesehatan.mail_dari ?? "—" })
      ] }),
      kesehatan.mailer === "log" ? /* @__PURE__ */ jsx(Alert, { children: /* @__PURE__ */ jsxs(AlertDescription, { children: [
        "Mailer masih ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: "log" }),
        " — email tidak benar-benar terkirim, isinya ditulis ke",
        " ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: "storage/logs" }),
        "."
      ] }) }) : null,
      kesehatan.mail_paksa_ke ? (
        /* Katup pengaman peralihan (docs/PANDUAN-PRODUKSI-EMAIL.md). Wajib
           terlihat di sini: selama ia terisi, SELURUH notifikasi email
           mendarat di satu alamat dan tak seorang penerima pun sadar. */
        /* @__PURE__ */ jsx(Alert, { variant: "destructive", children: /* @__PURE__ */ jsxs(AlertDescription, { children: [
          "Seluruh email sedang ",
          /* @__PURE__ */ jsx("strong", { children: "dibelokkan" }),
          " ke",
          " ",
          /* @__PURE__ */ jsx("span", { className: "font-mono", children: kesehatan.mail_paksa_ke }),
          " (",
          /* @__PURE__ */ jsx("span", { className: "font-mono", children: "MAIL_PAKSA_KE" }),
          "). Penerima sesungguhnya tidak menerima apa pun."
        ] }) })
      ) : null
    ] }),
    /* @__PURE__ */ jsx(CardFooter, { children: /* @__PURE__ */ jsx(
      TombolAksi,
      {
        url: route("pengaturan.sistem.uji-email"),
        ikon: SentIcon,
        label: "Kirim email uji ke alamat saya",
        judul: "Kirim email uji?",
        pesan: "Email uji dikirim ke alamat akun Anda sendiri. Batas 3 kali per menit.",
        tombolYa: "Ya, kirim",
        kelas: "w-full"
      }
    ) })
  ] });
}
function KartuPemeliharaan() {
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: EraserIcon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Pemeliharaan"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Dipakai setelah mengubah izin atau setelan." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "Membersihkan cache aplikasi dan cache izin (spatie). Tidak menyentuh satu pun data: dokumen, pengguna, dan setelan tetap utuh — yang dibuang hanya salinan sementaranya." }) }),
    /* @__PURE__ */ jsx(CardFooter, { children: /* @__PURE__ */ jsx(
      TombolAksi,
      {
        url: route("pengaturan.sistem.cache"),
        ikon: RefreshCwIcon,
        label: "Bersihkan cache",
        judul: "Bersihkan cache?",
        pesan: "Cache aplikasi & cache izin akan dibersihkan. Tidak ada data yang terhapus.",
        tombolYa: "Ya, bersihkan",
        kelas: "w-full",
        varian: "secondary"
      }
    ) })
  ] });
}
function TombolAksi({
  url,
  ikon,
  label,
  judul,
  pesan,
  tombolYa,
  kelas,
  varian = "outline"
}) {
  const { post, processing } = useForm({});
  return /* @__PURE__ */ jsx(
    ConfirmDialog,
    {
      judul,
      pesan,
      tombolYa,
      onKonfirmasi: () => post(url, { preserveScroll: true }),
      pemicu: /* @__PURE__ */ jsxs(Button, { type: "button", variant: varian, className: kelas, disabled: processing, children: [
        processing ? /* @__PURE__ */ jsx(Spinner, {}) : /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ikon, strokeWidth: 1.5, className: "size-4" }),
        label
      ] })
    }
  );
}
function ZonaBerbahaya({ jumlahDokumen }) {
  const [buka, setBuka] = useState(false);
  const [konfirmasi, setKonfirmasi] = useState(false);
  const form = useForm({ alasan: "", konfirmasi: "" });
  function minta(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  return /* @__PURE__ */ jsxs(Card, { className: "border-destructive", children: [
    /* @__PURE__ */ jsx(CardHeader, { children: /* @__PURE__ */ jsxs(CardTitle, { className: "text-destructive flex items-center gap-2", children: [
      /* @__PURE__ */ jsx(
        HugeiconsIcon,
        {
          icon: OctagonAlertIcon,
          strokeWidth: 1.5,
          className: "size-4",
          "aria-hidden": "true"
        }
      ),
      "Zona Berbahaya"
    ] }) }),
    /* @__PURE__ */ jsxs(CardContent, { className: "flex flex-wrap items-center justify-between gap-3", children: [
      /* @__PURE__ */ jsxs("p", { className: "text-sm", children: [
        /* @__PURE__ */ jsx("span", { className: "font-semibold", children: "Bersihkan seluruh dokumen." }),
        " Menghapus",
        " ",
        jumlahDokumen,
        " dokumen beserta lampiran, versi, dan riwayatnya — tanpa undo. Akun, departemen, dan Audit Log tidak tersentuh."
      ] }),
      /* @__PURE__ */ jsxs(Dialog, { open: buka, onOpenChange: setBuka, children: [
        /* @__PURE__ */ jsx(DialogTrigger, { asChild: true, children: /* @__PURE__ */ jsxs(Button, { variant: "destructive", disabled: jumlahDokumen === 0, children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }),
          "Bersihkan Seluruh Dokumen"
        ] }) }),
        /* @__PURE__ */ jsx(DialogContent, { children: /* @__PURE__ */ jsxs("form", { onSubmit: minta, className: "grid gap-4", children: [
          /* @__PURE__ */ jsxs(DialogHeader, { children: [
            /* @__PURE__ */ jsxs(DialogTitle, { className: "text-destructive flex items-center gap-2", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: OctagonAlertIcon,
                  strokeWidth: 1.5,
                  className: "size-4",
                  "aria-hidden": "true"
                }
              ),
              "Bersihkan Seluruh Dokumen"
            ] }),
            /* @__PURE__ */ jsxs(DialogDescription, { children: [
              jumlahDokumen,
              " dokumen — segala status, tujuh departemen, termasuk yang sudah diarsipkan. Beserta lampiran, berkas arsip, dan singgahan PDF-nya. Tidak ada undo."
            ] })
          ] }),
          /* @__PURE__ */ jsx(Alert, { children: /* @__PURE__ */ jsxs(AlertDescription, { children: [
            "Akun pengguna, departemen, jenis dokumen, menu Informasi, dan",
            " ",
            /* @__PURE__ */ jsx("strong", { children: "Audit Log" }),
            " tidak tersentuh."
          ] }) }),
          /* @__PURE__ */ jsxs(FieldGroup, { children: [
            /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.alasan || void 0, children: [
              /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "alasan", children: "Alasan pembersihan" }),
              /* @__PURE__ */ jsx(
                Textarea,
                {
                  id: "alasan",
                  rows: 3,
                  required: true,
                  minLength: 10,
                  maxLength: 1e3,
                  placeholder: "mis. mengosongkan data uji sebelum impor arsip lama",
                  value: form.data.alasan,
                  onChange: (e) => form.setData("alasan", e.target.value),
                  "aria-invalid": !!form.errors.alasan
                }
              ),
              /* @__PURE__ */ jsx(FieldDescription, { children: "Tersimpan di Audit Log sebagai satu-satunya jejak yang tersisa." }),
              /* @__PURE__ */ jsx(
                FieldError,
                {
                  errors: form.errors.alasan ? [{ message: form.errors.alasan }] : void 0
                }
              )
            ] }),
            /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.konfirmasi || void 0, children: [
              /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "konfirmasi", children: "Ketik frasa konfirmasi" }),
              /* @__PURE__ */ jsx(
                Input,
                {
                  id: "konfirmasi",
                  required: true,
                  autoComplete: "off",
                  className: "font-mono",
                  placeholder: "MUSNAHKAN SEMUA DOKUMEN",
                  value: form.data.konfirmasi,
                  onChange: (e) => form.setData("konfirmasi", e.target.value),
                  "aria-invalid": !!form.errors.konfirmasi
                }
              ),
              /* @__PURE__ */ jsxs(FieldDescription, { children: [
                "Harus persis",
                " ",
                /* @__PURE__ */ jsx("span", { className: "font-mono font-semibold", children: "MUSNAHKAN SEMUA DOKUMEN" }),
                "."
              ] }),
              /* @__PURE__ */ jsx(
                FieldError,
                {
                  errors: form.errors.konfirmasi ? [{ message: form.errors.konfirmasi }] : void 0
                }
              )
            ] })
          ] }),
          /* @__PURE__ */ jsxs(DialogFooter, { children: [
            /* @__PURE__ */ jsx(DialogClose, { asChild: true, children: /* @__PURE__ */ jsx(Button, { type: "button", variant: "ghost", children: "Batal" }) }),
            /* @__PURE__ */ jsxs(Button, { type: "submit", variant: "destructive", disabled: form.processing, children: [
              /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }),
              "Musnahkan Semua"
            ] })
          ] }),
          /* @__PURE__ */ jsx(
            ConfirmDialog,
            {
              judul: "Bersihkan Seluruh Dokumen?",
              pesan: `Musnahkan SELURUH ${jumlahDokumen} dokumen? Seluruh isi, versi, riwayat, lampiran, dan masukan hilang permanen. Tidak ada undo.`,
              tombolYa: "Ya, musnahkan semua",
              destruktif: true,
              buka: konfirmasi,
              onUbahBuka: setKonfirmasi,
              onKonfirmasi: () => form.delete(route("documents.purgeAll"), {
                // Jendelanya hanya ditutup kalau memang
                // berhasil — galat validasi harus tetap
                // terbaca di tempat isiannya, bukan
                // hilang bersama jendelanya.
                onSuccess: () => {
                  setBuka(false);
                  form.reset();
                }
              })
            }
          )
        ] }) })
      ] })
    ] })
  ] });
}
export {
  PengaturanSistem as default
};
