import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { InboxIcon, ReplyIcon, CheckmarkCircle02Icon, InformationCircleIcon, SentIcon, Message01Icon, CalendarRemove01Icon, RefreshCwIcon, CircleSlashIcon, OctagonAlertIcon, Delete02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Checkbox } from "./checkbox-DZTcz7W1.js";
import { B as Badge, S as Separator, D as Dialog, b as DialogTrigger, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, g as DialogFooter } from "./AppLayout-C74XVGrz.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError, b as FieldDescription } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { L as Label } from "./label-emiz6fAI.js";
import { T as Textarea } from "./textarea-CFf6776c.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
function DaftarMasukan({
  masukan,
  bolehMembalas
}) {
  if (masukan.length === 0) {
    return /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground flex items-center gap-2 text-sm", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InboxIcon, strokeWidth: 1.5, className: "size-4", "aria-hidden": "true" }),
      "Belum ada masukan."
    ] });
  }
  return /* @__PURE__ */ jsx("div", { className: "grid gap-2", children: masukan.map((m) => /* @__PURE__ */ jsx(KartuMasukan, { m, bolehMembalas }, m.id)) });
}
function ronaStatus(status) {
  if (status === "baru") return "destructive";
  if (status === "diadopsi") return "default";
  return "secondary";
}
function KartuMasukan({ m, bolehMembalas }) {
  const form = useForm({ balasan: "" });
  const [konfirmasi, setKonfirmasi] = useState(false);
  function balas(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  function kirim() {
    form.post(route("documents.feedback.respond", m.id), {
      preserveScroll: true,
      onSuccess: () => form.reset("balasan")
    });
  }
  return /* @__PURE__ */ jsxs("div", { className: "rounded-2xl border p-2", children: [
    /* @__PURE__ */ jsxs("div", { className: "flex items-start justify-between gap-2", children: [
      /* @__PURE__ */ jsx("span", { className: "text-muted-foreground font-mono text-xs", children: m.nomor }),
      /* @__PURE__ */ jsx(Badge, { variant: ronaStatus(m.status), children: m.status_label })
    ] }),
    /* @__PURE__ */ jsx("p", { className: "text-sm", children: m.isi }),
    /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground text-xs", children: [
      m.oleh ?? "—",
      " · ",
      m.waktu,
      " WITA"
    ] }),
    m.balasan ? /* @__PURE__ */ jsxs("div", { className: "bg-muted mt-2 rounded-r border-l-4 px-2 py-1 text-sm", children: [
      /* @__PURE__ */ jsxs("span", { className: "inline-flex items-center gap-1 font-semibold", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ReplyIcon, strokeWidth: 1.5, className: "size-3.5", "aria-hidden": "true" }),
        "Balasan"
      ] }),
      m.pembalas ? /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground", children: [
        " — ",
        m.pembalas
      ] }) : null,
      /* @__PURE__ */ jsx("div", { children: m.balasan })
    ] }) : null,
    m.status === "diadopsi" && !m.balasan ? /* @__PURE__ */ jsxs("p", { className: "text-chart-5 mt-1 flex items-center gap-1 text-sm", children: [
      /* @__PURE__ */ jsx(
        HugeiconsIcon,
        {
          icon: CheckmarkCircle02Icon,
          strokeWidth: 1.5,
          className: "size-3.5",
          "aria-hidden": "true"
        }
      ),
      "Diadopsi menjadi bahan revisi dokumen ini."
    ] }) : null,
    bolehMembalas && (m.status === "baru" || m.status === "dibaca") ? /* @__PURE__ */ jsxs("form", { onSubmit: balas, className: "mt-2 flex gap-2", children: [
      /* @__PURE__ */ jsx(
        Input,
        {
          name: "balasan",
          required: true,
          maxLength: 2e3,
          placeholder: "Balasan untuk pengirim (mis. sudah sesuai standar terbaru)...",
          value: form.data.balasan,
          onChange: (e) => form.setData("balasan", e.target.value)
        }
      ),
      /* @__PURE__ */ jsxs(Button, { type: "submit", variant: "outline", size: "sm", disabled: form.processing, className: "shrink-0", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ReplyIcon, strokeWidth: 1.5, className: "size-4" }),
        "Balas & Tutup"
      ] }),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: "Balas & Tutup?",
          pesan: `Tutup masukan ${m.nomor} tanpa revisi dan kirim balasan ke pengirimnya?`,
          tombolYa: "Ya, kirim balasan",
          buka: konfirmasi,
          onUbahBuka: setKonfirmasi,
          onKonfirmasi: kirim
        }
      )
    ] }) : null
  ] });
}
function JendelaFormulir({
  pemicu,
  judul,
  keterangan,
  anak,
  tombolKirim,
  konfirmasiJudul,
  konfirmasiPesan,
  konfirmasiYa,
  destruktif = false,
  sedangKirim,
  onKirim,
  lebar,
  buka: bukaLuar,
  onUbahBuka
}) {
  const [bukaSendiri, setBukaSendiri] = useState(false);
  const [konfirmasi, setKonfirmasi] = useState(false);
  const dikendalikan = bukaLuar !== void 0;
  const buka = dikendalikan ? bukaLuar : bukaSendiri;
  const setBuka = dikendalikan ? onUbahBuka ?? (() => {
  }) : setBukaSendiri;
  function ajukan(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  return /* @__PURE__ */ jsxs(Dialog, { open: buka, onOpenChange: setBuka, children: [
    pemicu ? /* @__PURE__ */ jsx(DialogTrigger, { asChild: true, children: pemicu }) : null,
    /* @__PURE__ */ jsxs(DialogContent, { className: lebar, children: [
      /* @__PURE__ */ jsxs("form", { onSubmit: ajukan, className: "grid gap-4", children: [
        /* @__PURE__ */ jsxs(DialogHeader, { children: [
          /* @__PURE__ */ jsx(DialogTitle, { children: judul }),
          keterangan ? /* @__PURE__ */ jsx(DialogDescription, { children: keterangan }) : null
        ] }),
        /* @__PURE__ */ jsx("div", { className: "max-h-[60vh] overflow-y-auto pr-1", children: anak }),
        /* @__PURE__ */ jsxs(DialogFooter, { children: [
          /* @__PURE__ */ jsx(Button, { type: "button", variant: "ghost", onClick: () => setBuka(false), children: "Batal" }),
          /* @__PURE__ */ jsx(Button, { type: "submit", disabled: sedangKirim, variant: destruktif ? "destructive" : "default", children: tombolKirim })
        ] })
      ] }),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: konfirmasiJudul,
          pesan: konfirmasiPesan,
          tombolYa: konfirmasiYa,
          destruktif,
          buka: konfirmasi,
          onUbahBuka: setKonfirmasi,
          onKonfirmasi: () => onKirim(() => setBuka(false))
        }
      )
    ] })
  ] });
}
function DialogMasukan({
  doc,
  masukanSaya,
  ...kendali
}) {
  const form = useForm({ isi: "" });
  return /* @__PURE__ */ jsx(
    JendelaFormulir,
    {
      ...kendali,
      judul: /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Message01Icon, strokeWidth: 1.5, className: "size-4", "aria-hidden": "true" }),
        "Beri Masukan — ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: doc.nomor })
      ] }),
      sedangKirim: form.processing,
      konfirmasiJudul: "Kirim Masukan?",
      konfirmasiPesan: `Kirim masukan ini ke Section Head / Departemen Head ${doc.dept ?? ""}?`,
      konfirmasiYa: "Ya, kirim",
      tombolKirim: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: SentIcon, strokeWidth: 1.5, className: "size-4" }),
        "Kirim Masukan"
      ] }),
      onKirim: (tutup) => form.post(route("documents.feedback.store", doc.id), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset("isi");
          tutup();
        }
      }),
      anak: /* @__PURE__ */ jsxs("div", { className: "grid gap-3", children: [
        /* @__PURE__ */ jsxs(Alert, { children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsx(AlertDescription, { children: "Masukan tidak mengubah isi dokumen. Ia dikirim ke atasan Anda sebagai bahan pertimbangan revisi." })
        ] }),
        /* @__PURE__ */ jsx(FieldGroup, { children: /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.isi || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: `isi-${doc.id}`, children: "Isi masukan" }),
          /* @__PURE__ */ jsx(
            Textarea,
            {
              id: `isi-${doc.id}`,
              rows: 4,
              required: true,
              maxLength: 2e3,
              placeholder: "mis. Langkah 5 tidak sesuai kondisi pit; alat pelindung belum disebut.",
              value: form.data.isi,
              "aria-invalid": !!form.errors.isi,
              onChange: (e) => form.setData("isi", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(FieldError, { errors: form.errors.isi ? [{ message: form.errors.isi }] : void 0 })
        ] }) }),
        masukanSaya.length > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx(Separator, {}),
          /* @__PURE__ */ jsx("p", { className: "text-sm font-semibold", children: "Masukan Anda sebelumnya" }),
          /* @__PURE__ */ jsx(DaftarMasukan, { masukan: masukanSaya, bolehMembalas: false })
        ] }) : null
      ] })
    }
  );
}
function DialogRevisi({
  doc,
  masukanRevisi,
  jadwalPembuat,
  tercentang = [],
  ...kendali
}) {
  const form = useForm({ alasan: "", masukan: tercentang });
  const [edisi, revisi] = doc.janji_revisi;
  function alihMasukan(id, pilih) {
    form.setData(
      "masukan",
      pilih ? [...form.data.masukan, id] : form.data.masukan.filter((m) => m !== id)
    );
  }
  return /* @__PURE__ */ jsx(
    JendelaFormulir,
    {
      ...kendali,
      lebar: "sm:max-w-2xl",
      judul: /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RefreshCwIcon, strokeWidth: 1.5, className: "size-4", "aria-hidden": "true" }),
        "Revisi — ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: doc.nomor })
      ] }),
      sedangKirim: form.processing,
      konfirmasiJudul: "Mulai Revisi?",
      konfirmasiPesan: `Buat Edisi ${edisi} Revisi ${revisi} untuk ${doc.nomor}? Versi lama tetap Berlaku sementara sampai versi baru disetujui.`,
      konfirmasiYa: "Ya, revisi",
      tombolKirim: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: RefreshCwIcon, strokeWidth: 1.5, className: "size-4" }),
        "Revisi"
      ] }),
      onKirim: (tutup) => form.post(route("documents.requestRevision", doc.id), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset();
          tutup();
        }
      }),
      anak: /* @__PURE__ */ jsxs("div", { className: "grid gap-3", children: [
        jadwalPembuat ? /* @__PURE__ */ jsxs(Alert, { children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CalendarRemove01Icon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsxs(AlertDescription, { children: [
            jadwalPembuat.nama,
            " (pembuat) sedang ",
            jadwalPembuat.jenis,
            " sampai",
            " ",
            /* @__PURE__ */ jsx("strong", { children: jadwalPembuat.kembali }),
            ". Draft revisi tetap dibuat atas namanya dan menunggu sampai ia kembali."
          ] })
        ] }) : null,
        /* @__PURE__ */ jsx(FieldGroup, { children: /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.alasan || void 0, children: [
          /* @__PURE__ */ jsxs(FieldLabel, { htmlFor: `alasan-${doc.id}`, children: [
            "Apa yang perlu direvisi? ",
            /* @__PURE__ */ jsx("span", { className: "text-destructive", children: "*" })
          ] }),
          /* @__PURE__ */ jsx(
            Textarea,
            {
              id: `alasan-${doc.id}`,
              rows: 4,
              required: true,
              maxLength: 2e3,
              placeholder: "mis. Langkah 5 tidak lagi sesuai kondisi pit terbaru.",
              value: form.data.alasan,
              "aria-invalid": !!form.errors.alasan,
              onChange: (e) => form.setData("alasan", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(FieldDescription, { children: "Dibaca penyusun revisi, SH/DH, dan MD." }),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: form.errors.alasan ? [{ message: form.errors.alasan }] : void 0
            }
          )
        ] }) }),
        masukanRevisi.length > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx(Separator, {}),
          /* @__PURE__ */ jsxs("p", { className: "flex items-center gap-2 text-sm font-semibold", children: [
            /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: Message01Icon,
                strokeWidth: 1.5,
                className: "size-4",
                "aria-hidden": "true"
              }
            ),
            "Masukan Lapangan (",
            masukanRevisi.length,
            ")"
          ] }),
          /* @__PURE__ */ jsxs("p", { className: "text-muted-foreground text-sm", children: [
            "Yang dicentang otomatis ikut tercatat sebagai alasan revisi, dan statusnya menjadi ",
            /* @__PURE__ */ jsx("em", { children: "Diadopsi" }),
            "."
          ] }),
          masukanRevisi.map((m) => /* @__PURE__ */ jsxs(
            Label,
            {
              htmlFor: `msk-${m.id}`,
              className: "grid grid-cols-[auto_1fr] items-start gap-2 rounded-2xl border p-2 font-normal",
              children: [
                /* @__PURE__ */ jsx(
                  Checkbox,
                  {
                    id: `msk-${m.id}`,
                    checked: form.data.masukan.includes(m.id),
                    onCheckedChange: (v) => alihMasukan(m.id, v === true)
                  }
                ),
                /* @__PURE__ */ jsxs("span", { className: "text-sm", children: [
                  /* @__PURE__ */ jsx("span", { className: "text-muted-foreground font-mono", children: m.nomor }),
                  /* @__PURE__ */ jsx("span", { className: "block", children: m.isi }),
                  /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground block text-xs", children: [
                    m.oleh ?? "—",
                    " · ",
                    m.waktu,
                    " WITA"
                  ] })
                ] })
              ]
            },
            m.id
          ))
        ] }) : null
      ] })
    }
  );
}
function DialogNonaktif({
  doc,
  ...kendali
}) {
  const form = useForm({ alasan: "" });
  return /* @__PURE__ */ jsx(
    JendelaFormulir,
    {
      ...kendali,
      judul: /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CircleSlashIcon, strokeWidth: 1.5, className: "size-4", "aria-hidden": "true" }),
        "Ajukan Nonaktif — ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: doc.nomor })
      ] }),
      sedangKirim: form.processing,
      konfirmasiJudul: "Ajukan Nonaktif?",
      konfirmasiPesan: "Pengajuan dikirim ke SH/DH departemen, lalu Management Development, lalu PJO. Dokumen tetap Berlaku sampai keputusan terakhir.",
      konfirmasiYa: "Ya, ajukan",
      tombolKirim: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: CircleSlashIcon, strokeWidth: 1.5, className: "size-4" }),
        "Ajukan Nonaktif"
      ] }),
      onKirim: (tutup) => form.post(route("nonaktif.ajukan", doc.id), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset();
          tutup();
        }
      }),
      anak: /* @__PURE__ */ jsxs("div", { className: "grid gap-3", children: [
        /* @__PURE__ */ jsxs(Alert, { children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsxs(AlertDescription, { children: [
            "Bila disetujui sampai tahap terakhir, dokumen menjadi ",
            /* @__PURE__ */ jsx("strong", { children: "Tidak Berlaku" }),
            " ",
            "dan ",
            /* @__PURE__ */ jsx("strong", { children: "nomornya dilepas" }),
            " — nomor itu bisa dipakai dokumen baru."
          ] })
        ] }),
        /* @__PURE__ */ jsx(FieldGroup, { children: /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.alasan || void 0, children: [
          /* @__PURE__ */ jsxs(FieldLabel, { htmlFor: `alasan-nonaktif-${doc.id}`, children: [
            "Alasan nonaktif ",
            /* @__PURE__ */ jsx("span", { className: "text-destructive", children: "*" })
          ] }),
          /* @__PURE__ */ jsx(
            Textarea,
            {
              id: `alasan-nonaktif-${doc.id}`,
              rows: 3,
              required: true,
              maxLength: 2e3,
              placeholder: "mis. Pekerjaannya sudah tidak dilakukan sejak alat X dipensiunkan.",
              value: form.data.alasan,
              "aria-invalid": !!form.errors.alasan,
              onChange: (e) => form.setData("alasan", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(FieldDescription, { children: "Dibaca SH/DH, Management Development, dan PJO; tercatat di Log Dokumen." }),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: form.errors.alasan ? [{ message: form.errors.alasan }] : void 0
            }
          )
        ] }) })
      ] })
    }
  );
}
function DialogMusnahkan({
  doc,
  ...kendali
}) {
  const form = useForm({ alasan: "", konfirmasi_nomor: "" });
  return /* @__PURE__ */ jsx(
    JendelaFormulir,
    {
      ...kendali,
      destruktif: true,
      judul: /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: OctagonAlertIcon,
            strokeWidth: 1.5,
            className: "text-destructive size-4",
            "aria-hidden": "true"
          }
        ),
        "Musnahkan Dokumen"
      ] }),
      sedangKirim: form.processing,
      konfirmasiJudul: "Musnahkan Dokumen?",
      konfirmasiPesan: `Musnahkan ${doc.nomor}? Isi, riwayat tinjauan, persetujuan, versi, dan masukan ikut hilang permanen.`,
      konfirmasiYa: "Ya, musnahkan",
      tombolKirim: /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }),
        "Musnahkan"
      ] }),
      onKirim: (tutup) => form.delete(route("documents.purge", doc.id), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset();
          tutup();
        }
      }),
      anak: /* @__PURE__ */ jsxs("div", { className: "grid gap-3", children: [
        /* @__PURE__ */ jsxs(Alert, { variant: "destructive", children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: OctagonAlertIcon, strokeWidth: 1.5, className: "size-4" }),
          /* @__PURE__ */ jsxs(AlertDescription, { children: [
            /* @__PURE__ */ jsx("span", { className: "font-semibold", children: doc.nomor }),
            " — ",
            doc.judul,
            ". Seluruh isi, versi, riwayat, lampiran, dan masukan hilang",
            " ",
            /* @__PURE__ */ jsx("span", { className: "font-semibold", children: "tanpa bisa dikembalikan" }),
            ". Nomornya kembali ke kolam."
          ] })
        ] }),
        /* @__PURE__ */ jsxs(FieldGroup, { children: [
          /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.alasan || void 0, children: [
            /* @__PURE__ */ jsx(FieldLabel, { htmlFor: `musnah-alasan-${doc.id}`, children: "Alasan penghapusan" }),
            /* @__PURE__ */ jsx(
              Textarea,
              {
                id: `musnah-alasan-${doc.id}`,
                rows: 3,
                required: true,
                minLength: 10,
                placeholder: "mis. dokumen ganda, terbit karena kesalahan input",
                value: form.data.alasan,
                "aria-invalid": !!form.errors.alasan,
                onChange: (e) => form.setData("alasan", e.target.value)
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
          /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.konfirmasi_nomor || void 0, children: [
            /* @__PURE__ */ jsx(FieldLabel, { htmlFor: `musnah-nomor-${doc.id}`, children: "Ketik ulang nomor dokumen" }),
            /* @__PURE__ */ jsx(
              Input,
              {
                id: `musnah-nomor-${doc.id}`,
                required: true,
                autoComplete: "off",
                placeholder: doc.nomor,
                value: form.data.konfirmasi_nomor,
                "aria-invalid": !!form.errors.konfirmasi_nomor,
                onChange: (e) => form.setData("konfirmasi_nomor", e.target.value)
              }
            ),
            /* @__PURE__ */ jsxs(FieldDescription, { children: [
              "Harus persis ",
              /* @__PURE__ */ jsx("span", { className: "font-semibold", children: doc.nomor }),
              "."
            ] }),
            /* @__PURE__ */ jsx(
              FieldError,
              {
                errors: form.errors.konfirmasi_nomor ? [{ message: form.errors.konfirmasi_nomor }] : void 0
              }
            )
          ] })
        ] })
      ] })
    }
  );
}
export {
  DialogMusnahkan as D,
  DialogMasukan as a,
  DialogRevisi as b,
  DialogNonaktif as c,
  DaftarMasukan as d
};
