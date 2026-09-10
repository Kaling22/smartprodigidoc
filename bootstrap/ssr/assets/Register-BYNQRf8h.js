import { jsx, jsxs } from "react/jsx-runtime";
import { useForm, Link } from "@inertiajs/react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError, b as FieldDescription } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { S as Spinner } from "./spinner-dcS4h88c.js";
import { A as AuthLayout } from "./AuthLayout-fjbuoxut.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "react";
import "./label-emiz6fAI.js";
import "@hugeicons/react";
import "@hugeicons/core-free-icons";
function Register({ departments }) {
  const { data, setData, post, processing, errors } = useForm({
    name: "",
    nrp: "",
    nomor_hp: "",
    jabatan: "",
    department_id: "",
    password: "",
    password_confirmation: ""
  });
  function kirim(e) {
    e.preventDefault();
    post(route("register.store"));
  }
  return /* @__PURE__ */ jsx(AuthLayout, { judul: "Daftar", lebar: "max-w-md", children: /* @__PURE__ */ jsx("form", { onSubmit: kirim, className: "flex flex-col gap-6", children: /* @__PURE__ */ jsxs(FieldGroup, { children: [
    /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-1", children: [
      /* @__PURE__ */ jsx("h1", { className: "text-2xl font-bold", children: "Pendaftaran Akun Non-Staff" }),
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm text-balance", children: "Akun aktif setelah disetujui oleh Group Leader, Pimpinan, atau Admin IT." })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "grid gap-5 sm:grid-cols-2", children: [
      /* @__PURE__ */ jsx(
        Isian,
        {
          id: "name",
          label: "Nama Lengkap",
          nilai: data.name,
          galat: errors.name,
          onUbah: (v) => setData("name", v),
          wajib: true
        }
      ),
      /* @__PURE__ */ jsx(
        Isian,
        {
          id: "nrp",
          label: "NRP",
          nilai: data.nrp,
          galat: errors.nrp,
          onUbah: (v) => setData("nrp", v),
          petunjuk: "Nomor Registrasi Pegawai",
          autoComplete: "username",
          wajib: true
        }
      ),
      /* @__PURE__ */ jsx(
        Isian,
        {
          id: "nomor_hp",
          label: "Nomor HP",
          nilai: data.nomor_hp,
          galat: errors.nomor_hp,
          onUbah: (v) => setData("nomor_hp", v),
          petunjuk: "mis. 0812xxxxxxx"
        }
      ),
      /* @__PURE__ */ jsx(
        Isian,
        {
          id: "jabatan",
          label: "Jabatan",
          nilai: data.jabatan,
          galat: errors.jabatan,
          onUbah: (v) => setData("jabatan", v),
          petunjuk: "mis. Teknisi ICTMD, Magang, Helper"
        }
      ),
      /* @__PURE__ */ jsxs(
        Field,
        {
          className: "sm:col-span-2",
          "data-invalid": !!errors.department_id || void 0,
          children: [
            /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "department_id", children: "Departemen" }),
            /* @__PURE__ */ jsxs(
              Select,
              {
                value: data.department_id,
                onValueChange: (v) => setData("department_id", v),
                children: [
                  /* @__PURE__ */ jsx(
                    SelectTrigger,
                    {
                      id: "department_id",
                      className: "bg-background w-full",
                      "aria-invalid": !!errors.department_id,
                      children: /* @__PURE__ */ jsx(SelectValue, { placeholder: "— Pilih —" })
                    }
                  ),
                  /* @__PURE__ */ jsx(SelectContent, { children: departments.map((d) => /* @__PURE__ */ jsxs(SelectItem, { value: String(d.id), children: [
                    d.code,
                    " — ",
                    d.name
                  ] }, d.id)) })
                ]
              }
            ),
            /* @__PURE__ */ jsx(
              FieldError,
              {
                errors: errors.department_id ? [{ message: errors.department_id }] : void 0
              }
            )
          ]
        }
      ),
      /* @__PURE__ */ jsx(
        Isian,
        {
          id: "password",
          label: "Kata Sandi",
          type: "password",
          nilai: data.password,
          galat: errors.password,
          onUbah: (v) => setData("password", v),
          autoComplete: "new-password",
          wajib: true
        }
      ),
      /* @__PURE__ */ jsx(
        Isian,
        {
          id: "password_confirmation",
          label: "Ulangi Kata Sandi",
          type: "password",
          nilai: data.password_confirmation,
          galat: errors.password_confirmation,
          onUbah: (v) => setData("password_confirmation", v),
          autoComplete: "new-password",
          wajib: true
        }
      )
    ] }),
    /* @__PURE__ */ jsxs(Field, { children: [
      /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
        processing ? /* @__PURE__ */ jsx(Spinner, {}) : null,
        "Daftar"
      ] }),
      /* @__PURE__ */ jsxs(FieldDescription, { className: "text-center", children: [
        "Sudah punya akun?",
        " ",
        /* @__PURE__ */ jsx(Link, { href: route("login"), className: "underline underline-offset-4", children: "Masuk" })
      ] })
    ] })
  ] }) }) });
}
function Isian({
  id,
  label,
  nilai,
  galat,
  onUbah,
  type = "text",
  petunjuk,
  autoComplete,
  wajib = false
}) {
  return /* @__PURE__ */ jsxs(Field, { "data-invalid": !!galat || void 0, children: [
    /* @__PURE__ */ jsx(FieldLabel, { htmlFor: id, children: label }),
    /* @__PURE__ */ jsx(
      Input,
      {
        id,
        name: id,
        type,
        value: nilai,
        onChange: (e) => onUbah(e.target.value),
        placeholder: petunjuk,
        autoComplete,
        required: wajib,
        "aria-invalid": !!galat,
        className: "bg-background"
      }
    ),
    /* @__PURE__ */ jsx(FieldError, { errors: galat ? [{ message: galat }] : void 0 })
  ] });
}
export {
  Register as default
};
