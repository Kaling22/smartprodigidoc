import { jsxs, jsx } from "react/jsx-runtime";
import { PlusSignIcon, HierarchyIcon, UserSettings01Icon, Tick02Icon, MinusSignIcon, PencilIcon, Delete02Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, router } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, D as Dialog, b as DialogTrigger, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, g as DialogFooter, h as DialogClose, B as Badge, I as Ikon } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { C as Checkbox } from "./checkbox-DZTcz7W1.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription } from "./empty-CkAl4IHy.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError, b as FieldDescription } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { P as PenyaringDokumen, s as saringDepartemen } from "./PenyaringDokumen-ZbRxHXvI.js";
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
import "./input-group-C5LPhh_3.js";
const KOSONG = "*";
function AksesIndex({
  profiles,
  groupLeaders,
  departments,
  filters,
  jenis,
  rupa
}) {
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: "Manajemen Akses",
      aksi: /* @__PURE__ */ jsx(
        DialogProfil,
        {
          profil: null,
          jenis,
          rupa,
          pemicu: /* @__PURE__ */ jsxs(Button, { children: [
            /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: PlusSignIcon,
                strokeWidth: 1.5,
                className: "size-4"
              }
            ),
            "Profil Baru"
          ] })
        }
      ),
      children: [
        /* @__PURE__ */ jsx(KartuProfil, { profiles, jenis, rupa }),
        /* @__PURE__ */ jsx(
          KartuPenetapan,
          {
            profiles,
            groupLeaders,
            departments,
            filters
          }
        )
      ]
    }
  );
}
function KartuProfil({
  profiles,
  jenis,
  rupa
}) {
  const kolom = [
    {
      judul: "Nama Profil",
      render: (p) => /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("div", { className: "font-medium", children: p.nama }),
        p.keterangan ? /* @__PURE__ */ jsx("div", { className: "text-muted-foreground text-xs", children: p.keterangan }) : null
      ] })
    },
    {
      judul: "Jenis Dokumen",
      render: (p) => p.jenis_dibolehkan.length === 0 ? /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: "— tidak menyusun —" }) : /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-1", children: p.jenis_dibolehkan.map((kode) => /* @__PURE__ */ jsx(LencanaJenis, { kode, rupa }, kode)) })
    },
    {
      judul: "Tinjau JSA",
      render: (p) => /* @__PURE__ */ jsxs(Badge, { variant: p.boleh_review_jsa ? "secondary" : "outline", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: p.boleh_review_jsa ? Tick02Icon : MinusSignIcon,
            strokeWidth: 2,
            className: "size-3"
          }
        ),
        p.boleh_review_jsa ? "Ya" : "Tidak"
      ] })
    },
    {
      judul: "Pengguna",
      kelas: "text-center",
      render: (p) => /* @__PURE__ */ jsx(Badge, { variant: p.users_count > 0 ? "secondary" : "outline", children: p.users_count })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (p) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(
          DialogProfil,
          {
            profil: p,
            jenis,
            rupa,
            pemicu: /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: PencilIcon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              ),
              "Ubah"
            ] })
          }
        ),
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            judul: `Hapus profil "${p.nama}"?`,
            pesan: p.users_count > 0 ? `${p.users_count} pengguna akan kehilangan aksesnya dan kembali ke Tanpa Akses.` : "Profil ini belum dipakai siapa pun.",
            tombolYa: "Ya, hapus",
            destruktif: true,
            onKonfirmasi: () => router.delete(route("akses.destroy", p.id), { preserveScroll: true }),
            pemicu: /* @__PURE__ */ jsx(
              Button,
              {
                variant: "outline",
                size: "icon",
                "aria-label": `Hapus profil ${p.nama}`,
                children: /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: Delete02Icon,
                    strokeWidth: 1.5,
                    className: "size-4"
                  }
                )
              }
            )
          }
        )
      ] })
    }
  ];
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "border-b", children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: HierarchyIcon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Profil Akses"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Buat profil sekali, tetapkan ke banyak orang. Mengubah profil langsung mengubah wewenang semua penggunanya." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
      DataTable,
      {
        kolom,
        baris: profiles,
        kunci: (p) => p.id,
        kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
          /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: HierarchyIcon,
              strokeWidth: 1.5,
              className: "size-6"
            }
          ) }),
          /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada profil akses." }),
          /* @__PURE__ */ jsx(EmptyDescription, { children: "Selama belum ada, tak seorang Group Leader pun bisa menyusun dokumen." })
        ] }) })
      }
    ) })
  ] });
}
function KartuPenetapan({
  profiles,
  groupLeaders,
  departments,
  filters
}) {
  const kolom = [
    { judul: "Nama", render: (g) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: g.name }) },
    { judul: "NRP", render: (g) => /* @__PURE__ */ jsx("span", { className: "font-mono text-sm", children: g.nrp ?? "—" }) },
    { judul: "Dept", render: (g) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: g.dept ?? "—" }) },
    {
      judul: "Profil Akses",
      kelas: "min-w-[280px]",
      render: (g) => /* @__PURE__ */ jsx(FormTetapkan, { gl: g, profiles })
    }
  ];
  const saringProfil = {
    nama: "access_profile_id",
    label: "Profil Akses",
    opsi: [
      ["", "Semua"],
      ["tanpa", "— Tanpa Akses —"],
      ...profiles.map((p) => [String(p.id), p.nama])
    ]
  };
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "border-b", children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: UserSettings01Icon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Penetapan Akses"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Group Leader saja. Admin IT berwenang penuh tanpa profil; SH/DH/PJO/Non-Staff tidak menyusun dokumen sehingga tidak ditetapkan." }),
      /* @__PURE__ */ jsx(
        PenyaringDokumen,
        {
          url: route("akses.index"),
          filters,
          pilihan: [saringDepartemen(departments), saringProfil],
          labelCari: "Cari (nama / NRP)",
          placeholderCari: "mis. nama atau NRP…"
        }
      )
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
      DataTable,
      {
        kolom,
        baris: groupLeaders.data,
        kunci: (g) => g.id,
        kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
          /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: UserSettings01Icon,
              strokeWidth: 1.5,
              className: "size-6"
            }
          ) }),
          /* @__PURE__ */ jsx(EmptyTitle, { children: filters.q || filters.department_id || filters.access_profile_id ? "Tidak ada Group Leader yang cocok dengan filter." : "Belum ada akun Group Leader." })
        ] }) })
      }
    ) }),
    /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: groupLeaders }) })
  ] });
}
function FormTetapkan({ gl, profiles }) {
  const { data, setData, post, processing } = useForm({
    access_profile_id: gl.access_profile_id ? String(gl.access_profile_id) : ""
  });
  function kirim(e) {
    e.preventDefault();
    post(route("akses.tetapkan", gl.id), { preserveScroll: true });
  }
  return /* @__PURE__ */ jsxs("form", { onSubmit: kirim, className: "flex gap-2", children: [
    /* @__PURE__ */ jsxs(
      Select,
      {
        value: data.access_profile_id || KOSONG,
        onValueChange: (v) => setData("access_profile_id", v === KOSONG ? "" : v),
        children: [
          /* @__PURE__ */ jsx(SelectTrigger, { className: "w-full", "aria-label": `Profil akses ${gl.name}`, children: /* @__PURE__ */ jsx(SelectValue, {}) }),
          /* @__PURE__ */ jsxs(SelectContent, { children: [
            /* @__PURE__ */ jsx(SelectItem, { value: KOSONG, children: "— Tanpa Akses —" }),
            profiles.map((p) => /* @__PURE__ */ jsx(SelectItem, { value: String(p.id), children: p.nama }, p.id))
          ] })
        ]
      }
    ),
    /* @__PURE__ */ jsxs(Button, { type: "submit", variant: "outline", size: "sm", disabled: processing, children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Tick02Icon, strokeWidth: 1.5, className: "size-4" }),
      "Simpan"
    ] })
  ] });
}
function DialogProfil({
  profil,
  jenis,
  rupa,
  pemicu
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    nama: profil?.nama ?? "",
    keterangan: profil?.keterangan ?? "",
    jenis_dibolehkan: profil?.jenis_dibolehkan ?? [],
    boleh_review_jsa: profil?.boleh_review_jsa ?? false
  });
  function pilihJenis(kode, aktif) {
    form.setData(
      "jenis_dibolehkan",
      aktif ? [...form.data.jenis_dibolehkan, kode] : form.data.jenis_dibolehkan.filter((k) => k !== kode)
    );
  }
  function kirim(e) {
    e.preventDefault();
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };
    if (profil) {
      form.put(route("akses.update", profil.id), opsi);
    } else {
      form.post(route("akses.store"), opsi);
    }
  }
  const kunci = profil?.id ?? "baru";
  return /* @__PURE__ */ jsxs(Dialog, { open: buka, onOpenChange: setBuka, children: [
    /* @__PURE__ */ jsx(DialogTrigger, { asChild: true, children: pemicu }),
    /* @__PURE__ */ jsx(DialogContent, { children: /* @__PURE__ */ jsxs("form", { onSubmit: kirim, className: "grid gap-6", children: [
      /* @__PURE__ */ jsxs(DialogHeader, { children: [
        /* @__PURE__ */ jsx(DialogTitle, { children: profil ? "Ubah Profil" : "Profil Baru" }),
        /* @__PURE__ */ jsx(DialogDescription, { children: profil && profil.users_count > 0 ? `Perubahan ini langsung berlaku bagi ${profil.users_count} pengguna.` : "Tentukan jenis dokumen yang boleh disusun pemegang profil ini." })
      ] }),
      /* @__PURE__ */ jsxs(FieldGroup, { children: [
        /* @__PURE__ */ jsxs(Field, { "data-invalid": !!form.errors.nama || void 0, children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: `nama-${kunci}`, children: "Nama Profil" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: `nama-${kunci}`,
              required: true,
              maxLength: 255,
              placeholder: "Penyusun SOP & IK",
              value: form.data.nama,
              "aria-invalid": !!form.errors.nama,
              onChange: (e) => form.setData("nama", e.target.value)
            }
          ),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: form.errors.nama ? [{ message: form.errors.nama }] : void 0
            }
          )
        ] }),
        /* @__PURE__ */ jsxs(Field, { children: [
          /* @__PURE__ */ jsx(FieldLabel, { htmlFor: `ket-${kunci}`, children: "Keterangan (opsional)" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: `ket-${kunci}`,
              maxLength: 255,
              placeholder: "Untuk GL yang hanya menyusun prosedur",
              value: form.data.keterangan ?? "",
              onChange: (e) => form.setData("keterangan", e.target.value)
            }
          )
        ] }),
        /* @__PURE__ */ jsxs(Field, { children: [
          /* @__PURE__ */ jsx(FieldLabel, { children: "Jenis dokumen yang boleh disusun" }),
          /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-4", children: jenis.map((kode) => {
            const id = `jn-${kunci}-${kode}`;
            return /* @__PURE__ */ jsxs(
              Field,
              {
                orientation: "horizontal",
                className: "w-auto gap-2",
                children: [
                  /* @__PURE__ */ jsx(
                    Checkbox,
                    {
                      id,
                      checked: form.data.jenis_dibolehkan.includes(kode),
                      onCheckedChange: (v) => pilihJenis(kode, v === true)
                    }
                  ),
                  /* @__PURE__ */ jsx(FieldLabel, { htmlFor: id, className: "font-normal", children: /* @__PURE__ */ jsx(LencanaJenis, { kode, rupa }) })
                ]
              },
              kode
            );
          }) }),
          /* @__PURE__ */ jsx(FieldDescription, { children: "Tak satu pun dicentang = profil ini tidak memberi wewenang menyusun." })
        ] }),
        /* @__PURE__ */ jsxs(Field, { children: [
          /* @__PURE__ */ jsxs(Field, { orientation: "horizontal", className: "gap-2", children: [
            /* @__PURE__ */ jsx(
              Checkbox,
              {
                id: `jsa-${kunci}`,
                checked: form.data.boleh_review_jsa,
                onCheckedChange: (v) => form.setData("boleh_review_jsa", v === true)
              }
            ),
            /* @__PURE__ */ jsxs(FieldLabel, { htmlFor: `jsa-${kunci}`, className: "font-normal", children: [
              "Boleh ",
              /* @__PURE__ */ jsx("strong", { children: "meninjau" }),
              " dokumen JSA"
            ] })
          ] }),
          /* @__PURE__ */ jsx(FieldDescription, { children: "GL departemen SHE sudah berwenang otomatis — centang ini untuk GL departemen lain." })
        ] })
      ] }),
      /* @__PURE__ */ jsxs(DialogFooter, { children: [
        /* @__PURE__ */ jsx(DialogClose, { asChild: true, children: /* @__PURE__ */ jsx(Button, { type: "button", variant: "ghost", children: "Batal" }) }),
        /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: form.processing, children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Tick02Icon, strokeWidth: 1.5, className: "size-4" }),
          "Simpan"
        ] })
      ] })
    ] }) })
  ] });
}
function LencanaJenis({ kode, rupa }) {
  const [ikon, warna] = rupa[kode] ?? [];
  return /* @__PURE__ */ jsxs(Badge, { variant: "outline", children: [
    /* @__PURE__ */ jsx(Ikon, { nama: ikon, className: "size-3.5" }),
    /* @__PURE__ */ jsx("span", { style: warna ? { color: warna } : void 0, children: kode })
  ] });
}
export {
  AksesIndex as default
};
