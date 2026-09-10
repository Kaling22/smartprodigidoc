import { jsx, jsxs } from "react/jsx-runtime";
import { ViewOffSlashIcon, ViewIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, Link } from "@inertiajs/react";
import { useState } from "react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Checkbox } from "./checkbox-DZTcz7W1.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError, b as FieldDescription } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { I as InputGroup, a as InputGroupInput, b as InputGroupAddon, c as InputGroupButton } from "./input-group-C5LPhh_3.js";
import { S as Spinner } from "./spinner-dcS4h88c.js";
import { A as AuthLayout } from "./AuthLayout-fjbuoxut.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "./label-emiz6fAI.js";
function Login() {
  const { data, setData, post, processing, errors } = useForm({
    nrp: "",
    password: "",
    remember: false
  });
  const [sandiTampil, setSandiTampil] = useState(false);
  function kirim(e) {
    e.preventDefault();
    post(route("login.store"));
  }
  return /* @__PURE__ */ jsx(AuthLayout, { judul: "Masuk", children: /* @__PURE__ */ jsx("form", { onSubmit: kirim, className: "flex flex-col gap-6", children: /* @__PURE__ */ jsxs(FieldGroup, { children: [
    /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-1", children: [
      /* @__PURE__ */ jsx("h1", { className: "text-2xl font-bold", children: "Selamat datang" }),
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm text-balance", children: "Masukkan NRP dan kata sandi untuk masuk." })
    ] }),
    /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.nrp || void 0, children: [
      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "nrp", children: "NRP" }),
      /* @__PURE__ */ jsx(
        Input,
        {
          id: "nrp",
          name: "nrp",
          value: data.nrp,
          onChange: (e) => setData("nrp", e.target.value),
          placeholder: "Nomor Registrasi Pegawai",
          autoComplete: "username",
          required: true,
          autoFocus: true,
          "aria-invalid": !!errors.nrp,
          className: "bg-background"
        }
      ),
      /* @__PURE__ */ jsx(FieldError, { errors: errors.nrp ? [{ message: errors.nrp }] : void 0 })
    ] }),
    /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.password || void 0, children: [
      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "password", children: "Kata Sandi" }),
      /* @__PURE__ */ jsxs(InputGroup, { className: "bg-background", children: [
        /* @__PURE__ */ jsx(
          InputGroupInput,
          {
            id: "password",
            name: "password",
            type: sandiTampil ? "text" : "password",
            value: data.password,
            onChange: (e) => setData("password", e.target.value),
            autoComplete: "current-password",
            required: true,
            "aria-invalid": !!errors.password
          }
        ),
        /* @__PURE__ */ jsx(InputGroupAddon, { align: "inline-end", children: /* @__PURE__ */ jsx(
          InputGroupButton,
          {
            size: "icon-xs",
            onClick: () => setSandiTampil((v) => !v),
            "aria-pressed": sandiTampil,
            "aria-label": sandiTampil ? "Sembunyikan kata sandi" : "Tampilkan kata sandi",
            children: /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: sandiTampil ? ViewOffSlashIcon : ViewIcon,
                strokeWidth: 1.5
              }
            )
          }
        ) })
      ] }),
      /* @__PURE__ */ jsx(FieldError, { errors: errors.password ? [{ message: errors.password }] : void 0 })
    ] }),
    /* @__PURE__ */ jsxs(Field, { orientation: "horizontal", children: [
      /* @__PURE__ */ jsx(
        Checkbox,
        {
          id: "remember",
          checked: data.remember,
          onCheckedChange: (v) => setData("remember", v === true)
        }
      ),
      /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "remember", className: "font-normal", children: "Ingat saya" })
    ] }),
    /* @__PURE__ */ jsxs(Field, { children: [
      /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
        processing ? /* @__PURE__ */ jsx(Spinner, {}) : null,
        "Masuk"
      ] }),
      /* @__PURE__ */ jsxs(FieldDescription, { className: "text-center", children: [
        "Belum punya akun?",
        " ",
        /* @__PURE__ */ jsx(Link, { href: route("register"), className: "underline underline-offset-4", children: "Daftar sebagai Non-Staff" })
      ] })
    ] })
  ] }) }) });
}
export {
  Login as default
};
