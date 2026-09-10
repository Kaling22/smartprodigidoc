import { jsx, jsxs } from "react/jsx-runtime";
import { Tick02Icon, ArrowLeft01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, Link } from "@inertiajs/react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent } from "./card-B3VJCD2M.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError, b as FieldDescription } from "./field-AJb7LB-s.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { S as Spinner } from "./spinner-dcS4h88c.js";
import { A as AppLayout } from "./AppLayout-C74XVGrz.js";
import "class-variance-authority";
import "radix-ui";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "clsx";
import "tailwind-merge";
import "react";
import "./label-emiz6fAI.js";
import "sonner";
function UsersCreate({ departments, roles, roleLabels }) {
  const { data, setData, post, processing, errors } = useForm({
    name: "",
    nrp: "",
    nomor_hp: "",
    department_id: "",
    role: "",
    email: "",
    password: "",
    password_confirmation: ""
  });
  function kirim(e) {
    e.preventDefault();
    post(route("users.store"));
  }
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Buat Akun",
      remah: [{ label: "Manajemen User", href: route("users.index") }, { label: "Buat Akun" }],
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsxs(CardHeader, { children: [
          /* @__PURE__ */ jsx(CardTitle, { children: "Buat Akun" }),
          /* @__PURE__ */ jsx(CardDescription, { children: "Akun yang dibuat admin langsung berstatus aktif." })
        ] }),
        /* @__PURE__ */ jsx(CardContent, { children: /* @__PURE__ */ jsxs("form", { onSubmit: kirim, children: [
          /* @__PURE__ */ jsxs(FieldGroup, { children: [
            /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:grid-cols-2", children: [
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
                  petunjuk: "Dipakai untuk login.",
                  nilai: data.nrp,
                  galat: errors.nrp,
                  onUbah: (v) => setData("nrp", v),
                  wajib: true
                }
              ),
              /* @__PURE__ */ jsx(
                Isian,
                {
                  id: "nomor_hp",
                  label: "Nomor HP",
                  petunjuk: "mis. 0812xxxxxxx",
                  nilai: data.nomor_hp,
                  galat: errors.nomor_hp,
                  onUbah: (v) => setData("nomor_hp", v)
                }
              ),
              /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.department_id || void 0, children: [
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
                          className: "w-full",
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
              ] })
            ] }),
            /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.role || void 0, children: [
              /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "role", children: "Jabatan / Peran" }),
              /* @__PURE__ */ jsxs(Select, { value: data.role, onValueChange: (v) => setData("role", v), children: [
                /* @__PURE__ */ jsx(
                  SelectTrigger,
                  {
                    id: "role",
                    className: "w-full",
                    "aria-invalid": !!errors.role,
                    children: /* @__PURE__ */ jsx(SelectValue, { placeholder: "— Pilih Jabatan —" })
                  }
                ),
                /* @__PURE__ */ jsx(SelectContent, { children: roles.map((r) => /* @__PURE__ */ jsx(SelectItem, { value: r, children: roleLabels[r] ?? r }, r)) })
              ] }),
              /* @__PURE__ */ jsx(
                FieldError,
                {
                  errors: errors.role ? [{ message: errors.role }] : void 0
                }
              )
            ] }),
            /* @__PURE__ */ jsx(
              Isian,
              {
                id: "email",
                label: "Email",
                type: "email",
                petunjuk: "Opsional.",
                nilai: data.email,
                galat: errors.email,
                onUbah: (v) => setData("email", v)
              }
            ),
            /* @__PURE__ */ jsxs("div", { className: "grid gap-4 md:grid-cols-2", children: [
              /* @__PURE__ */ jsx(
                Isian,
                {
                  id: "password",
                  label: "Kata Sandi",
                  type: "password",
                  nilai: data.password,
                  galat: errors.password,
                  onUbah: (v) => setData("password", v),
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
                  wajib: true
                }
              )
            ] })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "mt-6 flex gap-2", children: [
            /* @__PURE__ */ jsxs(Button, { type: "submit", disabled: processing, children: [
              processing ? /* @__PURE__ */ jsx(Spinner, {}) : /* @__PURE__ */ jsx(
                HugeiconsIcon,
                {
                  icon: Tick02Icon,
                  strokeWidth: 1.5,
                  className: "size-4"
                }
              ),
              "Simpan Akun"
            ] }),
            /* @__PURE__ */ jsx(Button, { asChild: true, variant: "ghost", children: /* @__PURE__ */ jsxs(Link, { href: route("users.index"), children: [
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
        ] }) })
      ] })
    }
  );
}
function Isian({
  id,
  label,
  nilai,
  galat,
  onUbah,
  type = "text",
  petunjuk,
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
        required: wajib,
        value: nilai,
        onChange: (e) => onUbah(e.target.value),
        "aria-invalid": !!galat
      }
    ),
    petunjuk ? /* @__PURE__ */ jsx(FieldDescription, { children: petunjuk }) : null,
    /* @__PURE__ */ jsx(FieldError, { errors: galat ? [{ message: galat }] : void 0 })
  ] });
}
export {
  UsersCreate as default
};
