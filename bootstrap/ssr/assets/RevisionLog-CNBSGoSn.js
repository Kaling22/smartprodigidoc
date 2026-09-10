import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { Delete02Icon, PlusSignIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { B as Button } from "./button-DLS2B9Gu.js";
import { I as Input } from "./input-B9Vuz8J-.js";
import { L as Label } from "./label-emiz6fAI.js";
import { T as Textarea } from "./textarea-CFf6776c.js";
const barisLogBaru = () => ({ no_rev: null, tanggal: "", halaman: "", catatan: "", sub: [] });
const barisLogBerisi = (row) => String(row.catatan ?? "").trim() !== "" || (row.sub ?? []).some((s) => String(s.catatan ?? "").trim() !== "");
function RevisionLog({
  rows,
  onRows,
  edisi,
  noRevisi,
  onEdisi,
  onNoRevisi,
  judul,
  revisiKirim,
  pesan,
  tanggal,
  onTanggal,
  adaTombolRevisi = false
}) {
  const daftar = rows.length ? rows : [barisLogBaru()];
  const ubah = (i, patch) => onRows(daftar.map((r, n) => n === i ? { ...r, ...patch } : r));
  const hapus = (i) => {
    const sisa = daftar.filter((_, n) => n !== i);
    onRows(sisa.length ? sisa : [barisLogBaru()]);
  };
  const ubahSub = (i, j, catatan) => ubah(i, { sub: (daftar[i]?.sub ?? []).map((s, n) => n === j ? { ...s, catatan } : s) });
  const tambahSub = (i) => ubah(i, { sub: [...daftar[i]?.sub ?? [], { catatan: "" }] });
  const hapusSub = (i, j) => ubah(i, { sub: (daftar[i]?.sub ?? []).filter((_, n) => n !== j) });
  return /* @__PURE__ */ jsxs("div", { children: [
    /* @__PURE__ */ jsxs("div", { className: "mb-3 rounded-lg border bg-muted/40 p-3", children: [
      /* @__PURE__ */ jsx("p", { className: "mb-2 text-sm font-semibold", children: "Penomoran versi ini" }),
      /* @__PURE__ */ jsxs("div", { className: "mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm", children: [
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "Sekarang" }),
        /* @__PURE__ */ jsxs("span", { className: "font-medium", children: [
          "Edisi ",
          edisi,
          " · Revisi ",
          noRevisi
        ] }),
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", "aria-hidden": true, children: "→" }),
        /* @__PURE__ */ jsx("span", { className: "text-muted-foreground", children: "Setelah dikirim" }),
        /* @__PURE__ */ jsx("span", { className: "font-semibold text-primary", children: revisiKirim ? `Edisi ${revisiKirim.edisi} · Revisi ${revisiKirim.revisi}` : "—" })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-2 md:grid-cols-4", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm font-semibold", children: "Edisi" }),
          /* @__PURE__ */ jsx(Input, { type: "number", min: 1, name: "edisi", value: edisi, onChange: (e) => onEdisi(Number(e.target.value)) })
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm font-semibold", children: "Revisi" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              type: "number",
              min: 0,
              name: "no_revisi",
              value: noRevisi,
              onChange: (e) => onNoRevisi(Number(e.target.value))
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "md:col-span-2", children: [
          /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm font-semibold", children: "Judul Dokumen" }),
          /* @__PURE__ */ jsx(Input, { value: judul, readOnly: true, disabled: true })
        ] })
      ] }),
      /* @__PURE__ */ jsx("p", { className: "mt-1 text-xs text-muted-foreground", children: "Otomatis (roll-over) — boleh diubah manual." })
    ] }),
    tanggal ? /* @__PURE__ */ jsxs("div", { className: "mb-3 rounded-lg border bg-muted/40 p-3", children: [
      /* @__PURE__ */ jsx("p", { className: "mb-2 text-sm font-semibold", children: "Tanggal pada kop cetak" }),
      /* @__PURE__ */ jsxs("div", { className: "grid gap-2 md:grid-cols-2", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm font-semibold", children: "Tgl. Terbit / Efektif" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              type: "date",
              name: "tanggal_terbit",
              value: tanggal.terbit,
              onChange: (e) => onTanggal({ terbit: e.target.value })
            }
          ),
          /* @__PURE__ */ jsx("p", { className: "mt-1 text-xs text-muted-foreground", children: "Bawaannya Tanggal Efektif yang diisi saat dokumen lamanya diunggah — bukan hari pengetikan ulang." })
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm font-semibold", children: "Tgl. Revisi" }),
          /* @__PURE__ */ jsx(
            Input,
            {
              type: "date",
              name: "tanggal_revisi",
              value: tanggal.revisi,
              onChange: (e) => onTanggal({ revisi: e.target.value })
            }
          ),
          /* @__PURE__ */ jsx("p", { className: "mt-1 text-xs text-muted-foreground", children: "Bawaan hari ini — boleh diubah manual." })
        ] })
      ] })
    ] }) : null,
    /* @__PURE__ */ jsx(Label, { className: "mb-1.5 font-semibold", children: "Catatan Perubahan (Revisi)" }),
    /* @__PURE__ */ jsxs("p", { className: "mb-2 text-xs text-muted-foreground", children: [
      "Satu baris = satu catatan pada lembar CATATAN REVISI; baris revisi terdahulu ikut tercetak.",
      adaTombolRevisi ? /* @__PURE__ */ jsxs(Fragment, { children: [
        " ",
        "Tombol ",
        /* @__PURE__ */ jsx("strong", { children: "Revisi" }),
        " mengisi No. Rev, Tanggal, dan Hal.; catatannya Anda ketik sendiri."
      ] }) : null
    ] }),
    pesan ? /* @__PURE__ */ jsx("p", { className: "mb-2 rounded-lg border border-chart-3/40 bg-chart-3/10 px-3 py-2 text-sm", children: pesan }) : null,
    daftar.map((row, i) => (
      // eslint-disable-next-line react/no-array-index-key
      /* @__PURE__ */ jsxs("div", { className: "mb-2 rounded-lg border bg-muted/40 p-3", children: [
        /* @__PURE__ */ jsxs("div", { className: "grid items-start gap-2 md:grid-cols-12", children: [
          /* @__PURE__ */ jsxs("div", { className: "md:col-span-2", children: [
            /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm", children: "No. Rev" }),
            /* @__PURE__ */ jsx(
              Input,
              {
                type: "number",
                min: 0,
                name: `sections[catatan_revisi][${i}][no_rev]`,
                value: row.no_rev ?? "",
                placeholder: String(revisiKirim?.revisi ?? ""),
                "data-optional": true,
                onChange: (e) => ubah(i, { no_rev: e.target.value })
              }
            )
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "md:col-span-3", children: [
            /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm", children: "Tanggal Rev" }),
            /* @__PURE__ */ jsx(
              Input,
              {
                type: "date",
                name: `sections[catatan_revisi][${i}][tanggal]`,
                value: row.tanggal ?? "",
                "data-optional": true,
                onChange: (e) => ubah(i, { tanggal: e.target.value })
              }
            )
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "md:col-span-2", children: [
            /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm", children: "Hal." }),
            /* @__PURE__ */ jsx(
              Input,
              {
                name: `sections[catatan_revisi][${i}][halaman]`,
                value: row.halaman ?? "",
                placeholder: "mis. 1-4",
                "data-optional": true,
                onChange: (e) => ubah(i, { halaman: e.target.value })
              }
            )
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "md:col-span-5", children: [
            /* @__PURE__ */ jsx(Label, { className: "mb-1 text-sm", children: "Catatan Revisi" }),
            /* @__PURE__ */ jsx(
              Textarea,
              {
                rows: 2,
                name: `sections[catatan_revisi][${i}][catatan]`,
                value: row.catatan ?? "",
                placeholder: row.bab ? `Apa yang berubah pada ${row.bab}?` : "mis. Perubahan",
                "data-optional": true,
                onChange: (e) => ubah(i, { catatan: e.target.value })
              }
            )
          ] })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "mt-2 space-y-2 border-l-2 pl-3", children: [
          (row.sub ?? []).map((sub, j) => (
            // eslint-disable-next-line react/no-array-index-key
            /* @__PURE__ */ jsxs("div", { className: "flex items-start gap-2", children: [
              /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground mt-2 w-4 shrink-0 text-sm tabular-nums", children: [
                String.fromCharCode(97 + j % 26),
                "."
              ] }),
              /* @__PURE__ */ jsx(
                Textarea,
                {
                  rows: 2,
                  name: `sections[catatan_revisi][${i}][sub][${j}][catatan]`,
                  value: sub.catatan ?? "",
                  placeholder: sub.bab ? `Apa yang berubah pada ${sub.bab}?` : "mis. Pimpinan Departemen dari ... ke ...",
                  "data-optional": true,
                  onChange: (e) => ubahSub(i, j, e.target.value)
                }
              ),
              /* @__PURE__ */ jsx(
                Button,
                {
                  type: "button",
                  variant: "outline",
                  size: "sm",
                  "aria-label": `Hapus sub-poin ${String.fromCharCode(97 + j % 26)}`,
                  onClick: () => hapusSub(i, j),
                  children: /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5 })
                }
              )
            ] }, j)
          )),
          /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", size: "sm", onClick: () => tambahSub(i), children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 }),
            "Tambah Sub-Poin"
          ] })
        ] }),
        /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", size: "sm", className: "mt-2", onClick: () => hapus(i), children: [
          /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Delete02Icon, strokeWidth: 1.5 }),
          "Hapus Poin Catatan"
        ] })
      ] }, i)
    )),
    /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", size: "sm", className: "w-full", onClick: () => onRows([...daftar, barisLogBaru()]), children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: PlusSignIcon, strokeWidth: 1.5 }),
      "Tambah Sesi Revisi"
    ] })
  ] });
}
export {
  RevisionLog as R,
  barisLogBerisi as a,
  barisLogBaru as b
};
