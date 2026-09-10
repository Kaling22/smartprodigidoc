import { jsxs, jsx } from "react/jsx-runtime";
import { InformationCircleIcon, Building02Icon, Files01Icon, Idea01Icon, LockIcon, PlusSignIcon, FloppyDiskIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm } from "@inertiajs/react";
import { useState } from "react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { A as AppLayout, B as Badge, I as Ikon } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { C as Checkbox } from "./checkbox-DZTcz7W1.js";
import { c as FieldError } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { I as InputGroup, b as InputGroupAddon, a as InputGroupInput } from "./input-group-C5LPhh_3.js";
import { S as Switch } from "./switch-3nvfOMMe.js";
import { T as Table, a as TableHeader, b as TableRow, c as TableHead, d as TableBody, e as TableCell } from "./table-COSAIfdh.js";
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
function PengaturanMaster({
  departemen,
  jenis,
  kategori,
  jumlahInformasi,
  kolomTersedia,
  rupa
}) {
  return /* @__PURE__ */ jsxs(AppLayout, { judul: "Master Data", children: [
    /* @__PURE__ */ jsxs(Alert, { children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: InformationCircleIcon, strokeWidth: 1.5, className: "size-4" }),
      /* @__PURE__ */ jsxs(AlertDescription, { children: [
        "Tidak ada penghapusan di layar ini. Departemen, jenis, dan kategori ditunjuk oleh dokumen & pengguna yang sudah ada, jadi yang tersedia adalah saklar",
        " ",
        /* @__PURE__ */ jsx("strong", { children: "Aktif" }),
        ": barisnya berhenti ditawarkan pada formulir baru, sementara seluruh dokumen dan penggunanya tetap utuh dan tetap terbaca."
      ] })
    ] }),
    /* @__PURE__ */ jsx(KartuDepartemen, { departemen }),
    /* @__PURE__ */ jsx(KartuJenis, { jenis, rupa }),
    /* @__PURE__ */ jsx(
      KartuKategori,
      {
        kategori,
        jumlahInformasi,
        kolomTersedia
      }
    )
  ] });
}
function KartuDepartemen({ departemen }) {
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: Building02Icon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Departemen"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Kode departemen ikut masuk ke nomor dokumen, jadi ia terkunci begitu departemen itu punya dokumen." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx("div", { className: "overflow-x-auto", children: /* @__PURE__ */ jsxs(Table, { children: [
      /* @__PURE__ */ jsx(TableHeader, { children: /* @__PURE__ */ jsxs(TableRow, { children: [
        /* @__PURE__ */ jsx(TableHead, { className: "w-36", children: "Kode" }),
        /* @__PURE__ */ jsx(TableHead, { children: "Nama" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-32", children: "Alias" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-24 text-center", children: "Dokumen" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-24 text-center", children: "Pengguna" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-20 text-center", children: "Aktif" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-20" })
      ] }) }),
      /* @__PURE__ */ jsxs(TableBody, { children: [
        departemen.map((d) => /* @__PURE__ */ jsx(BarisDept, { d }, d.id)),
        /* @__PURE__ */ jsx(BarisDept, { d: null })
      ] })
    ] }) }) })
  ] });
}
function BarisDept({ d }) {
  const idForm = d ? `dept-${d.id}` : "dept-baru";
  const form = useForm({
    code: d?.code ?? "",
    name: d?.name ?? "",
    alias: d?.alias ?? "",
    is_active: d ? d.is_active : true
  });
  const terkunci = !!d && d.documents_count > 0;
  return /* @__PURE__ */ jsxs(
    BarisSimpan,
    {
      form,
      idForm,
      baru: !d,
      judulKonfirmasi: d ? `Simpan ${d.code}?` : "Tambah departemen?",
      pesanKonfirmasi: "Departemen yang dinonaktifkan berhenti ditawarkan pada pendaftaran akun & pembuatan dokumen. Dokumen dan penggunanya tidak berubah.",
      kirim: () => d ? form.put(route("pengaturan.master.departemen.simpan", d.id), {
        preserveScroll: true,
        errorBag: idForm
      }) : form.post(route("pengaturan.master.departemen.tambah"), {
        preserveScroll: true,
        errorBag: idForm,
        onSuccess: () => form.reset()
      }),
      children: [
        /* @__PURE__ */ jsxs(TableCell, { children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              form: idForm,
              className: "font-mono uppercase",
              maxLength: 20,
              required: !!d,
              placeholder: d ? void 0 : "KODE",
              disabled: terkunci,
              "aria-label": `Kode departemen ${d?.code ?? "baru"}`,
              value: form.data.code,
              onChange: (e) => form.setData("code", e.target.value),
              "aria-invalid": !!form.errors.code
            }
          ),
          terkunci ? /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground mt-1 flex items-center gap-1 text-[.68rem]", children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: LockIcon, strokeWidth: 2, className: "size-3", "aria-hidden": "true" }),
            "terkunci oleh ",
            d.documents_count,
            " dokumen"
          ] }) : null,
          /* @__PURE__ */ jsx(Galat, { pesan: form.errors.code })
        ] }),
        /* @__PURE__ */ jsxs(TableCell, { children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              form: idForm,
              maxLength: 100,
              required: !!d,
              placeholder: d ? void 0 : "Nama departemen baru",
              "aria-label": `Nama departemen ${d?.code ?? "baru"}`,
              value: form.data.name,
              onChange: (e) => form.setData("name", e.target.value),
              "aria-invalid": !!form.errors.name
            }
          ),
          /* @__PURE__ */ jsx(Galat, { pesan: form.errors.name })
        ] }),
        /* @__PURE__ */ jsxs(TableCell, { children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              form: idForm,
              className: "uppercase",
              maxLength: 50,
              placeholder: "Alias",
              "aria-label": `Alias departemen ${d?.code ?? "baru"}`,
              value: form.data.alias ?? "",
              onChange: (e) => form.setData("alias", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(Galat, { pesan: form.errors.alias })
        ] }),
        /* @__PURE__ */ jsx(TableCell, { className: "text-center", children: d ? /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: d.documents_count }) : /* @__PURE__ */ jsx(Kosong, {}) }),
        /* @__PURE__ */ jsx(TableCell, { className: "text-center", children: d ? /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: d.users_count }) : /* @__PURE__ */ jsx(Kosong, {}) }),
        /* @__PURE__ */ jsx(
          SelAktif,
          {
            nilai: form.data.is_active,
            onUbah: (v) => form.setData("is_active", v),
            label: `Departemen ${d?.code ?? "baru"} aktif`
          }
        )
      ]
    }
  );
}
function KartuJenis({ jenis, rupa }) {
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: Files01Icon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Jenis Dokumen"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Nama & saklar aktif saja. Menambah jenis baru menuntut schema JSON dan template cetak, jadi ia tidak bisa dibuat dari layar mana pun." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx("div", { className: "overflow-x-auto", children: /* @__PURE__ */ jsxs(Table, { children: [
      /* @__PURE__ */ jsx(TableHeader, { children: /* @__PURE__ */ jsxs(TableRow, { children: [
        /* @__PURE__ */ jsx(TableHead, { className: "w-28", children: "Kode" }),
        /* @__PURE__ */ jsx(TableHead, { children: "Nama" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-28 text-center", children: "Berjalan" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-28 text-center", children: "Berlaku" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-20 text-center", children: "Aktif" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-20" })
      ] }) }),
      /* @__PURE__ */ jsx(TableBody, { children: jenis.map((j) => /* @__PURE__ */ jsx(BarisJenisDok, { j, rupa }, j.id)) })
    ] }) }) })
  ] });
}
function BarisJenisDok({ j, rupa }) {
  const idForm = `jenis-${j.id}`;
  const form = useForm({ name: j.name, is_active: j.is_active });
  const [ikon, warna] = rupa[j.code] ?? ["bi-file-earmark-text", "#8392ab"];
  return /* @__PURE__ */ jsxs(
    BarisSimpan,
    {
      form,
      idForm,
      judulKonfirmasi: `Simpan ${j.code}?`,
      pesanKonfirmasi: `Jenis yang dinonaktifkan hilang dari menu Dokumen Baru dan tidak bisa disusun lagi. Dokumen ${j.code} yang sudah ada tetap terbaca dan tetap bisa dicetak.`,
      kirim: () => form.put(route("pengaturan.master.jenis.simpan", j.id), {
        preserveScroll: true,
        errorBag: idForm
      }),
      children: [
        /* @__PURE__ */ jsx(TableCell, { children: /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-1.5 font-mono font-semibold", style: { color: warna }, children: [
          /* @__PURE__ */ jsx(Ikon, { nama: ikon, className: "size-4" }),
          j.code
        ] }) }),
        /* @__PURE__ */ jsxs(TableCell, { children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              form: idForm,
              maxLength: 100,
              required: true,
              "aria-label": `Nama jenis ${j.code}`,
              value: form.data.name,
              onChange: (e) => form.setData("name", e.target.value),
              "aria-invalid": !!form.errors.name
            }
          ),
          /* @__PURE__ */ jsx(Galat, { pesan: form.errors.name })
        ] }),
        /* @__PURE__ */ jsx(TableCell, { className: "text-center", children: /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: j.berjalan_count }) }),
        /* @__PURE__ */ jsx(TableCell, { className: "text-center", children: /* @__PURE__ */ jsx(Badge, { variant: "default", children: j.berlaku_count }) }),
        /* @__PURE__ */ jsx(
          SelAktif,
          {
            nilai: form.data.is_active,
            onUbah: (v) => form.setData("is_active", v),
            label: `Jenis ${j.code} aktif`
          }
        )
      ]
    }
  );
}
function KartuKategori({
  kategori,
  jumlahInformasi,
  kolomTersedia
}) {
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: InformationCircleIcon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Kategori Informasi"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Submenu pada menu Informasi. Kategori baru langsung tampil di sidebar dan di aplikasi mobile — tanpa rilis kode." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx("div", { className: "overflow-x-auto", children: /* @__PURE__ */ jsxs(Table, { children: [
      /* @__PURE__ */ jsx(TableHeader, { children: /* @__PURE__ */ jsxs(TableRow, { children: [
        /* @__PURE__ */ jsx(TableHead, { className: "w-52", children: "Nama" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-44", children: "Ikon" }),
        /* @__PURE__ */ jsx(TableHead, { children: "Deskripsi" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-56", children: "Kolom yang dipakai" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-24 text-center", children: "Dokumen" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-20 text-center", children: "Aktif" }),
        /* @__PURE__ */ jsx(TableHead, { className: "w-20" })
      ] }) }),
      /* @__PURE__ */ jsxs(TableBody, { children: [
        kategori.map((k) => /* @__PURE__ */ jsx(
          BarisKat,
          {
            k,
            jumlah: jumlahInformasi[k.slug] ?? 0,
            kolomTersedia
          },
          k.id
        )),
        /* @__PURE__ */ jsx(BarisKat, { k: null, jumlah: 0, kolomTersedia })
      ] })
    ] }) }) }),
    /* @__PURE__ */ jsxs(CardFooter, { className: "text-muted-foreground border-t gap-2 pt-6 text-sm", children: [
      /* @__PURE__ */ jsx(
        HugeiconsIcon,
        {
          icon: Idea01Icon,
          strokeWidth: 1.5,
          className: "mt-0.5 size-4 shrink-0",
          "aria-hidden": "true"
        }
      ),
      /* @__PURE__ */ jsxs("span", { children: [
        "Kolom yang bisa dipilih hanya ",
        /* @__PURE__ */ jsx("strong", { children: "Edisi" }),
        ", ",
        /* @__PURE__ */ jsx("strong", { children: "No. Revisi" }),
        ", dan ",
        /* @__PURE__ */ jsx("strong", { children: "Tanggal Efektif" }),
        " — ketiganya sudah ada di tabel informasi. Nomor & judul selalu dipakai. Nama ikon diambil dari",
        " ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: "bootstrap-icons" }),
        ", mis.",
        " ",
        /* @__PURE__ */ jsx("span", { className: "font-mono", children: "bi-journal-bookmark" }),
        "."
      ] })
    ] })
  ] });
}
function BarisKat({
  k,
  jumlah,
  kolomTersedia
}) {
  const idForm = k ? `kat-${k.id}` : "kat-baru";
  const form = useForm({
    nama: k?.nama ?? "",
    deskripsi: k?.deskripsi ?? "",
    ikon: k?.ikon ?? "bi-info-circle",
    kolom: k?.kolom ?? [],
    is_active: k ? k.is_active : true
  });
  function pilihKolom(kunci, aktif) {
    form.setData(
      "kolom",
      aktif ? [...form.data.kolom, kunci] : form.data.kolom.filter((x) => x !== kunci)
    );
  }
  return /* @__PURE__ */ jsxs(
    BarisSimpan,
    {
      form,
      idForm,
      baru: !k,
      judulKonfirmasi: k ? `Simpan ${k.nama}?` : "Tambah kategori?",
      pesanKonfirmasi: "Kategori yang dinonaktifkan tetap tampil di menu Informasi dalam keadaan tak bisa diklik, dan tidak menerima unggahan baru. Dokumen yang sudah ada tidak dihapus.",
      kirim: () => k ? form.put(route("pengaturan.master.kategori.simpan", k.id), {
        preserveScroll: true,
        errorBag: idForm
      }) : form.post(route("pengaturan.master.kategori.tambah"), {
        preserveScroll: true,
        errorBag: idForm,
        onSuccess: () => form.reset()
      }),
      children: [
        /* @__PURE__ */ jsxs(TableCell, { children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              form: idForm,
              maxLength: 60,
              required: !!k,
              placeholder: k ? void 0 : "Nama kategori baru",
              "aria-label": `Nama kategori ${k?.nama ?? "baru"}`,
              value: form.data.nama,
              onChange: (e) => form.setData("nama", e.target.value),
              "aria-invalid": !!form.errors.nama
            }
          ),
          /* @__PURE__ */ jsx("span", { className: "text-muted-foreground mt-1 block font-mono text-[.68rem]", children: k ? k.slug : "kuncinya dibuat otomatis dari nama" }),
          /* @__PURE__ */ jsx(Galat, { pesan: form.errors.nama })
        ] }),
        /* @__PURE__ */ jsxs(TableCell, { children: [
          /* @__PURE__ */ jsxs(InputGroup, { children: [
            /* @__PURE__ */ jsx(InputGroupAddon, { children: /* @__PURE__ */ jsx(Ikon, { nama: form.data.ikon, className: "size-4" }) }),
            /* @__PURE__ */ jsx(
              InputGroupInput,
              {
                form: idForm,
                className: "font-mono",
                maxLength: 50,
                required: !!k,
                "aria-label": `Ikon kategori ${k?.nama ?? "baru"}`,
                value: form.data.ikon,
                onChange: (e) => form.setData("ikon", e.target.value),
                "aria-invalid": !!form.errors.ikon
              }
            )
          ] }),
          /* @__PURE__ */ jsx(Galat, { pesan: form.errors.ikon })
        ] }),
        /* @__PURE__ */ jsxs(TableCell, { children: [
          /* @__PURE__ */ jsx(
            Input,
            {
              form: idForm,
              maxLength: 255,
              placeholder: "opsional",
              "aria-label": `Deskripsi kategori ${k?.nama ?? "baru"}`,
              value: form.data.deskripsi ?? "",
              onChange: (e) => form.setData("deskripsi", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(Galat, { pesan: form.errors.deskripsi })
        ] }),
        /* @__PURE__ */ jsx(TableCell, { children: /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-3", children: Object.entries(kolomTersedia).map(([kunci, label]) => {
          const id = `kol-${k?.id ?? "baru"}-${kunci}`;
          return /* @__PURE__ */ jsxs("label", { htmlFor: id, className: "flex items-center gap-1.5 text-sm", children: [
            /* @__PURE__ */ jsx(
              Checkbox,
              {
                id,
                checked: form.data.kolom.includes(kunci),
                onCheckedChange: (v) => pilihKolom(kunci, v === true)
              }
            ),
            label
          ] }, kunci);
        }) }) }),
        /* @__PURE__ */ jsx(TableCell, { className: "text-center", children: k ? /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: jumlah }) : /* @__PURE__ */ jsx(Kosong, {}) }),
        /* @__PURE__ */ jsx(
          SelAktif,
          {
            nilai: form.data.is_active,
            onUbah: (v) => form.setData("is_active", v),
            label: `Kategori ${k?.nama ?? "baru"} aktif`
          }
        )
      ]
    }
  );
}
function BarisSimpan({
  form,
  idForm,
  baru = false,
  judulKonfirmasi,
  pesanKonfirmasi,
  kirim,
  children
}) {
  const [konfirmasi, setKonfirmasi] = useState(false);
  function minta(e) {
    e.preventDefault();
    setKonfirmasi(true);
  }
  return /* @__PURE__ */ jsxs(TableRow, { className: baru ? "bg-muted/40" : void 0, children: [
    children,
    /* @__PURE__ */ jsxs(TableCell, { className: "text-right align-top", children: [
      /* @__PURE__ */ jsx("form", { id: idForm, onSubmit: minta }),
      /* @__PURE__ */ jsx(
        Button,
        {
          type: "submit",
          form: idForm,
          size: "icon",
          variant: baru ? "default" : "outline",
          disabled: form.processing,
          title: baru ? "Tambah" : "Simpan",
          "aria-label": baru ? "Tambah" : "Simpan",
          children: /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: baru ? PlusSignIcon : FloppyDiskIcon,
              strokeWidth: 1.5,
              className: "size-4"
            }
          )
        }
      ),
      /* @__PURE__ */ jsx(
        ConfirmDialog,
        {
          judul: judulKonfirmasi,
          pesan: pesanKonfirmasi,
          tombolYa: baru ? "Ya, tambahkan" : "Ya, simpan",
          buka: konfirmasi,
          onUbahBuka: setKonfirmasi,
          onKonfirmasi: kirim
        }
      )
    ] })
  ] });
}
function SelAktif({
  nilai,
  onUbah,
  label
}) {
  return /* @__PURE__ */ jsx(TableCell, { className: "text-center", children: /* @__PURE__ */ jsx("div", { className: "flex justify-center", children: /* @__PURE__ */ jsx(Switch, { checked: nilai, onCheckedChange: onUbah, "aria-label": label }) }) });
}
function Galat({ pesan }) {
  return pesan ? /* @__PURE__ */ jsx(FieldError, { className: "mt-1", errors: [{ message: pesan }] }) : null;
}
function Kosong() {
  return /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "—" });
}
export {
  PengaturanMaster as default
};
