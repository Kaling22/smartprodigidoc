import { jsx, jsxs } from "react/jsx-runtime";
import { InformationCircleIcon, Tick02Icon, ArrowLeft01Icon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { useForm, Link } from "@inertiajs/react";
import { A as Alert, a as AlertDescription } from "./alert-BvkCPa_V.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { C as Card, b as CardHeader, c as CardTitle, d as CardDescription, a as CardContent } from "./card-B3VJCD2M.js";
import { d as FieldGroup, F as Field, a as FieldLabel, c as FieldError, b as FieldDescription } from "./field-AJb7LB-s.js";
import { S as Select, a as SelectTrigger, b as SelectValue, c as SelectContent, d as SelectItem } from "./select-Db_F_VlA.js";
import { S as Spinner } from "./spinner-dcS4h88c.js";
import { C as ConfirmDialog } from "./ConfirmDialog-C9h_aHIF.js";
import { A as AppLayout } from "./AppLayout-C74XVGrz.js";
import "class-variance-authority";
import "../uji-render.js";
import "next-themes";
import "react-dom/server";
import "radix-ui";
import "clsx";
import "tailwind-merge";
import "react";
import "./label-emiz6fAI.js";
import "sonner";
function UsersEdit({ user, departments, roles, roleLabels, peranSekarang }) {
  const { data, setData, put, processing, errors } = useForm({
    role: peranSekarang ?? "",
    department_id: user.department_id ? String(user.department_id) : ""
  });
  return /* @__PURE__ */ jsx(
    AppLayout,
    {
      judul: "Ubah Peran",
      remah: [
        { label: "Manajemen User", href: route("users.index") },
        { label: "Ubah Peran" }
      ],
      children: /* @__PURE__ */ jsxs(Card, { children: [
        /* @__PURE__ */ jsxs(CardHeader, { children: [
          /* @__PURE__ */ jsx(CardTitle, { children: "Ubah Peran" }),
          /* @__PURE__ */ jsxs(CardDescription, { children: [
            user.name,
            " · NRP ",
            user.nrp ?? "—"
          ] })
        ] }),
        /* @__PURE__ */ jsxs(CardContent, { children: [
          /* @__PURE__ */ jsxs(Alert, { className: "mb-6", children: [
            /* @__PURE__ */ jsx(
              HugeiconsIcon,
              {
                icon: InformationCircleIcon,
                strokeWidth: 1.5,
                className: "size-4"
              }
            ),
            /* @__PURE__ */ jsxs(AlertDescription, { children: [
              "Peran sekarang:",
              " ",
              /* @__PURE__ */ jsx("span", { className: "font-medium", children: (peranSekarang && roleLabels[peranSekarang]) ?? peranSekarang ?? "—" }),
              ". Mengubah peran ikut menyesuaikan jabatan pada alur dokumen."
            ] })
          ] }),
          /* @__PURE__ */ jsxs(FieldGroup, { children: [
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
            /* @__PURE__ */ jsxs(Field, { "data-invalid": !!errors.department_id || void 0, children: [
              /* @__PURE__ */ jsx(FieldLabel, { htmlFor: "department_id", children: "Departemen" }),
              /* @__PURE__ */ jsxs(
                Select,
                {
                  value: data.department_id || "*",
                  onValueChange: (v) => setData("department_id", v === "*" ? "" : v),
                  children: [
                    /* @__PURE__ */ jsx(
                      SelectTrigger,
                      {
                        id: "department_id",
                        className: "w-full",
                        "aria-invalid": !!errors.department_id,
                        children: /* @__PURE__ */ jsx(SelectValue, {})
                      }
                    ),
                    /* @__PURE__ */ jsxs(SelectContent, { children: [
                      /* @__PURE__ */ jsx(SelectItem, { value: "*", children: "— Tanpa departemen (Pimpinan / Admin) —" }),
                      departments.map((d) => /* @__PURE__ */ jsxs(SelectItem, { value: String(d.id), children: [
                        d.code,
                        " — ",
                        d.name
                      ] }, d.id))
                    ] })
                  ]
                }
              ),
              /* @__PURE__ */ jsx(FieldDescription, { children: "Pimpinan (PJO) selalu disimpan tanpa departemen — ia melintasi ketujuhnya." }),
              /* @__PURE__ */ jsx(
                FieldError,
                {
                  errors: errors.department_id ? [{ message: errors.department_id }] : void 0
                }
              )
            ] })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "mt-6 flex gap-2", children: [
            /* @__PURE__ */ jsx(
              ConfirmDialog,
              {
                judul: "Ubah Peran?",
                pesan: `Ubah peran ${user.name}? Hak akses dan posisinya pada alur dokumen ikut berubah.`,
                tombolYa: "Ya, ubah",
                destruktif: true,
                onKonfirmasi: () => put(route("users.update", user.id)),
                pemicu: /* @__PURE__ */ jsxs(Button, { disabled: processing, children: [
                  processing ? /* @__PURE__ */ jsx(Spinner, {}) : /* @__PURE__ */ jsx(
                    HugeiconsIcon,
                    {
                      icon: Tick02Icon,
                      strokeWidth: 1.5,
                      className: "size-4"
                    }
                  ),
                  "Simpan Perubahan"
                ] })
              }
            ),
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
        ] })
      ] })
    }
  );
}
export {
  UsersEdit as default
};
