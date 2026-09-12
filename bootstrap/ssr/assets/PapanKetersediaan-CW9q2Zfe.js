import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { Alert02Icon, HourglassIcon } from "@hugeicons/core-free-icons";
import { HugeiconsIcon } from "@hugeicons/react";
import { L as Label } from "./label-emiz6fAI.js";
import { c as cn } from "../uji-render.js";
import { createContext, useContext } from "react";
function xsrf() {
  const m = /(?:^|;\s*)XSRF-TOKEN=([^;]*)/.exec(document.cookie);
  return m ? decodeURIComponent(m[1]) : "";
}
async function unggahGambar(url, berkas, sectionKey) {
  const fd = new FormData();
  fd.append("image", berkas);
  fd.append("section", sectionKey);
  try {
    const r = await fetch(url, {
      method: "POST",
      headers: { "X-XSRF-TOKEN": xsrf(), "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
      body: fd
    });
    const j = await r.json();
    if (r.ok && j.path) return { path: j.path, galat: null };
    return { path: null, galat: j.message ?? "Gagal mengunggah gambar." };
  } catch {
    return { path: null, galat: "Gagal mengunggah gambar." };
  }
}
function kecilkan(berkas) {
  const MAKS = 1600;
  const sudahBenar = /^image\/(png|jpe?g)$/i.test(berkas.type);
  return new Promise((selesai) => {
    const url = URL.createObjectURL(berkas);
    const img = new Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      if (sudahBenar && img.width <= MAKS && berkas.size <= 15e5) {
        return selesai(berkas);
      }
      const skala = Math.min(1, MAKS / img.width);
      const c = document.createElement("canvas");
      c.width = Math.round(img.width * skala);
      c.height = Math.round(img.height * skala);
      const ctx = c.getContext("2d");
      if (!ctx) return selesai(berkas);
      ctx.fillStyle = "#fff";
      ctx.fillRect(0, 0, c.width, c.height);
      ctx.drawImage(img, 0, 0, c.width, c.height);
      c.toBlob((b) => selesai(b ? new File([b], "gambar.jpg", { type: "image/jpeg" }) : berkas), "image/jpeg", 0.9);
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      selesai(berkas);
    };
    img.src = url;
  });
}
const Konteks = createContext(null);
const PenyediaWizard = Konteks.Provider;
function useWizard() {
  const nilai = useContext(Konteks);
  if (!nilai) {
    throw new Error("Komponen field dipakai di luar PenyediaWizard.");
  }
  return nilai;
}
function Bantuan({ teks }) {
  if (!teks) return null;
  return /* @__PURE__ */ jsx("p", { className: "mb-2 text-xs text-muted-foreground", children: teks });
}
function PapanKetersediaan({ section, value, onChange }) {
  const { candidates, ketersediaan, papan, ambang } = useWizard();
  return /* @__PURE__ */ jsx(
    PapanPilihPeninjau,
    {
      section,
      value,
      onChange,
      nama: `sections[${section.key}]`,
      options: candidates[section.key] ?? [],
      jadwal: ketersediaan[section.key] ?? {},
      memblokir: papan[section.key] ?? true,
      ambang
    }
  );
}
function PapanPilihPeninjau({
  section,
  value,
  onChange,
  nama,
  options,
  jadwal,
  memblokir,
  ambang
}) {
  const required = section.required ?? false;
  const terpilih = value === null || value === void 0 || value === "" ? null : Number(value);
  const bisaDipilih = (o) => !memblokir || (jadwal[o.id]?.tersedia ?? true);
  const adaYangTersedia = options.some(bisaDipilih);
  const tercepat = options.map((o) => jadwal[o.id]).filter((a) => Boolean(a?.kembaliIso)).sort((a, b) => (a.kembaliIso ?? "").localeCompare(b.kembaliIso ?? ""))[0];
  const adaTingkat = memblokir && options.some((o) => (jadwal[o.id]?.tingkat ?? "normal") !== "normal");
  const pitaPertama = options.length ? jadwal[options[0].id]?.pita ?? [] : [];
  const pekanBaru = (i) => i > 0 && i % 7 === 0;
  const baris = cn(
    "grid items-center gap-2 md:gap-4",
    memblokir ? "grid-cols-1 md:grid-cols-[minmax(0,14rem)_1fr_auto]" : "grid-cols-1 md:grid-cols-[minmax(0,14rem)_1fr]"
  );
  const selLanjut = (i) => i >= 7 ? "hidden md:block" : "";
  return /* @__PURE__ */ jsxs("div", { className: "mb-6", children: [
    /* @__PURE__ */ jsxs(Label, { className: "mb-1.5 font-semibold", children: [
      section.label ?? section.key,
      required ? /* @__PURE__ */ jsx("span", { className: "text-destructive", children: " *" }) : null
    ] }),
    /* @__PURE__ */ jsx(Bantuan, { teks: section.hint }),
    options.length === 0 ? /* @__PURE__ */ jsxs("p", { className: "flex items-center gap-2 rounded-lg border border-chart-3/40 bg-chart-3/10 px-3 py-2 text-sm", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Alert02Icon, strokeWidth: 1.5, className: "size-4 shrink-0" }),
      "Belum ada kandidat untuk peran ini."
    ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsxs("div", { className: cn(baris, "hidden pb-1 pl-10 md:grid"), children: [
          /* @__PURE__ */ jsx("span", { className: "text-[0.7rem] text-muted-foreground", children: "Pilih satu baris" }),
          /* @__PURE__ */ jsx("span", { className: "flex gap-[3px] px-1", "aria-hidden": "true", children: pitaPertama.map((sel, i) => /* @__PURE__ */ jsx(
            "span",
            {
              className: cn(
                "min-w-0 flex-1 text-xs text-foreground/70",
                pekanBaru(i) && "ml-2",
                selLanjut(i)
              ),
              children: i === 0 || pekanBaru(i) ? /* @__PURE__ */ jsx("span", { className: cn("whitespace-nowrap", i === 0 && "font-semibold text-primary"), children: sel.tanggal }) : null
            },
            i
          )) }),
          memblokir ? /* @__PURE__ */ jsx("span", { className: "text-right text-[0.7rem] whitespace-nowrap text-muted-foreground", children: "Beban" }) : null
        ] }),
        options.map((o) => {
          const a = jadwal[o.id];
          const terkunci = !bisaDipilih(o);
          return /* @__PURE__ */ jsxs(
            "label",
            {
              className: cn("relative mb-2 block", terkunci ? "cursor-not-allowed" : "cursor-pointer"),
              children: [
                /* @__PURE__ */ jsx(
                  "input",
                  {
                    type: "radio",
                    className: "peer absolute top-5 left-3.5 z-10 size-4 accent-primary",
                    name: nama,
                    value: o.id,
                    checked: terpilih === o.id,
                    disabled: terkunci,
                    required,
                    onChange: () => onChange(o.id)
                  }
                ),
                /* @__PURE__ */ jsx(
                  "span",
                  {
                    className: cn(
                      "block rounded-xl border border-l-[3px] border-l-transparent py-2.5 pr-3.5 pl-10 transition-colors",
                      "peer-checked:border-l-primary peer-checked:bg-accent",
                      "peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ring",
                      terkunci ? "opacity-55" : "hover:bg-accent/50"
                    ),
                    children: /* @__PURE__ */ jsxs("span", { className: baris, children: [
                      /* @__PURE__ */ jsxs("span", { className: "min-w-0", children: [
                        /* @__PURE__ */ jsx("span", { className: "block truncate text-sm font-semibold", children: o.nama }),
                        /* @__PURE__ */ jsxs("span", { className: "block truncate text-xs text-muted-foreground", children: [
                          o.jabatan,
                          " · ",
                          o.dept,
                          " · ",
                          o.nrp
                        ] }),
                        /* @__PURE__ */ jsx("span", { className: "mt-1 block text-sm", children: /* @__PURE__ */ jsx(
                          "span",
                          {
                            className: cn(
                              terkunci || a?.off ? "font-semibold text-chart-3" : "text-muted-foreground"
                            ),
                            children: !memblokir && a?.penuh && !a.off ? "Tersedia hari ini" : a?.alasan ?? ""
                          }
                        ) })
                      ] }),
                      /* @__PURE__ */ jsx(Pita, { pita: a?.pita ?? [], nama: o.nama, pekanBaru, selLanjut }),
                      memblokir ? /* @__PURE__ */ jsx("span", { className: "order-first text-right whitespace-nowrap md:order-none", children: /* @__PURE__ */ jsxs(
                        "span",
                        {
                          className: cn(
                            "inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium",
                            WARNA_TINGKAT[a?.tingkat ?? "normal"]
                          ),
                          "data-tingkat": a?.tingkat ?? "normal",
                          role: "img",
                          title: `${a?.beban ?? 0} dokumen sedang ditinjau`,
                          "aria-label": `${a?.beban ?? 0} dokumen sedang ditinjau`,
                          children: [
                            a?.beban ?? 0,
                            /* @__PURE__ */ jsx("span", { className: "ml-1 font-normal opacity-75", children: "dok" })
                          ]
                        }
                      ) }) : null
                    ] })
                  }
                )
              ]
            },
            o.id
          );
        })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-2 flex flex-wrap gap-3 text-[0.7rem] text-muted-foreground", children: [
        /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-1.5", children: [
          /* @__PURE__ */ jsx("i", { className: "inline-block h-2 w-3.5 rounded-sm bg-primary" }),
          " cuti/off"
        ] }),
        /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-1.5", children: [
          /* @__PURE__ */ jsx("i", { className: "inline-block h-2 w-3.5 rounded-sm bg-card ring-1 ring-border" }),
          " tersedia"
        ] }),
        adaTingkat ? /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsxs("span", { children: [
            /* @__PURE__ */ jsx("span", { className: cn("rounded-md border px-1.5 py-0.5", WARNA_TINGKAT.padat), children: "padat" }),
            " ",
            ambang.padat,
            "–",
            ambang.sibuk - 1,
            " dokumen"
          ] }),
          /* @__PURE__ */ jsxs("span", { children: [
            /* @__PURE__ */ jsx("span", { className: cn("rounded-md border px-1.5 py-0.5", WARNA_TINGKAT.sibuk), children: "sibuk" }),
            " ",
            ambang.sibuk,
            " dokumen atau lebih"
          ] })
        ] }) : null,
        /* @__PURE__ */ jsx("span", { children: "Sumbu waktu mulai hari ini." })
      ] }),
      memblokir && !adaYangTersedia ? (
        // Buntu: bisa terjadi di departemen yang hanya punya satu SH.
        // Papan tetap ditampilkan di atas — GL berhak melihat SEBABNYA —
        // lalu diberi tahu apa yang bisa ia lakukan sekarang.
        /* @__PURE__ */ jsxs("div", { className: "mt-2 rounded-lg border border-chart-3/40 bg-chart-3/10 px-3 py-2 text-sm", children: [
          /* @__PURE__ */ jsxs("p", { className: "flex items-center gap-1.5 font-semibold", children: [
            /* @__PURE__ */ jsx(HugeiconsIcon, { icon: HourglassIcon, strokeWidth: 1.5, className: "size-4" }),
            "Belum ada kandidat yang bisa dipilih sekarang."
          ] }),
          "Simpan dokumen ini sebagai draft dulu.",
          " ",
          tercepat?.kembali ? /* @__PURE__ */ jsxs(Fragment, { children: [
            "Peninjau berikutnya bisa menerima dokumen lagi pada",
            " ",
            /* @__PURE__ */ jsx("strong", { children: tercepat.kembali }),
            "."
          ] }) : null
        ] })
      ) : null
    ] })
  ] });
}
const WARNA_TINGKAT = {
  normal: "border-transparent bg-secondary text-secondary-foreground",
  padat: "border-chart-3/40 bg-chart-3/10 text-chart-3",
  sibuk: "border-destructive/40 bg-destructive/10 text-destructive"
};
function Pita({
  pita,
  nama,
  pekanBaru,
  selLanjut
}) {
  return /* @__PURE__ */ jsx("span", { className: "flex gap-[3px] rounded-lg bg-muted/60 p-1", children: pita.map((sel, i) => /* @__PURE__ */ jsx(
    "span",
    {
      className: cn(
        "h-3.5 min-w-0 flex-1 rounded-sm",
        sel.status === "off" ? "bg-primary" : "bg-card ring-1 ring-border",
        pekanBaru(i) && "ml-2",
        selLanjut(i)
      ),
      title: `${nama} — ${sel.label}`
    },
    i
  )) });
}
export {
  Bantuan as B,
  PapanKetersediaan as P,
  unggahGambar as a,
  PenyediaWizard as b,
  PapanPilihPeninjau as c,
  kecilkan as k,
  useWizard as u,
  xsrf as x
};
