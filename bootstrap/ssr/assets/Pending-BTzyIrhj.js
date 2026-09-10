import { jsx, jsxs } from "react/jsx-runtime";
import { CheckmarkCircle02Icon, Tick02Icon, Cancel01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { router } from "@inertiajs/react";
import { A as AppLayout, B as Badge } from "./AppLayout-C74XVGrz.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, f as CardAction, a as CardContent } from "./card-B3VJCD2M.js";
import { E as Empty, a as EmptyHeader, b as EmptyMedia, c as EmptyTitle } from "./empty-CkAl4IHy.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { D as DataTable } from "./DataTable-Cclynbfl.js";
import "react";
import "sonner";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./table-COSAIfdh.js";
function UsersPending({ pendingUsers }) {
  const kolom = [
    { judul: "Nama", render: (u) => /* @__PURE__ */ jsx("span", { className: "font-medium", children: u.name }) },
    { judul: "NRP", render: (u) => /* @__PURE__ */ jsx("span", { className: "font-mono text-sm", children: u.nrp ?? "—" }) },
    {
      /* Jabatan yang DIKETIK pendaftar (mis. "Magang"); di bawahnya hak
         akses yang akan ia dapat, agar penyetuju tahu keduanya. Bila tak
         diisi, cukup labelnya — bukan kunci internal "staff". */
      judul: "Jabatan",
      render: (u) => u.jabatan_diajukan ? /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("div", { children: u.jabatan_diajukan }),
        /* @__PURE__ */ jsxs("div", { className: "text-muted-foreground text-xs", children: [
          "Akses: ",
          u.jabatan_label
        ] })
      ] }) : u.jabatan_label || "—"
    },
    { judul: "Departemen", render: (u) => /* @__PURE__ */ jsx(Badge, { variant: "outline", children: u.dept ?? "—" }) },
    {
      judul: "Email",
      render: (u) => /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-sm", children: u.email ?? "—" })
    },
    {
      judul: "Aksi",
      kelas: "w-px text-right whitespace-nowrap",
      render: (u) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-end gap-2", children: [
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            judul: "Setujui Akun?",
            pesan: `Setujui pendaftaran akun ${u.name}?`,
            tombolYa: "Ya, setujui",
            onKonfirmasi: () => router.post(route("users.approve", u.id), {}, { preserveScroll: true }),
            pemicu: /* @__PURE__ */ jsxs(Button, { size: "sm", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: Tick02Icon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              ),
              "Setujui"
            ] })
          }
        ),
        /* @__PURE__ */ jsx(
          ConfirmDialog,
          {
            judul: "Tolak Pendaftaran?",
            pesan: `Tolak pendaftaran ${u.name}?`,
            tombolYa: "Ya, tolak",
            destruktif: true,
            onKonfirmasi: () => router.post(route("users.reject", u.id), {}, { preserveScroll: true }),
            pemicu: /* @__PURE__ */ jsxs(Button, { variant: "outline", size: "sm", children: [
              /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: Cancel01Icon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              ),
              "Tolak"
            ] })
          }
        )
      ] })
    }
  ];
  return /* @__PURE__ */ jsx(AppLayout, { judul: "Persetujuan Akun", children: /* @__PURE__ */ jsxs(Card, { children: [
    /* @__PURE__ */ jsxs(CardHeader, { className: "border-b", children: [
      /* @__PURE__ */ jsx(CardTitle, { children: "Persetujuan Akun" }),
      /* @__PURE__ */ jsx(CardDescription, { children: "Setujui atau tolak pendaftaran User Departemen." }),
      /* @__PURE__ */ jsx(CardAction, { children: /* @__PURE__ */ jsxs(Badge, { variant: "secondary", children: [
        pendingUsers.length,
        " menunggu"
      ] }) })
    ] }),
    /* @__PURE__ */ jsx(CardContent, { className: "px-0", children: /* @__PURE__ */ jsx(
      DataTable,
      {
        kolom,
        baris: pendingUsers,
        kunci: (u) => u.id,
        kosong: /* @__PURE__ */ jsx(Empty, { className: "border-0", children: /* @__PURE__ */ jsxs(EmptyHeader, { children: [
          /* @__PURE__ */ jsx(EmptyMedia, { variant: "icon", children: /* @__PURE__ */ jsx(
            HugeiconsIcon,
            {
              icon: CheckmarkCircle02Icon,
              strokeWidth: 1.5,
              className: "size-6"
            }
          ) }),
          /* @__PURE__ */ jsx(EmptyTitle, { children: "Tidak ada akun yang menunggu persetujuan." })
        ] }) })
      }
    ) })
  ] }) });
}
export {
  UsersPending as default
};
