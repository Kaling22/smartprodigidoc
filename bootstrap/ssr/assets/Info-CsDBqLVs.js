import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { ListViewIcon, FloppyDiskIcon, ArrowLeft01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, Link } from "@inertiajs/react";
import { useState } from "react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, a as CardContent, b as CardHeader, c as CardTitle } from "./card-B3VJCD2M.js";
import { C as Checkbox } from "./checkbox-DZTcz7W1.js";
import { F as Field, a as FieldLabel, b as FieldDescription, c as FieldError, d as FieldGroup } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { A as AppLayout, a as Avatar, S as Separator } from "./AppLayout-C74XVGrz.js";
import { S as Spinner } from "./spinner-dcS4h88c.js";
import { L as LencanaStatusAkun } from "./LencanaStatusAkun-DFNpaity.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./label-emiz6fAI.js";
import "sonner";
function AccountInfo({ user }) {
  const { data, setData, put, processing, errors } = useForm({
    nomor_hp: user.nomor_hp ?? "",
    email: user.email ?? "",
    photo: null,
    remove_photo: false
  });
  const [pratinjau, setPratinjau] = useState(user.photo_url);
  function pilihFoto(berkas) {
    setData((sebelum) => ({ ...sebelum, photo: berkas, remove_photo: false }));
    setPratinjau(berkas ? URL.createObjectURL(berkas) : user.photo_url);
  }
  function kirim(e) {
    e.preventDefault();
    put(route("account.update"), { forceFormData: true });
  }
  return /* @__PURE__ */ jsx(AppLayout, { judul: "Informasi Akun", children: /* @__PURE__ */ jsxs("form", { onSubmit: kirim, className: "grid gap-4 md:gap-6 lg:grid-cols-3", children: [
    /* @__PURE__ */ jsx(Card, { className: "lg:col-span-1", children: /* @__PURE__ */ jsxs(CardContent, { className: "flex flex-col items-center gap-3 text-center", children: [
      /* @__PURE__ */ jsx(
        Avatar,
        {
          nama: user.name,
          foto: data.remove_photo ? null : pratinjau,
          className: "size-28"
        }
      ),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("h2", { className: "font-semibold", children: user.name }),
        /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm", children: user.peran })
      ] }),
      /* @__PURE__ */ jsx(LencanaStatusAkun, { status: user.status }),
      /* @__PURE__ */ jsxs(Field, { className: "text-left", "data-invalid": !!errors.photo || void 0, children: [
        /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "photo", children: "Ganti Foto Profil" }),
        /* @__PURE__ */ jsx(
          Input,
          {
            id: "photo",
            type: "file",
            accept: "image/jpeg,image/png",
            "aria-invalid": !!errors.photo,
            onChange: (e) => pilihFoto(e.target.files?.[0] ?? null)
          }
        ),
        /* @__PURE__ */ jsx(FieldDescription, { children: "JPG/PNG, maks 2MB." }),
        /* @__PURE__ */ jsx(
          FieldError,
          {
            errors: errors.photo ? [{ message: errors.photo }] : void 0
          }
        ),
        user.punya_foto ? /* @__PURE__ */ jsxs(Field, { orientation: "horizontal", className: "gap-2", children: [
          /* @__PURE__ */ jsx(
            Checkbox,
            {
              id: "remove_photo",
              checked: data.remove_photo,
              onCheckedChange: (v) => setData("remove_photo", v === true)
            }
          ),
          /* @__PURE__ */ jsx(
            FieldLabel,
            {
              htmlFor: "remove_photo",
              className: "text-destructive font-normal",
              children: "Hapus foto profil"
            }
          )
        ] }) : null
      ] })
    ] }) }),
    /* @__PURE__ */ jsxs(Card, { className: "lg:col-span-2", children: [
      /* @__PURE__ */ jsx(CardHeader, { children: /* @__PURE__ */ jsxs(CardTitle, { className: "flex items-center gap-2", children: [
        /* @__PURE__ */ jsx(
          HugeiconsIcon,
          {
            icon: ListViewIcon,
            strokeWidth: 1.5,
            className: "size-4",
            "aria-hidden": "true"
          }
        ),
        "Detail Akun"
      ] }) }),
      /* @__PURE__ */ jsxs(CardContent, { children: [
        /* @__PURE__ */ jsxs("dl", { className: "grid gap-2 text-sm sm:grid-cols-[10rem_1fr]", children: [
          /* @__PURE__ */ jsx(
            Baris,
            {
              label: "NRP",
              nilai: /* @__PURE__ */ jsx("span", { className: "font-mono", children: user.nrp ?? "—" })
            }
          ),
          /* @__PURE__ */ jsx(Baris, { label: "Nama Lengkap", nilai: user.name }),
          /* @__PURE__ */ jsx(Baris, { label: "Jabatan", nilai: user.jabatan_label ?? "—" }),
          /* @__PURE__ */ jsx(
            Baris,
            {
              label: "Departemen",
              nilai: user.departemen ?? "— (lintas departemen)"
            }
          ),
          /* @__PURE__ */ jsx(
            Baris,
            {
              label: "Peran Sistem",
              nilai: /* @__PURE__ */ jsx("span", { className: "capitalize", children: user.peran })
            }
          )
        ] }),
        /* @__PURE__ */ jsx(Separator, { className: "my-6" }),
        /* @__PURE__ */ jsxs(FieldGroup, { children: [
          /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.nomor_hp || void 0, children: [
            /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "nomor_hp", children: "Nomor HP" }),
            /* @__PURE__ */ jsx(
              Input,
              {
                id: "nomor_hp",
                placeholder: "mis. 0812xxxxxxx",
                value: data.nomor_hp,
                "aria-invalid": !!errors.nomor_hp,
                onChange: (e) => setData("nomor_hp", e.target.value)
              }
            ),
            /* @__PURE__ */ jsx(
              FieldError,
              {
                errors: errors.nomor_hp ? [{ message: errors.nomor_hp }] : void 0
              }
            )
          ] }),
          /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.email || void 0, children: [
            /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "email", children: "Email" }),
            /* @__PURE__ */ jsx(
              Input,
              {
                id: "email",
                type: "email",
                placeholder: "nama@perusahaan.com",
                value: data.email,
                "aria-invalid": !!errors.email,
                onChange: (e) => setData("email", e.target.value)
              }
            ),
            /* @__PURE__ */ jsx(
              FieldError,
              {
                errors: errors.email ? [{ message: errors.email }] : void 0
              }
            )
          ] })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "mt-6 flex gap-2", children: [
          /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
            processing ? /* @__PURE__ */ jsx(Spinner, {}) : /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: FloppyDiskIcon,
                strokeWidth: 1.5,
                className: "size-4"
              }
            ),
            "Simpan Perubahan"
          ] }),
          /* @__PURE__ */ jsx(Button, { asChild: true, variant: "ghost", children: /* @__PURE__ */ jsxs(Link, { href: route("dashboard"), children: [
            /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: ArrowLeft01Icon,
                strokeWidth: 1.5,
                className: "size-4"
              }
            ),
            "Batal"
          ] }) })
        ] })
      ] })
    ] })
  ] }) });
}
function Baris({ label, nilai }) {
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx("dt", { className: "text-muted-foreground", children: label }),
    /* @__PURE__ */ jsx("dd", { children: nilai })
  ] });
}
export {
  AccountInfo as default
};
