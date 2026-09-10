import { jsx, jsxs } from "react/jsx-runtime";
import { createInertiaApp } from "@inertiajs/react";
import { ThemeProvider } from "next-themes";
import ReactDOMServer from "react-dom/server";
import { Tooltip as Tooltip$1 } from "radix-ui";
import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";
function cn(...inputs) {
  return twMerge(clsx(inputs));
}
function TooltipProvider({
  delayDuration = 0,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Tooltip$1.Provider,
    {
      "data-slot": "tooltip-provider",
      delayDuration,
      ...props
    }
  );
}
function Tooltip({
  ...props
}) {
  return /* @__PURE__ */ jsx(Tooltip$1.Root, { "data-slot": "tooltip", ...props });
}
function TooltipTrigger({
  ...props
}) {
  return /* @__PURE__ */ jsx(Tooltip$1.Trigger, { "data-slot": "tooltip-trigger", ...props });
}
function TooltipContent({
  className,
  sideOffset = 0,
  children,
  ...props
}) {
  return /* @__PURE__ */ jsx(Tooltip$1.Portal, { children: /* @__PURE__ */ jsxs(
    Tooltip$1.Content,
    {
      "data-slot": "tooltip-content",
      sideOffset,
      className: cn(
        "z-50 inline-flex w-fit max-w-xs origin-(--radix-tooltip-content-transform-origin) items-center gap-1.5 rounded-2xl bg-foreground px-3 py-1.5 text-xs text-background has-data-[slot=kbd]:pr-1.5 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 **:data-[slot=kbd]:relative **:data-[slot=kbd]:isolate **:data-[slot=kbd]:z-50 **:data-[slot=kbd]:rounded-4xl data-[state=delayed-open]:animate-in data-[state=delayed-open]:fade-in-0 data-[state=delayed-open]:zoom-in-95 data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95",
        className
      ),
      ...props,
      children: [
        children,
        /* @__PURE__ */ jsx(Tooltip$1.Arrow, { className: "z-50 size-2.5 translate-y-[calc(-50%_-_2px)] rotate-45 rounded-[2px] bg-foreground fill-foreground data-[side=left]:translate-x-[-1.5px] data-[side=right]:translate-x-[1.5px]" })
      ]
    }
  ) });
}
const halaman = /* @__PURE__ */ Object.assign({ "./pages/V2/Account/Info.tsx": () => import("./assets/Info-CsDBqLVs.js"), "./pages/V2/Akses/Index.tsx": () => import("./assets/Index-DmQusFXm.js"), "./pages/V2/Approvals/Index.tsx": () => import("./assets/Index-CVT_sqJy.js"), "./pages/V2/Approvals/Show.tsx": () => import("./assets/Show-B_fX-ghJ.js"), "./pages/V2/Audit/Index.tsx": () => import("./assets/Index-DqAjCWSf.js"), "./pages/V2/Auth/Login.tsx": () => import("./assets/Login-BVPIFb-J.js"), "./pages/V2/Auth/Pending.tsx": () => import("./assets/Pending-De03dn-l.js"), "./pages/V2/Auth/Register.tsx": () => import("./assets/Register-BYNQRf8h.js"), "./pages/V2/Dashboard.tsx": () => import("./assets/Dashboard-DNNppXl8.js"), "./pages/V2/Documents/Arsip/Catatan.tsx": () => import("./assets/Catatan-BselTZWX.js"), "./pages/V2/Documents/Arsip/Edit.tsx": () => import("./assets/Edit-bPf2CPA-.js"), "./pages/V2/Documents/Create.tsx": () => import("./assets/Create-tOpEO1hj.js"), "./pages/V2/Documents/Distribution.tsx": () => import("./assets/Distribution-CqVwDuYk.js"), "./pages/V2/Documents/Edit.tsx": () => import("./assets/Edit-DXNqm6q-.js"), "./pages/V2/Documents/Index.tsx": () => import("./assets/Index-Cugy3lsd.js"), "./pages/V2/Documents/Obsolete.tsx": () => import("./assets/Obsolete-DZNXLsYd.js"), "./pages/V2/Documents/Published.tsx": () => import("./assets/Published-vNAWCMu3.js"), "./pages/V2/Documents/Revisions.tsx": () => import("./assets/Revisions-QHTPMK9y.js"), "./pages/V2/Documents/RincianInformasi.tsx": () => import("./assets/RincianInformasi-DgjMXiCU.js"), "./pages/V2/Documents/Show.tsx": () => import("./assets/Show-DQmX-SJR.js"), "./pages/V2/Documents/StaffStatus.tsx": () => import("./assets/StaffStatus-ftEkmL8f.js"), "./pages/V2/Documents/Unavailable.tsx": () => import("./assets/Unavailable-RmwU2ZQ1.js"), "./pages/V2/Informasi/Create.tsx": () => import("./assets/Create-CwoB2rtg.js"), "./pages/V2/Informasi/Index.tsx": () => import("./assets/Index-DPfVKT2E.js"), "./pages/V2/Informasi/Nonaktif.tsx": () => import("./assets/Nonaktif-B7sWSqql.js"), "./pages/V2/Informasi/Perbarui.tsx": () => import("./assets/Perbarui-50XE-z0x.js"), "./pages/V2/JobExecutions/Index.tsx": () => import("./assets/Index-DU-xSz_v.js"), "./pages/V2/Log/Masukan.tsx": () => import("./assets/Masukan-N4h0qSy8.js"), "./pages/V2/Log/Pesan.tsx": () => import("./assets/Pesan-CxZ9mdv_.js"), "./pages/V2/MasukanSejawat/Show.tsx": () => import("./assets/Show-BG1gOTrG.js"), "./pages/V2/Nonaktif/Index.tsx": () => import("./assets/Index-ChiFAEXR.js"), "./pages/V2/Notifications/Index.tsx": () => import("./assets/Index-CRF_Aq3d.js"), "./pages/V2/Pengaturan/Master.tsx": () => import("./assets/Master-Dyse9ReQ.js"), "./pages/V2/Pengaturan/Penomoran.tsx": () => import("./assets/Penomoran-DThRYaQV.js"), "./pages/V2/Pengaturan/Sistem.tsx": () => import("./assets/Sistem-CE_KMXpi.js"), "./pages/V2/Review/Index.tsx": () => import("./assets/Index-XLdC95F2.js"), "./pages/V2/Review/Md.tsx": () => import("./assets/Md-CaIk_e-W.js"), "./pages/V2/Review/Show.tsx": () => import("./assets/Show-BO52ABwM.js"), "./pages/V2/Users/Create.tsx": () => import("./assets/Create-v_EPlm38.js"), "./pages/V2/Users/Edit.tsx": () => import("./assets/Edit-BX5LlsiW.js"), "./pages/V2/Users/Index.tsx": () => import("./assets/Index-bTZySU4P.js"), "./pages/V2/Users/Pending.tsx": () => import("./assets/Pending-BTzyIrhj.js") });
globalThis.route ??= (nama) => `/uji/${nama}`;
async function render(page) {
  const hasil = await createInertiaApp({
    page,
    render: ReactDOMServer.renderToString,
    resolve: (nama) => {
      const muat = halaman[`./pages/${nama}.tsx`];
      if (!muat) {
        throw new Error(`Halaman Inertia tidak ditemukan: resources/js/pages/${nama}.tsx`);
      }
      return muat().then((modul) => modul.default);
    },
    setup: ({ App, props }) => /* @__PURE__ */ jsx(ThemeProvider, { attribute: "class", defaultTheme: "light", enableSystem: false, children: /* @__PURE__ */ jsx(TooltipProvider, { children: /* @__PURE__ */ jsx(App, { ...props }) }) })
  });
  return hasil?.body ?? "";
}
export {
  Tooltip as T,
  TooltipTrigger as a,
  TooltipContent as b,
  cn as c,
  render
};
