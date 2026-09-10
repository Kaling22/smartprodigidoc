import { jsxs, jsx } from "react/jsx-runtime";
import { UserSettings01Icon, UserAdd01Icon, IdentityCardIcon, SpellCheckIcon, Alert02Icon, CircleSlashIcon, CheckmarkCircle01Icon, Delete02Icon, AiBrain01Icon, Settings01Icon, ToggleOnIcon, ShieldKeyIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { usePage, Link, router, useForm } from "@inertiajs/react";
import { useState } from "react";
import { A as AppLayout, B as Badge, o as DropdownMenuItem, D as Dialog, b as DialogTrigger, c as DialogContent, d as DialogHeader, e as DialogTitle, f as DialogDescription, S as Separator } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent, e as CardFooter } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle, d as EmptyDescription } from "./empty-CkAl4IHy.js";
import { F as Field, a as FieldLabel, b as FieldDescription, c as FieldError } from "./field-AJb7LB-s.js";
import { I as InputGroup, a as InputGroupInput, b as InputGroupAddon, c as InputGroupButton } from "./input-group-C5LPhh_3.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable, P as Paginasi } from "./DataTable-Cclynbfl.js";
import { L as LencanaStatusAkun } from "./LencanaStatusAkun-DFNpaity.js";
import { P as PenyaringDokumen, s as saringDepartemen } from "./PenyaringDokumen-ZbRxHXvI.js";
import { S as StripAksi } from "./StripAksi-BFgTXRBT.js";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./label-emiz6fAI.js";
import "./input-B9Vuz8J-.js";
import "./table-COSAIfdh.js";
import "./select-Db_F_VlA.js";
function UsersIndex() {
  const { users, departments, filters, akunMd, roleLabels, auth } = usePage().props;
  const kolom = [
    {
      judul: "Nama",
      render: (u) => /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("div", { className: "font-medium", children: u.name }),
        /* @__PURE__ */ jsxs("div", { className: "text-muted-foreground flex items-center gap-1 text-xs", children: [
          /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: IdentityCardIcon,
              strokeWidth: 1.5,
              className: "size-3.5",
              "aria-hidden": "true"
            }
          ),
          u.nrp ?? "—"
        ] })
      ] })
    },
    { judul: "NRP", render: (u) => /* @__PURE__ */ jsx("span", { className: "font-mono text-sm", children: u.nrp ?? "—" }) },
    { judul: "No. HP", render: (u) => /* @__PURE__ */ jsx("span", { className: "text-sm", children: u.nomor_hp ?? "—" }) },
    { judul: "Departemen", render: (u) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: u.dept ?? "—" }) },
    {
      judul: "Peran",
      render: (u) => /* @__PURE__ */ jsx(Badge, { variant: "secondary", children: (u.role && roleLabels[u.role]) ?? u.role ?? "—" })
    },
    { judul: "Status", render: (u) => /* @__PURE__ */ jsx(LencanaStatusAkun, { status: u.status }) },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (u) => /* @__PURE__ */ jsx(AksiBaris, { user: u, milikSendiri: u.id === auth.user?.id })
    }
  ];
  return /* @__PURE__ */ jsxs(
    AppLayout,
    {
      judul: "Manajemen User",
      sub: `${users.total} akun terdaftar.`,
      aksi: /* @__PURE__ */ jsx(Button, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("users.create"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: UserAdd01Icon, strokeWidth: 1.5, className: "size-4" }),
        "Buat Akun"
      ] }) }),
      children: [
        /* @__PURE__ */ jsx(KartuAkunMd, { akunMd }),
        /* @__PURE__ */ jsxs(Card, { children: [
          /* @__PURE__ */ jsxs(CardHeader, { className: "border-b", children: [
            /* @__PURE__ */ jsx(CardTitle, { children: "Daftar Akun" }),
            /* @__PURE__ */ jsx(CardDescription, { children: "Kelola semua akun dan buat akun staf." }),
            /* @__PURE__ */ jsx(
              PenyaringDokumen,
              {
                url: route("users.index"),
                filters,
                pilihan: [saringDepartemen(departments)],
                labelCari: "Cari (nama / NRP / email)",
                placeholderCari: "mis. nama, NRP, atau email…"
              }
            )
          ] }),
          /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
            DataTable,
            {
              kolom,
              baris: users.data,
              kunci: (u) => u.id,
              kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
                /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: UserSettings01Icon,
                    strokeWidth: 1.5,
                    className: "size-6"
                  }
                ) }),
                /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada user ditemukan." }),
                /* @__PURE__ */ jsx(EmptyDescription, { children: "Coba longgarkan penyaring di atas." })
              ] }) })
            }
          ) }),
          /* @__PURE__ */ jsx(CardFooter, { className: "border-t pt-6", children: /* @__PURE__ */ jsx(Paginasi, { paginator: users }) })
        ] })
      ]
    }
  );
}
function AksiBaris({ user, milikSendiri }) {
  const [jendela, setJendela] = useState(null);
  if (milikSendiri) {
    return /* @__PURE__ */ jsx(Badge, { variant: "outline", children: "Akun Anda" });
  }
  return /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
    /* @__PURE__ */ jsxs(StripAksi, { children: [
      /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("users.edit", user.id), children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: UserSettings01Icon,
            strokeWidth: 1.5,
            className: "size-4"
          }
        ),
        "Ubah Peran"
      ] }) }),
      /* @__PURE__ */ jsxs(DropdownMenuItem, { onSelect: () => setJendela("status"), children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: user.aktif ? CircleSlashIcon : CheckmarkCircle01Icon,
            strokeWidth: 1.5,
            className: "size-4"
          }
        ),
        user.aktif ? "Nonaktifkan" : "Aktifkan"
      ] }),
      /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => setJendela("hapus"), children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5, className: "size-4" }),
        "Hapus"
      ] })
    ] }),
    /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: "Ubah Status Akun?",
        pesan: `Ubah status akun ${user.name}?`,
        tombolYa: "Ya, ubah",
        destruktif: user.aktif,
        buka: jendela === "status",
        onUbahBuka: (b) => setJendela(b ? "status" : null),
        onKonfirmasi: () => router.post(route("users.toggleStatus", user.id), {}, { preserveScroll: true })
      }
    ),
    /* @__PURE__ */ jsx(
      ConfirmDialog,
      {
        judul: "Hapus Akun?",
        pesan: `Hapus akun ${user.name} (${user.nrp ?? "—"})? Akun yang masih tertaut dokumen akan ditolak — pakai Nonaktifkan untuk itu.`,
        tombolYa: "Ya, hapus",
        destruktif: true,
        buka: jendela === "hapus",
        onUbahBuka: (b) => setJendela(b ? "hapus" : null),
        onKonfirmasi: () => router.delete(route("users.destroy", user.id), { preserveScroll: true })
      }
    )
  ] });
}
function KartuAkunMd({ akunMd }) {
  const kolom = [
    { judul: "NRP", render: (m) => /* @__PURE__ */ jsx("span", { className: "font-mono text-sm", children: m.nrp ?? "—" }) },
    { judul: "Nama", render: (m) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: m.name }) },
    { judul: "Dept", render: (m) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: m.dept ?? "—" }) },
    { judul: "Status", render: (m) => /* @__PURE__ */ jsx(LencanaStatusAkun, { status: m.status }) },
    {
      judul: "Bantuan AI",
      render: (m) => /* @__PURE__ */ jsxs(Badge, { variant: m.ai ? "secondary" : "outline", children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: AiBrain01Icon, strokeWidth: 2, className: "size-3" }),
        m.ai ? "Aktif" : "Mati"
      ] })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (m) => /* @__PURE__ */ jsx(DialogKonfigurasiMd, { md: m })
    }
  ];
  return /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "border-b", children: [
      /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: SpellCheckIcon,
            strokeWidth: 1.5,
            className: "text-primary size-4",
            "aria-hidden": "true"
          }
        ),
        "Akun Management Development"
      ] }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Peninjau kedua — memeriksa sistematika penulisan sesudah SH/DH dan sebelum PJO. Akun bersama; hanya Admin yang boleh membuat dan mengelolanya." })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
      DataTable,
      {
        kolom,
        baris: akunMd,
        kunci: (m) => m.id,
        kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
          /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: Alert02Icon,
              strokeWidth: 1.5,
              className: "size-6"
            }
          ) }),
          /* @__PURE__ */ jsx(EmptyTitle, { children: "Belum ada akun Management Development." }),
          /* @__PURE__ */ jsx(EmptyDescription, { children: "Tanpa akun ini, dokumen SOP akan tertahan dan tak bisa sampai ke PJO." })
        ] }) })
      }
    ) })
  ] });
}
function DialogKonfigurasiMd({ md }) {
  const [buka, setBuka] = useState(false);
  const sandi = useForm({ aksi: "reset_sandi", sandi_baru: "" });
  function kirimAksi(aksi) {
    router.post(
      route("users.mdConfig", md.id),
      { aksi },
      { preserveScroll: true, onSuccess: () => setBuka(false) }
    );
  }
  function gantiSandi(e) {
    e.preventDefault();
    sandi.post(route("users.mdConfig", md.id), {
      preserveScroll: true,
      onSuccess: () => {
        sandi.reset("sandi_baru");
        setBuka(false);
      }
    });
  }
  return /* @__PURE__ */ jsxs(Dialog, { open: buka, onOpenChange: setBuka, children: [
    /* @__PURE__ */ jsx(DialogTrigger, { asChild: true, children: /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Settings01Icon, strokeWidth: 1.5, className: "size-4" }),
      "Konfigurasi"
    ] }) }),
    /* @__PURE__ */ jsxs(DialogContent, { children: [
      /* @__PURE__ */ jsxs(DialogHeader, { children: [
        /* @__PURE__ */ jsxs(DialogTitle, { children: [
          "Konfigurasi — ",
          md.nrp
        ] }),
        /* @__PURE__ */ jsx(DialogDescription, { children: md.name })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-4", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex items-start justify-between gap-4", children: [
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5 text-sm font-medium", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: AiBrain01Icon,
                  strokeWidth: 1.5,
                  className: "size-4",
                  "aria-hidden": "true"
                }
              ),
              "Bantuan AI"
            ] }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "Membantu menemukan typo & salah tulis. Keputusan tetap di tangan peninjau." })
          ] }),
          /* @__PURE__ */ jsx(Button, { variant: "outline", size: "sm", onClick: () => kirimAksi("ai"), children: md.ai ? "Matikan" : "Aktifkan" })
        ] }),
        /* @__PURE__ */ jsx(Separator, {}),
        /* @__PURE__ */ jsxs("div", { className: "flex items-start justify-between gap-4", children: [
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5 text-sm font-medium", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: ToggleOnIcon,
                  strokeWidth: 1.5,
                  className: "size-4",
                  "aria-hidden": "true"
                }
              ),
              "Status akun"
            ] }),
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: "Menonaktifkan akun menghentikan seluruh SOP di tahap ini." })
          ] }),
          /* @__PURE__ */ jsx(
            ConfirmDialog,
            {
              judul: md.aktif ? "Nonaktifkan akun MD?" : "Aktifkan akun MD?",
              pesan: md.aktif ? "Dokumen SOP akan TERTAHAN di tahap MD sampai diaktifkan lagi." : "Akun MD kembali menerima dokumen untuk ditinjau.",
              tombolYa: md.aktif ? "Ya, nonaktifkan" : "Ya, aktifkan",
              destruktif: md.aktif,
              onKonfirmasi: () => kirimAksi("status"),
              pemicu: /* @__PURE__ */ jsx(Button, { variant: "outline", size: "sm", children: md.aktif ? "Nonaktifkan" : "Aktifkan" })
            }
          )
        ] }),
        /* @__PURE__ */ jsx(Separator, {}),
        /* @__PURE__ */ jsx("form", { onSubmit: gantiSandi, children: /* @__PURE__ */ jsxs(Field, { "data-invalid": !!sandi.errors.sandi_baru || void 0, children: [
          /* @__PURE__ */ jsxs(
            FieldLabel,
            {
              htmlFor: `sandi-${md.id}`,
              className: "flex items-center gap-1.5",
              children: [
                /* @__PURE__ */ jsx(
                  HugeiconsIcon,
                  {
                    icon: ShieldKeyIcon,
                    strokeWidth: 1.5,
                    className: "size-4",
                    "aria-hidden": "true"
                  }
                ),
                "Ganti kata sandi"
              ]
            }
          ),
          /* @__PURE__ */ jsxs(InputGroup, { children: [
            /* @__PURE__ */ jsx(
              InputGroupInput,
              {
                id: `sandi-${md.id}`,
                type: "password",
                required: true,
                minLength: 8,
                placeholder: "Kata sandi baru (min. 8 karakter)",
                value: sandi.data.sandi_baru,
                "aria-invalid": !!sandi.errors.sandi_baru,
                onChange: (e) => sandi.setData("sandi_baru", e.target.value)
              }
            ),
            /* @__PURE__ */ jsx(InputGroupAddon, { align: "inline-end", children: /* @__PURE__ */ jsx(InputGroupButton, { type: "submit", disabled: sandi.processing, children: "Simpan" }) })
          ] }),
          /* @__PURE__ */ jsx(FieldDescription, { children: "Akun ini dipakai bersama — ganti sandinya setiap ada orang keluar atau pindah tugas." }),
          /* @__PURE__ */ jsx(
            FieldError,
            {
              errors: sandi.errors.sandi_baru ? [{ message: sandi.errors.sandi_baru }] : void 0
            }
          )
        ] }) })
      ] })
    ] })
  ] });
}
export {
  UsersIndex as default
};
