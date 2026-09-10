import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { usePage, Link, router, Head } from "@inertiajs/react";
import * as React from "react";
import { useState, Fragment as Fragment$1, useEffect } from "react";
import { toast } from "sonner";
import { cva } from "class-variance-authority";
import { Separator as Separator$1, Avatar as Avatar$2, Slot, Dialog as Dialog$1, Collapsible as Collapsible$1, DropdownMenu as DropdownMenu$1 } from "radix-ui";
import { c as cn, T as Tooltip, a as TooltipTrigger, b as TooltipContent } from "../uji-render.js";
import { B as Button } from "./button-DLS2B9Gu.js";
import { HugeiconsIcon } from "@hugeicons/react";
import { UserIcon, Cancel01Icon, SidebarLeftIcon, OctagonXIcon, CancelCircleIcon, LayoutGridIcon, TruckIcon, Delete02Icon, ToggleOnIcon, SparklesIcon, SpellCheckIcon, DashboardSpeed01Icon, FilterHorizontalIcon, CircleSlashIcon, ShieldKeyIcon, ShieldCheckIcon, ShieldAlertIcon, Share01Icon, SentIcon, ReplyIcon, PieChartIcon, UserAdd01Icon, UserSquareIcon, UserCheck01Icon, IdentityCardIcon, UserMultipleIcon, PencilEdit01Icon, PencilIcon, HelpCircleIcon, CheckmarkBadge01Icon, Megaphone01Icon, LockIcon, CheckListIcon, NotebookTextIcon, BookMarkedIcon, InformationSquareIcon, InformationCircleIcon, InboxIcon, Image01Icon, HourglassIcon, ServerStack01Icon, HashIcon, GridViewIcon, Settings01Icon, TextFontIcon, FolderOpenIcon, FolderCheckIcon, Files01Icon, FileEditIcon, FileValidationIcon, FileAddIcon, Alert02Icon, MailOpen01Icon, Mail01Icon, DropletIcon, CircleIcon, HierarchySquare01Icon, HierarchyIcon, ClipboardListIcon, ClipboardCheckIcon, ClipboardIcon, CheckmarkCircle02Icon, Tick02Icon, CheckmarkCircle01Icon, Message01Icon, QuoteDownIcon, ListViewIcon, Calendar03Icon, CalendarRemove01Icon, CalendarAdd01Icon, FactoryIcon, Building02Icon, RadioIcon, LinkSquare01Icon, Logout01Icon, Login01Icon, Download01Icon, Notification01Icon, RefreshCwIcon, Exchange01Icon, ArrowTurnBackwardIcon, Archive01Icon, ArrowRight01Icon, Sun03Icon, Moon02Icon } from "@hugeicons/core-free-icons";
import { useTheme } from "next-themes";
function Separator({
  className,
  orientation = "horizontal",
  decorative = true,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Separator$1.Root,
    {
      "data-slot": "separator",
      decorative,
      orientation,
      className: cn(
        "shrink-0 bg-border data-horizontal:h-px data-horizontal:w-full data-vertical:w-px data-vertical:self-stretch",
        className
      ),
      ...props
    }
  );
}
function Avatar$1({
  className,
  size = "default",
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Avatar$2.Root,
    {
      "data-slot": "avatar",
      "data-size": size,
      className: cn(
        "group/avatar relative flex size-8 shrink-0 rounded-full select-none after:absolute after:inset-0 after:rounded-full after:border after:border-border after:mix-blend-darken data-[size=lg]:size-10 data-[size=sm]:size-6 dark:after:mix-blend-lighten",
        className
      ),
      ...props
    }
  );
}
function AvatarImage({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Avatar$2.Image,
    {
      "data-slot": "avatar-image",
      className: cn(
        "aspect-square size-full rounded-full object-cover",
        className
      ),
      ...props
    }
  );
}
function AvatarFallback({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Avatar$2.Fallback,
    {
      "data-slot": "avatar-fallback",
      className: cn(
        "flex size-full items-center justify-center rounded-full bg-muted text-sm text-muted-foreground group-data-[size=sm]/avatar:text-xs",
        className
      ),
      ...props
    }
  );
}
function Avatar({
  nama,
  foto,
  className
}) {
  return /* @__PURE__ */ jsxs(Avatar$1, { className: cn("size-9 rounded-full", className), children: [
    foto ? /* @__PURE__ */ jsx(AvatarImage, { src: foto, alt: nama }) : null,
    /* @__PURE__ */ jsxs(AvatarFallback, { className: "rounded-full", children: [
      /* @__PURE__ */ jsx(HugeiconsIcon, { icon: UserIcon, strokeWidth: 1.5, className: "size-4", "aria-hidden": "true" }),
      /* @__PURE__ */ jsx("span", { className: "sr-only", children: nama })
    ] })
  ] });
}
const badgeVariants = cva(
  "group/badge inline-flex h-5 w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-4xl border border-transparent px-2 py-0.5 text-xs font-medium whitespace-nowrap transition-all focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 has-data-[icon=inline-end]:pr-1.5 has-data-[icon=inline-start]:pl-1.5 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 [&>svg]:pointer-events-none [&>svg]:size-3!",
  {
    variants: {
      variant: {
        default: "bg-primary text-primary-foreground [a]:hover:bg-primary/80",
        secondary: "bg-secondary text-secondary-foreground [a]:hover:bg-secondary/80",
        destructive: "bg-destructive/10 text-destructive focus-visible:ring-destructive/20 dark:bg-destructive/20 dark:focus-visible:ring-destructive/40 [a]:hover:bg-destructive/20",
        outline: "border-border bg-input/30 text-foreground [a]:hover:bg-muted [a]:hover:text-muted-foreground",
        ghost: "hover:bg-muted hover:text-muted-foreground dark:hover:bg-muted/50",
        link: "text-primary underline-offset-4 hover:underline"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);
function Badge({
  className,
  variant = "default",
  asChild = false,
  ...props
}) {
  const Comp = asChild ? Slot.Root : "span";
  return /* @__PURE__ */ jsx(
    Comp,
    {
      "data-slot": "badge",
      "data-variant": variant,
      className: cn(badgeVariants({ variant }), className),
      ...props
    }
  );
}
const MOBILE_BREAKPOINT = 768;
function useIsMobile() {
  const [isMobile, setIsMobile] = React.useState(void 0);
  React.useEffect(() => {
    const mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);
    const onChange = () => {
      setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
    };
    mql.addEventListener("change", onChange);
    setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
    return () => mql.removeEventListener("change", onChange);
  }, []);
  return !!isMobile;
}
function Sheet({ ...props }) {
  return /* @__PURE__ */ jsx(Dialog$1.Root, { "data-slot": "sheet", ...props });
}
function SheetPortal({
  ...props
}) {
  return /* @__PURE__ */ jsx(Dialog$1.Portal, { "data-slot": "sheet-portal", ...props });
}
function SheetOverlay({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Dialog$1.Overlay,
    {
      "data-slot": "sheet-overlay",
      className: cn(
        "fixed inset-0 z-50 bg-black/80 duration-100 supports-backdrop-filter:backdrop-blur-xs data-open:animate-in data-open:fade-in-0 data-closed:animate-out data-closed:fade-out-0",
        className
      ),
      ...props
    }
  );
}
function SheetContent({
  className,
  children,
  side = "right",
  showCloseButton = true,
  ...props
}) {
  return /* @__PURE__ */ jsxs(SheetPortal, { children: [
    /* @__PURE__ */ jsx(SheetOverlay, {}),
    /* @__PURE__ */ jsxs(
      Dialog$1.Content,
      {
        "data-slot": "sheet-content",
        "data-side": side,
        className: cn(
          "fixed z-50 flex flex-col bg-popover bg-clip-padding text-sm text-popover-foreground shadow-lg transition duration-200 ease-in-out data-[side=bottom]:inset-x-0 data-[side=bottom]:bottom-0 data-[side=bottom]:h-auto data-[side=bottom]:border-t data-[side=left]:inset-y-0 data-[side=left]:left-0 data-[side=left]:h-full data-[side=left]:w-3/4 data-[side=left]:border-r data-[side=right]:inset-y-0 data-[side=right]:right-0 data-[side=right]:h-full data-[side=right]:w-3/4 data-[side=right]:border-l data-[side=top]:inset-x-0 data-[side=top]:top-0 data-[side=top]:h-auto data-[side=top]:border-b data-[side=left]:sm:max-w-sm data-[side=right]:sm:max-w-sm data-open:animate-in data-open:fade-in-0 data-[side=bottom]:data-open:slide-in-from-bottom-10 data-[side=left]:data-open:slide-in-from-left-10 data-[side=right]:data-open:slide-in-from-right-10 data-[side=top]:data-open:slide-in-from-top-10 data-closed:animate-out data-closed:fade-out-0 data-[side=bottom]:data-closed:slide-out-to-bottom-10 data-[side=left]:data-closed:slide-out-to-left-10 data-[side=right]:data-closed:slide-out-to-right-10 data-[side=top]:data-closed:slide-out-to-top-10",
          className
        ),
        ...props,
        children: [
          children,
          showCloseButton && /* @__PURE__ */ jsx(Dialog$1.Close, { "data-slot": "sheet-close", asChild: true, children: /* @__PURE__ */ jsxs(
            Button,
            {
              variant: "ghost",
              className: "absolute top-4 right-4",
              size: "icon-sm",
              children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 2 }),
                /* @__PURE__ */ jsx("span", { className: "sr-only", children: "Close" })
              ]
            }
          ) })
        ]
      }
    )
  ] });
}
function SheetHeader({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "sheet-header",
      className: cn("flex flex-col gap-1.5 p-6", className),
      ...props
    }
  );
}
function SheetTitle({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Dialog$1.Title,
    {
      "data-slot": "sheet-title",
      className: cn(
        "font-heading text-base font-medium text-foreground",
        className
      ),
      ...props
    }
  );
}
function SheetDescription({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Dialog$1.Description,
    {
      "data-slot": "sheet-description",
      className: cn("text-sm text-muted-foreground", className),
      ...props
    }
  );
}
const SIDEBAR_COOKIE_NAME = "sidebar_state";
const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 7;
const SIDEBAR_WIDTH = "16rem";
const SIDEBAR_WIDTH_MOBILE = "18rem";
const SIDEBAR_WIDTH_ICON = "3rem";
const SIDEBAR_KEYBOARD_SHORTCUT = "b";
const SidebarContext = React.createContext(null);
function useSidebar() {
  const context = React.useContext(SidebarContext);
  if (!context) {
    throw new Error("useSidebar must be used within a SidebarProvider.");
  }
  return context;
}
function SidebarProvider({
  defaultOpen = true,
  open: openProp,
  onOpenChange: setOpenProp,
  className,
  style,
  children,
  ...props
}) {
  const isMobile = useIsMobile();
  const [openMobile, setOpenMobile] = React.useState(false);
  const [_open, _setOpen] = React.useState(defaultOpen);
  const open = openProp ?? _open;
  const setOpen = React.useCallback(
    (value) => {
      const openState = typeof value === "function" ? value(open) : value;
      if (setOpenProp) {
        setOpenProp(openState);
      } else {
        _setOpen(openState);
      }
      document.cookie = `${SIDEBAR_COOKIE_NAME}=${openState}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`;
    },
    [setOpenProp, open]
  );
  const toggleSidebar = React.useCallback(() => {
    return isMobile ? setOpenMobile((open2) => !open2) : setOpen((open2) => !open2);
  }, [isMobile, setOpen, setOpenMobile]);
  React.useEffect(() => {
    const handleKeyDown = (event) => {
      if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        toggleSidebar();
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [toggleSidebar]);
  const state = open ? "expanded" : "collapsed";
  const contextValue = React.useMemo(
    () => ({
      state,
      open,
      setOpen,
      isMobile,
      openMobile,
      setOpenMobile,
      toggleSidebar
    }),
    [state, open, setOpen, isMobile, openMobile, setOpenMobile, toggleSidebar]
  );
  return /* @__PURE__ */ jsx(SidebarContext.Provider, { value: contextValue, children: /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "sidebar-wrapper",
      style: {
        "--sidebar-width": SIDEBAR_WIDTH,
        "--sidebar-width-icon": SIDEBAR_WIDTH_ICON,
        ...style
      },
      className: cn(
        "group/sidebar-wrapper flex min-h-svh w-full has-data-[variant=inset]:bg-sidebar",
        className
      ),
      ...props,
      children
    }
  ) });
}
function Sidebar({
  side = "left",
  variant = "sidebar",
  collapsible = "offcanvas",
  className,
  children,
  dir,
  ...props
}) {
  const { isMobile, state, openMobile, setOpenMobile } = useSidebar();
  if (collapsible === "none") {
    return /* @__PURE__ */ jsx(
      "div",
      {
        "data-slot": "sidebar",
        className: cn(
          "flex h-full w-(--sidebar-width) flex-col bg-sidebar text-sidebar-foreground",
          className
        ),
        ...props,
        children
      }
    );
  }
  if (isMobile) {
    return /* @__PURE__ */ jsx(Sheet, { open: openMobile, onOpenChange: setOpenMobile, ...props, children: /* @__PURE__ */ jsxs(
      SheetContent,
      {
        dir,
        "data-sidebar": "sidebar",
        "data-slot": "sidebar",
        "data-mobile": "true",
        className: "w-(--sidebar-width) bg-sidebar p-0 text-sidebar-foreground [&>button]:hidden",
        style: {
          "--sidebar-width": SIDEBAR_WIDTH_MOBILE
        },
        side,
        children: [
          /* @__PURE__ */ jsxs(SheetHeader, { className: "sr-only", children: [
            /* @__PURE__ */ jsx(SheetTitle, { children: "Sidebar" }),
            /* @__PURE__ */ jsx(SheetDescription, { children: "Displays the mobile sidebar." })
          ] }),
          /* @__PURE__ */ jsx("div", { className: "flex h-full w-full flex-col", children })
        ]
      }
    ) });
  }
  return /* @__PURE__ */ jsxs(
    "div",
    {
      className: "group peer hidden text-sidebar-foreground md:block",
      "data-state": state,
      "data-collapsible": state === "collapsed" ? collapsible : "",
      "data-variant": variant,
      "data-side": side,
      "data-slot": "sidebar",
      children: [
        /* @__PURE__ */ jsx(
          "div",
          {
            "data-slot": "sidebar-gap",
            className: cn(
              "relative w-(--sidebar-width) bg-transparent transition-[width] duration-200 ease-linear",
              "group-data-[collapsible=offcanvas]:w-0",
              "group-data-[side=right]:rotate-180",
              variant === "floating" || variant === "inset" ? "group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4)))]" : "group-data-[collapsible=icon]:w-(--sidebar-width-icon)"
            )
          }
        ),
        /* @__PURE__ */ jsx(
          "div",
          {
            "data-slot": "sidebar-container",
            "data-side": side,
            className: cn(
              "fixed inset-y-0 z-10 hidden h-svh w-(--sidebar-width) transition-[left,right,width] duration-200 ease-linear data-[side=left]:left-0 data-[side=left]:group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)] data-[side=right]:right-0 data-[side=right]:group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)] md:flex",
              // Adjust the padding for floating and inset variants.
              variant === "floating" || variant === "inset" ? "p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4))+2px)]" : "group-data-[collapsible=icon]:w-(--sidebar-width-icon) group-data-[side=left]:border-r group-data-[side=right]:border-l",
              className
            ),
            ...props,
            children: /* @__PURE__ */ jsx(
              "div",
              {
                "data-sidebar": "sidebar",
                "data-slot": "sidebar-inner",
                className: "flex size-full flex-col bg-sidebar group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:shadow-sm group-data-[variant=floating]:ring-1 group-data-[variant=floating]:ring-sidebar-border",
                children
              }
            )
          }
        )
      ]
    }
  );
}
function SidebarTrigger({
  className,
  onClick,
  ...props
}) {
  const { toggleSidebar } = useSidebar();
  return /* @__PURE__ */ jsxs(
    Button,
    {
      "data-sidebar": "trigger",
      "data-slot": "sidebar-trigger",
      variant: "ghost",
      size: "icon-sm",
      className: cn(className),
      onClick: (event) => {
        onClick?.(event);
        toggleSidebar();
      },
      ...props,
      children: [
        /* @__PURE__ */ jsx(HugeiconsIcon, { icon: SidebarLeftIcon, strokeWidth: 2 }),
        /* @__PURE__ */ jsx("span", { className: "sr-only", children: "Toggle Sidebar" })
      ]
    }
  );
}
function SidebarInset({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "main",
    {
      "data-slot": "sidebar-inset",
      className: cn(
        "relative flex w-full flex-1 flex-col bg-background md:peer-data-[variant=inset]:m-2 md:peer-data-[variant=inset]:ml-0 md:peer-data-[variant=inset]:rounded-xl md:peer-data-[variant=inset]:shadow-sm md:peer-data-[variant=inset]:peer-data-[state=collapsed]:ml-2",
        className
      ),
      ...props
    }
  );
}
function SidebarHeader({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "sidebar-header",
      "data-sidebar": "header",
      className: cn(
        "flex flex-col gap-2 p-2 [--radius:var(--radius-xl)]",
        className
      ),
      ...props
    }
  );
}
function SidebarFooter({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "sidebar-footer",
      "data-sidebar": "footer",
      className: cn("flex flex-col gap-2 p-2", className),
      ...props
    }
  );
}
function SidebarContent({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "sidebar-content",
      "data-sidebar": "content",
      className: cn(
        "no-scrollbar flex min-h-0 flex-1 flex-col gap-2 overflow-auto [--radius:var(--radius-xl)] group-data-[collapsible=icon]:overflow-hidden",
        className
      ),
      ...props
    }
  );
}
function SidebarGroup({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "sidebar-group",
      "data-sidebar": "group",
      className: cn("relative flex w-full min-w-0 flex-col p-2", className),
      ...props
    }
  );
}
function SidebarGroupLabel({
  className,
  asChild = false,
  ...props
}) {
  const Comp = asChild ? Slot.Root : "div";
  return /* @__PURE__ */ jsx(
    Comp,
    {
      "data-slot": "sidebar-group-label",
      "data-sidebar": "group-label",
      className: cn(
        "flex h-8 shrink-0 items-center rounded-md px-3 text-xs font-medium text-sidebar-foreground/70 ring-sidebar-ring outline-hidden transition-[margin,opacity] duration-200 ease-linear group-data-[collapsible=icon]:-mt-8 group-data-[collapsible=icon]:opacity-0 focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0",
        className
      ),
      ...props
    }
  );
}
function SidebarMenu({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "ul",
    {
      "data-slot": "sidebar-menu",
      "data-sidebar": "menu",
      className: cn("flex w-full min-w-0 flex-col gap-1", className),
      ...props
    }
  );
}
function SidebarMenuItem({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "li",
    {
      "data-slot": "sidebar-menu-item",
      "data-sidebar": "menu-item",
      className: cn("group/menu-item relative", className),
      ...props
    }
  );
}
const sidebarMenuButtonVariants = cva(
  "peer/menu-button group/menu-button flex w-full items-center gap-2 overflow-hidden rounded-lg px-3 py-2 text-left text-sm ring-sidebar-ring outline-hidden transition-[width,height,padding] group-has-data-[sidebar=menu-action]/menu-item:pr-8 group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-2! hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-open:hover:bg-sidebar-accent data-open:hover:text-sidebar-accent-foreground data-active:bg-sidebar-accent data-active:font-medium data-active:text-sidebar-accent-foreground [&_svg]:size-4 [&_svg]:shrink-0 [&>span:last-child]:truncate",
  {
    variants: {
      variant: {
        default: "hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
        outline: "bg-background shadow-[0_0_0_1px_var(--sidebar-border)] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground hover:shadow-[0_0_0_1px_var(--sidebar-accent)]"
      },
      size: {
        default: "h-9 text-sm",
        sm: "h-8 text-xs",
        lg: "h-14 px-3 text-sm group-data-[collapsible=icon]:p-0!"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);
function SidebarMenuButton({
  asChild = false,
  isActive = false,
  variant = "default",
  size = "default",
  tooltip,
  className,
  ...props
}) {
  const Comp = asChild ? Slot.Root : "button";
  const { isMobile, state } = useSidebar();
  const button = /* @__PURE__ */ jsx(
    Comp,
    {
      "data-slot": "sidebar-menu-button",
      "data-sidebar": "menu-button",
      "data-size": size,
      "data-active": isActive,
      className: cn(sidebarMenuButtonVariants({ variant, size }), className),
      ...props
    }
  );
  if (!tooltip) {
    return button;
  }
  if (typeof tooltip === "string") {
    tooltip = {
      children: tooltip
    };
  }
  return /* @__PURE__ */ jsxs(Tooltip, { children: [
    /* @__PURE__ */ jsx(TooltipTrigger, { asChild: true, children: button }),
    /* @__PURE__ */ jsx(
      TooltipContent,
      {
        side: "right",
        align: "center",
        hidden: state !== "collapsed" || isMobile,
        ...tooltip
      }
    )
  ] });
}
function SidebarMenuBadge({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "sidebar-menu-badge",
      "data-sidebar": "menu-badge",
      className: cn(
        "pointer-events-none absolute right-1 flex h-5 min-w-5 items-center justify-center rounded-md px-1 text-xs font-medium text-sidebar-foreground tabular-nums select-none group-data-[collapsible=icon]:hidden peer-hover/menu-button:text-sidebar-accent-foreground peer-data-[size=default]/menu-button:top-1.5 peer-data-[size=lg]/menu-button:top-2.5 peer-data-[size=sm]/menu-button:top-1 peer-data-active/menu-button:text-sidebar-accent-foreground",
        className
      ),
      ...props
    }
  );
}
function SidebarMenuSub({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "ul",
    {
      "data-slot": "sidebar-menu-sub",
      "data-sidebar": "menu-sub",
      className: cn(
        "mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l border-sidebar-border px-2.5 py-0.5 group-data-[collapsible=icon]:hidden",
        className
      ),
      ...props
    }
  );
}
function SidebarMenuSubItem({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    "li",
    {
      "data-slot": "sidebar-menu-sub-item",
      "data-sidebar": "menu-sub-item",
      className: cn("group/menu-sub-item relative", className),
      ...props
    }
  );
}
function SidebarMenuSubButton({
  asChild = false,
  size = "md",
  isActive = false,
  className,
  ...props
}) {
  const Comp = asChild ? Slot.Root : "a";
  return /* @__PURE__ */ jsx(
    Comp,
    {
      "data-slot": "sidebar-menu-sub-button",
      "data-sidebar": "menu-sub-button",
      "data-size": size,
      "data-active": isActive,
      className: cn(
        "flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 text-sidebar-foreground ring-sidebar-ring outline-hidden group-data-[collapsible=icon]:hidden hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[size=md]:text-sm data-[size=sm]:text-xs data-active:bg-sidebar-accent data-active:text-sidebar-accent-foreground [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0 [&>svg]:text-sidebar-accent-foreground",
        className
      ),
      ...props
    }
  );
}
function Collapsible({
  ...props
}) {
  return /* @__PURE__ */ jsx(Collapsible$1.Root, { "data-slot": "collapsible", ...props });
}
function CollapsibleTrigger({
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Collapsible$1.CollapsibleTrigger,
    {
      "data-slot": "collapsible-trigger",
      ...props
    }
  );
}
function CollapsibleContent({
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Collapsible$1.CollapsibleContent,
    {
      "data-slot": "collapsible-content",
      ...props
    }
  );
}
const PETA = {
  "bi-archive": Archive01Icon,
  "bi-arrow-counterclockwise": ArrowTurnBackwardIcon,
  "bi-arrow-left-right": Exchange01Icon,
  "bi-arrow-repeat": RefreshCwIcon,
  "bi-bell": Notification01Icon,
  "bi-box-arrow-in-down": Download01Icon,
  "bi-box-arrow-in-right": Login01Icon,
  "bi-box-arrow-right": Logout01Icon,
  "bi-box-arrow-up-right": LinkSquare01Icon,
  "bi-broadcast": RadioIcon,
  "bi-building": Building02Icon,
  "bi-building-fill-gear": FactoryIcon,
  "bi-calendar-plus": CalendarAdd01Icon,
  "bi-calendar-x": CalendarRemove01Icon,
  "bi-calendar": Calendar03Icon,
  "bi-card-list": ListViewIcon,
  "bi-chat-left-dots": Message01Icon,
  "bi-chat-left-quote": QuoteDownIcon,
  "bi-chat-left-text": Message01Icon,
  "bi-chat-quote": QuoteDownIcon,
  "bi-chat-square-text": Message01Icon,
  "bi-check-circle": CheckmarkCircle01Icon,
  "bi-check2": Tick02Icon,
  "bi-check2-circle": CheckmarkCircle02Icon,
  "bi-clipboard": ClipboardIcon,
  "bi-clipboard-check": ClipboardCheckIcon,
  "bi-clipboard-data": ClipboardListIcon,
  "bi-diagram-2": HierarchyIcon,
  "bi-diagram-3": HierarchySquare01Icon,
  // Ikon CADANGAN yang dikirim server untuk aksi audit yang tak dikenal
  // (`DocumentController::timeline()`, `DasborTampilan::aktivitas()`).
  // Dipetakan EKSPLISIT walau `ikon()` di bawah sudah jatuh ke `CircleIcon`:
  // penjaga `PratinjauUiTest` membandingkan daftar kunci, dan kunci yang
  // hanya "kebetulan benar" lewat jalur cadangan tak pernah bisa dibedakan
  // dari kunci yang memang terlupa.
  "bi-dot": CircleIcon,
  "bi-droplet-half": DropletIcon,
  "bi-envelope": Mail01Icon,
  "bi-envelope-open": MailOpen01Icon,
  "bi-exclamation-triangle": Alert02Icon,
  "bi-file-earmark-plus": FileAddIcon,
  "bi-file-earmark-ruled": FileValidationIcon,
  "bi-file-earmark-text": FileEditIcon,
  "bi-files": Files01Icon,
  "bi-folder-check": FolderCheckIcon,
  "bi-folder2-open": FolderOpenIcon,
  "bi-fonts": TextFontIcon,
  "bi-gear-fill": Settings01Icon,
  "bi-grid": GridViewIcon,
  "bi-hash": HashIcon,
  "bi-hdd-network-fill": ServerStack01Icon,
  "bi-hourglass-split": HourglassIcon,
  "bi-image": Image01Icon,
  "bi-inbox": InboxIcon,
  "bi-info-circle": InformationCircleIcon,
  "bi-info-square": InformationSquareIcon,
  "bi-journal-bookmark": BookMarkedIcon,
  "bi-journal-text": NotebookTextIcon,
  "bi-list-check": CheckListIcon,
  "bi-lock-fill": LockIcon,
  "bi-megaphone": Megaphone01Icon,
  "bi-patch-check": CheckmarkBadge01Icon,
  "bi-patch-check-fill": CheckmarkBadge01Icon,
  "bi-patch-question": HelpCircleIcon,
  "bi-pencil": PencilIcon,
  "bi-pencil-square": PencilEdit01Icon,
  "bi-people": UserMultipleIcon,
  "bi-people-fill": UserMultipleIcon,
  "bi-person-badge": IdentityCardIcon,
  "bi-person-check": UserCheck01Icon,
  "bi-person-lines-fill": UserSquareIcon,
  "bi-person-plus": UserAdd01Icon,
  "bi-pie-chart": PieChartIcon,
  "bi-reply": ReplyIcon,
  "bi-send": SentIcon,
  "bi-share-fill": Share01Icon,
  "bi-shield-exclamation": ShieldAlertIcon,
  "bi-shield-fill-check": ShieldCheckIcon,
  "bi-shield-lock": ShieldKeyIcon,
  "bi-slash-circle": CircleSlashIcon,
  "bi-sliders": FilterHorizontalIcon,
  "bi-sliders2": FilterHorizontalIcon,
  "bi-speedometer2": DashboardSpeed01Icon,
  "bi-spellcheck": SpellCheckIcon,
  "bi-stars": SparklesIcon,
  "bi-toggles": ToggleOnIcon,
  "bi-trash": Delete02Icon,
  "bi-truck": TruckIcon,
  "bi-ui-checks-grid": LayoutGridIcon,
  "bi-x-circle": CancelCircleIcon,
  "bi-x-octagon": OctagonXIcon
};
function ikonHuge(nama) {
  return nama && PETA[nama] || CircleIcon;
}
function Ikon({ nama, className }) {
  return /* @__PURE__ */ jsx(
    HugeiconsIcon,
    {
      icon: ikonHuge(nama),
      strokeWidth: 1.5,
      className,
      "aria-hidden": "true"
    }
  );
}
function DropdownMenu({
  ...props
}) {
  return /* @__PURE__ */ jsx(DropdownMenu$1.Root, { "data-slot": "dropdown-menu", ...props });
}
function DropdownMenuTrigger({
  ...props
}) {
  return /* @__PURE__ */ jsx(
    DropdownMenu$1.Trigger,
    {
      "data-slot": "dropdown-menu-trigger",
      ...props
    }
  );
}
function DropdownMenuContent({
  className,
  align = "start",
  sideOffset = 4,
  ...props
}) {
  return /* @__PURE__ */ jsx(DropdownMenu$1.Portal, { children: /* @__PURE__ */ jsx(
    DropdownMenu$1.Content,
    {
      "data-slot": "dropdown-menu-content",
      sideOffset,
      align,
      className: cn(
        "dark z-50 max-h-(--radix-dropdown-menu-content-available-height) w-(--radix-dropdown-menu-trigger-width) min-w-48 origin-(--radix-dropdown-menu-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-2xl p-1 text-popover-foreground shadow-2xl ring-1 ring-foreground/5 duration-100 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-[state=closed]:overflow-hidden dark:ring-foreground/10 data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95 animate-none! relative bg-popover/70 before:pointer-events-none before:absolute before:inset-0 before:-z-1 before:rounded-[inherit] before:backdrop-blur-2xl before:backdrop-saturate-150 **:data-[slot$=-item]:focus:bg-foreground/10 **:data-[slot$=-item]:data-highlighted:bg-foreground/10 **:data-[slot$=-separator]:bg-foreground/5 **:data-[slot$=-trigger]:focus:bg-foreground/10 **:data-[slot$=-trigger]:aria-expanded:bg-foreground/10! **:data-[variant=destructive]:focus:bg-foreground/10! **:data-[variant=destructive]:text-accent-foreground! **:data-[variant=destructive]:**:text-accent-foreground!",
        className
      ),
      ...props
    }
  ) });
}
function DropdownMenuGroup({
  ...props
}) {
  return /* @__PURE__ */ jsx(DropdownMenu$1.Group, { "data-slot": "dropdown-menu-group", ...props });
}
function DropdownMenuItem({
  className,
  inset,
  variant = "default",
  ...props
}) {
  return /* @__PURE__ */ jsx(
    DropdownMenu$1.Item,
    {
      "data-slot": "dropdown-menu-item",
      "data-inset": inset,
      "data-variant": variant,
      className: cn(
        "group/dropdown-menu-item relative flex cursor-default items-center gap-2.5 rounded-xl px-3 py-2 text-sm outline-hidden select-none focus:bg-accent focus:text-accent-foreground not-data-[variant=destructive]:focus:**:text-accent-foreground data-inset:pl-9.5 data-[variant=destructive]:text-destructive data-[variant=destructive]:focus:bg-destructive/10 data-[variant=destructive]:focus:text-destructive dark:data-[variant=destructive]:focus:bg-destructive/20 data-disabled:pointer-events-none data-disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 data-[variant=destructive]:*:[svg]:text-destructive",
        className
      ),
      ...props
    }
  );
}
function DropdownMenuLabel({
  className,
  inset,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    DropdownMenu$1.Label,
    {
      "data-slot": "dropdown-menu-label",
      "data-inset": inset,
      className: cn(
        "px-3 py-2.5 text-xs text-muted-foreground data-inset:pl-9.5",
        className
      ),
      ...props
    }
  );
}
function DropdownMenuSeparator({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    DropdownMenu$1.Separator,
    {
      "data-slot": "dropdown-menu-separator",
      className: cn("-mx-1 my-1 h-px bg-border/50", className),
      ...props
    }
  );
}
function NavUser() {
  const { isMobile } = useSidebar();
  const { auth } = usePage().props;
  const user = auth.user;
  if (!user) {
    return null;
  }
  const jabatan = user.jabatan_label || auth.roles[0]?.replaceAll("_", " ") || "-";
  return /* @__PURE__ */ jsx(SidebarMenu, { children: /* @__PURE__ */ jsx(SidebarMenuItem, { children: /* @__PURE__ */ jsxs(DropdownMenu, { children: [
    /* @__PURE__ */ jsx(DropdownMenuTrigger, { asChild: true, children: /* @__PURE__ */ jsxs(
      SidebarMenuButton,
      {
        size: "lg",
        tooltip: user.name,
        className: "data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground",
        children: [
          /* @__PURE__ */ jsx(Avatar, { nama: user.name, foto: user.photo_url, className: "size-8" }),
          /* @__PURE__ */ jsxs("div", { className: "grid flex-1 text-left text-sm leading-tight", children: [
            /* @__PURE__ */ jsx("span", { className: "truncate font-medium", children: user.name }),
            /* @__PURE__ */ jsx("span", { className: "text-muted-foreground truncate text-xs", children: jabatan })
          ] }),
          /* @__PURE__ */ jsx(Ikon, { nama: "bi-sliders", className: "ml-auto size-4" })
        ]
      }
    ) }),
    /* @__PURE__ */ jsxs(
      DropdownMenuContent,
      {
        className: "w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-2xl",
        side: isMobile ? "bottom" : "right",
        align: "end",
        sideOffset: 4,
        children: [
          /* @__PURE__ */ jsx(DropdownMenuLabel, { className: "p-0 font-normal", children: /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2 px-1 py-1.5 text-left text-sm", children: [
            /* @__PURE__ */ jsx(Avatar, { nama: user.name, foto: user.photo_url, className: "size-8" }),
            /* @__PURE__ */ jsxs("div", { className: "grid flex-1 text-left text-sm leading-tight", children: [
              /* @__PURE__ */ jsx("span", { className: "truncate font-medium", children: user.name }),
              /* @__PURE__ */ jsx("span", { className: "text-muted-foreground truncate text-xs", children: user.nrp })
            ] })
          ] }) }),
          /* @__PURE__ */ jsx(DropdownMenuSeparator, {}),
          /* @__PURE__ */ jsx(DropdownMenuGroup, { children: /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(Link, { href: route("account.info"), children: [
            /* @__PURE__ */ jsx(Ikon, { nama: "bi-person-badge", className: "size-4" }),
            "Informasi Akun"
          ] }) }) }),
          /* @__PURE__ */ jsx(DropdownMenuSeparator, {}),
          /* @__PURE__ */ jsxs(DropdownMenuItem, { variant: "destructive", onSelect: () => router.post(route("logout")), children: [
            /* @__PURE__ */ jsx(Ikon, { nama: "bi-box-arrow-right", className: "size-4" }),
            "Logout"
          ] })
        ]
      }
    )
  ] }) }) });
}
function AppSidebar({ ...props }) {
  const { navigation } = usePage().props;
  return /* @__PURE__ */ jsxs(Sidebar, { collapsible: "icon", ...props, children: [
    /* @__PURE__ */ jsx(SidebarHeader, { children: /* @__PURE__ */ jsx(SidebarMenu, { children: /* @__PURE__ */ jsx(SidebarMenuItem, { children: /* @__PURE__ */ jsx(
      SidebarMenuButton,
      {
        asChild: true,
        tooltip: "SmartPro",
        className: "h-16 group-data-[collapsible=icon]:h-8 data-[slot=sidebar-menu-button]:p-1.5!",
        children: /* @__PURE__ */ jsxs(Link, { href: "/dashboard", children: [
          /* @__PURE__ */ jsx(
            "img",
            {
              src: "/images/logo-web.png",
              alt: "SmartPro",
              className: "h-12 w-auto object-contain group-data-[collapsible=icon]:h-6 dark:hidden"
            }
          ),
          /* @__PURE__ */ jsx(
            "img",
            {
              src: "/images/logodarkmode.png",
              alt: "SmartPro",
              className: "hidden h-12 w-auto object-contain group-data-[collapsible=icon]:h-6 dark:block"
            }
          )
        ] })
      }
    ) }) }) }),
    /* @__PURE__ */ jsx(SidebarContent, { children: navigation.map((bagian, i) => /* @__PURE__ */ jsxs(SidebarGroup, { children: [
      bagian.label ? /* @__PURE__ */ jsx(SidebarGroupLabel, { children: bagian.label }) : null,
      /* @__PURE__ */ jsx(SidebarMenu, { children: bagian.items.map((item) => /* @__PURE__ */ jsx(Baris, { item }, item.label)) })
    ] }, i)) }),
    /* @__PURE__ */ jsx(SidebarFooter, { children: /* @__PURE__ */ jsx(NavUser, {}) })
  ] });
}
function Baris({ item }) {
  if (item.items) {
    return /* @__PURE__ */ jsx(Grup, { item });
  }
  return /* @__PURE__ */ jsxs(SidebarMenuItem, { children: [
    item.locked ? /* @__PURE__ */ jsx(Terkunci, { item }) : /* @__PURE__ */ jsx(Tautan, { item }),
    item.badge ? /* @__PURE__ */ jsx(SidebarMenuBadge, { children: item.badge }) : null
  ] });
}
function Grup({ item }) {
  return /* @__PURE__ */ jsx(Collapsible, { asChild: true, defaultOpen: item.active, className: "group/collapsible", children: /* @__PURE__ */ jsxs(SidebarMenuItem, { children: [
    /* @__PURE__ */ jsx(CollapsibleTrigger, { asChild: true, children: /* @__PURE__ */ jsxs(SidebarMenuButton, { tooltip: item.label, isActive: item.active, children: [
      /* @__PURE__ */ jsx(Ikon, { nama: item.icon, className: "size-4" }),
      /* @__PURE__ */ jsx("span", { children: item.label }),
      item.dot ? /* @__PURE__ */ jsx(
        "span",
        {
          className: "bg-primary size-1.5 shrink-0 rounded-full",
          title: "Ada yang perlu dikerjakan"
        }
      ) : null,
      /* @__PURE__ */ jsx(ChevronLipat, {})
    ] }) }),
    /* @__PURE__ */ jsx(CollapsibleContent, { children: /* @__PURE__ */ jsx(SidebarMenuSub, { children: item.items?.map((anak) => /* @__PURE__ */ jsxs(SidebarMenuSubItem, { children: [
      anak.locked ? /* @__PURE__ */ jsxs(Tooltip, { children: [
        /* @__PURE__ */ jsx(TooltipTrigger, { asChild: true, children: /* @__PURE__ */ jsx(SidebarMenuSubButton, { asChild: true, className: "cursor-not-allowed opacity-50", children: /* @__PURE__ */ jsxs("span", { children: [
          /* @__PURE__ */ jsx(Ikon, { nama: anak.icon, className: "size-4" }),
          /* @__PURE__ */ jsx("span", { children: anak.label }),
          /* @__PURE__ */ jsx(Ikon, { nama: "bi-lock-fill", className: "ml-auto size-3" })
        ] }) }) }),
        /* @__PURE__ */ jsx(TooltipContent, { side: "right", children: anak.title ?? anak.label })
      ] }) : /* @__PURE__ */ jsx(SidebarMenuSubButton, { asChild: true, isActive: anak.active, children: /* @__PURE__ */ jsxs(Link, { href: anak.href, title: anak.title ?? void 0, children: [
        /* @__PURE__ */ jsx(Ikon, { nama: anak.icon, className: "size-4" }),
        /* @__PURE__ */ jsx("span", { children: anak.label })
      ] }) }),
      anak.badge ? /* @__PURE__ */ jsx(SidebarMenuBadge, { className: "top-1", children: anak.badge }) : null
    ] }, anak.label)) }) })
  ] }) });
}
function ChevronLipat() {
  return /* @__PURE__ */ jsx(
    HugeiconsIcon,
    {
      icon: ArrowRight01Icon,
      strokeWidth: 1.5,
      "aria-hidden": "true",
      className: "ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
    }
  );
}
function Tautan({ item }) {
  return /* @__PURE__ */ jsx(SidebarMenuButton, { asChild: true, tooltip: item.label, isActive: item.active, children: /* @__PURE__ */ jsxs(Link, { href: item.href, title: item.title ?? void 0, children: [
    /* @__PURE__ */ jsx(Ikon, { nama: item.icon, className: "size-4" }),
    /* @__PURE__ */ jsx("span", { children: item.label })
  ] }) });
}
function Terkunci({ item }) {
  return /* @__PURE__ */ jsxs(Tooltip, { children: [
    /* @__PURE__ */ jsx(TooltipTrigger, { asChild: true, children: /* @__PURE__ */ jsx(SidebarMenuButton, { asChild: true, className: "cursor-not-allowed opacity-50", children: /* @__PURE__ */ jsxs("span", { children: [
      /* @__PURE__ */ jsx(Ikon, { nama: item.icon, className: "size-4" }),
      /* @__PURE__ */ jsx("span", { children: item.label }),
      /* @__PURE__ */ jsx(Ikon, { nama: "bi-lock-fill", className: "ml-auto size-3" })
    ] }) }) }),
    /* @__PURE__ */ jsx(TooltipContent, { side: "right", children: item.title ?? item.label })
  ] });
}
function Dialog({
  ...props
}) {
  return /* @__PURE__ */ jsx(Dialog$1.Root, { "data-slot": "dialog", ...props });
}
function DialogTrigger({
  ...props
}) {
  return /* @__PURE__ */ jsx(Dialog$1.Trigger, { "data-slot": "dialog-trigger", ...props });
}
function DialogPortal({
  ...props
}) {
  return /* @__PURE__ */ jsx(Dialog$1.Portal, { "data-slot": "dialog-portal", ...props });
}
function DialogClose({
  ...props
}) {
  return /* @__PURE__ */ jsx(Dialog$1.Close, { "data-slot": "dialog-close", ...props });
}
function DialogOverlay({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Dialog$1.Overlay,
    {
      "data-slot": "dialog-overlay",
      className: cn(
        "fixed inset-0 isolate z-50 bg-black/80 duration-100 supports-backdrop-filter:backdrop-blur-xs data-open:animate-in data-open:fade-in-0 data-closed:animate-out data-closed:fade-out-0",
        className
      ),
      ...props
    }
  );
}
function DialogContent({
  className,
  children,
  showCloseButton = true,
  ...props
}) {
  return /* @__PURE__ */ jsxs(DialogPortal, { children: [
    /* @__PURE__ */ jsx(DialogOverlay, {}),
    /* @__PURE__ */ jsxs(
      Dialog$1.Content,
      {
        "data-slot": "dialog-content",
        className: cn(
          "fixed top-1/2 left-1/2 z-50 grid w-full max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 gap-6 rounded-4xl bg-popover p-6 text-sm text-popover-foreground ring-1 ring-foreground/5 duration-100 outline-none sm:max-w-md data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95",
          className
        ),
        ...props,
        children: [
          children,
          showCloseButton && /* @__PURE__ */ jsx(Dialog$1.Close, { "data-slot": "dialog-close", asChild: true, children: /* @__PURE__ */ jsxs(
            Button,
            {
              variant: "ghost",
              className: "absolute top-4 right-4",
              size: "icon-sm",
              children: [
                /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Cancel01Icon, strokeWidth: 2 }),
                /* @__PURE__ */ jsx("span", { className: "sr-only", children: "Close" })
              ]
            }
          ) })
        ]
      }
    )
  ] });
}
function DialogHeader({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "dialog-header",
      className: cn("flex flex-col gap-2", className),
      ...props
    }
  );
}
function DialogFooter({
  className,
  showCloseButton = false,
  children,
  ...props
}) {
  return /* @__PURE__ */ jsxs(
    "div",
    {
      "data-slot": "dialog-footer",
      className: cn(
        "flex flex-col-reverse gap-2 sm:flex-row sm:justify-end",
        className
      ),
      ...props,
      children: [
        children,
        showCloseButton && /* @__PURE__ */ jsx(Dialog$1.Close, { asChild: true, children: /* @__PURE__ */ jsx(Button, { variant: "outline", children: "Close" }) })
      ]
    }
  );
}
function DialogTitle({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Dialog$1.Title,
    {
      "data-slot": "dialog-title",
      className: cn(
        "font-heading text-base leading-none font-medium",
        className
      ),
      ...props
    }
  );
}
function DialogDescription({
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Dialog$1.Description,
    {
      "data-slot": "dialog-description",
      className: cn(
        "text-sm text-muted-foreground *:[a]:underline *:[a]:underline-offset-3 *:[a]:hover:text-foreground",
        className
      ),
      ...props
    }
  );
}
function ItemGroup({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      role: "list",
      "data-slot": "item-group",
      className: cn(
        "group/item-group flex w-full flex-col gap-4 has-data-[size=sm]:gap-2.5 has-data-[size=xs]:gap-2",
        className
      ),
      ...props
    }
  );
}
const itemVariants = cva(
  "group/item flex w-full flex-wrap items-center rounded-2xl border text-sm transition-colors duration-100 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 [a]:transition-colors [a]:hover:bg-muted",
  {
    variants: {
      variant: {
        default: "border-transparent",
        outline: "border-border",
        muted: "border-transparent bg-muted/50"
      },
      size: {
        default: "gap-3.5 px-4 py-3.5",
        sm: "gap-3.5 px-3.5 py-3",
        xs: "gap-2.5 px-3 py-2.5 in-data-[slot=dropdown-menu-content]:p-0"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);
function Item({
  className,
  variant = "default",
  size = "default",
  asChild = false,
  ...props
}) {
  const Comp = asChild ? Slot.Root : "div";
  return /* @__PURE__ */ jsx(
    Comp,
    {
      "data-slot": "item",
      "data-variant": variant,
      "data-size": size,
      className: cn(itemVariants({ variant, size, className })),
      ...props
    }
  );
}
const itemMediaVariants = cva(
  "flex shrink-0 items-center justify-center gap-2 group-has-data-[slot=item-description]/item:translate-y-0.5 group-has-data-[slot=item-description]/item:self-start [&_svg]:pointer-events-none",
  {
    variants: {
      variant: {
        default: "bg-transparent",
        icon: "[&_svg:not([class*='size-'])]:size-4",
        image: "size-10 overflow-hidden rounded-lg group-data-[size=sm]/item:size-8 group-data-[size=xs]/item:size-6 group-data-[size=xs]/item:rounded-md [&_img]:size-full [&_img]:object-cover"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);
function ItemMedia({
  className,
  variant = "default",
  ...props
}) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "item-media",
      "data-variant": variant,
      className: cn(itemMediaVariants({ variant, className })),
      ...props
    }
  );
}
function ItemContent({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "item-content",
      className: cn(
        "flex flex-1 flex-col gap-1 group-data-[size=xs]/item:gap-0.5 [&+[data-slot=item-content]]:flex-none",
        className
      ),
      ...props
    }
  );
}
function ItemTitle({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "item-title",
      className: cn(
        "line-clamp-1 flex w-fit items-center gap-2 text-sm leading-snug font-medium underline-offset-4",
        className
      ),
      ...props
    }
  );
}
function ItemDescription({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "p",
    {
      "data-slot": "item-description",
      className: cn(
        "line-clamp-2 text-left text-sm font-normal text-muted-foreground [&>a]:underline [&>a]:underline-offset-4 [&>a:hover]:text-primary",
        className
      ),
      ...props
    }
  );
}
function ItemActions({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "div",
    {
      "data-slot": "item-actions",
      className: cn("flex items-center gap-2", className),
      ...props
    }
  );
}
function DialogAntrean() {
  const { antreanAwal } = usePage().props;
  const [buka, setBuka] = useState(true);
  if (!antreanAwal || antreanAwal.length === 0) {
    return null;
  }
  return /* @__PURE__ */ jsx(Dialog, { open: buka, onOpenChange: setBuka, children: /* @__PURE__ */ jsxs(DialogContent, { className: "sm:max-w-md", children: [
    /* @__PURE__ */ jsx(DialogHeader, { children: /* @__PURE__ */ jsxs(DialogTitle, { className: "flex items-center gap-2", children: [
      /* @__PURE__ */ jsx(Ikon, { nama: "bi-inbox", className: "size-4" }),
      "Ada ",
      antreanAwal.length,
      " hal yang menunggu Anda"
    ] }) }),
    /* @__PURE__ */ jsx("div", { className: "flex flex-col gap-1", children: antreanAwal.map((tugas) => /* @__PURE__ */ jsx(Item, { asChild: true, variant: "outline", size: "sm", children: /* @__PURE__ */ jsxs(Link, { href: tugas.url, onClick: () => setBuka(false), children: [
      /* @__PURE__ */ jsx(ItemMedia, { children: /* @__PURE__ */ jsx(Ikon, { nama: tugas.icon, className: "size-4" }) }),
      /* @__PURE__ */ jsx(ItemContent, { children: /* @__PURE__ */ jsx(ItemTitle, { children: tugas.label }) }),
      /* @__PURE__ */ jsx(Badge, { variant: "destructive", children: tugas.jumlah })
    ] }) }, tugas.url)) }),
    /* @__PURE__ */ jsx(DialogFooter, { children: /* @__PURE__ */ jsx(Button, { variant: "outline", size: "sm", onClick: () => setBuka(false), children: "Tutup" }) })
  ] }) });
}
function Breadcrumb({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "nav",
    {
      "aria-label": "breadcrumb",
      "data-slot": "breadcrumb",
      className: cn(className),
      ...props
    }
  );
}
function BreadcrumbList({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "ol",
    {
      "data-slot": "breadcrumb-list",
      className: cn(
        "flex flex-wrap items-center gap-1.5 text-sm wrap-break-word text-muted-foreground sm:gap-2.5",
        className
      ),
      ...props
    }
  );
}
function BreadcrumbItem({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "li",
    {
      "data-slot": "breadcrumb-item",
      className: cn("inline-flex items-center gap-1.5", className),
      ...props
    }
  );
}
function BreadcrumbLink({
  asChild,
  className,
  ...props
}) {
  const Comp = asChild ? Slot.Root : "a";
  return /* @__PURE__ */ jsx(
    Comp,
    {
      "data-slot": "breadcrumb-link",
      className: cn("transition-colors hover:text-foreground", className),
      ...props
    }
  );
}
function BreadcrumbPage({ className, ...props }) {
  return /* @__PURE__ */ jsx(
    "span",
    {
      "data-slot": "breadcrumb-page",
      role: "link",
      "aria-disabled": "true",
      "aria-current": "page",
      className: cn("font-normal text-foreground", className),
      ...props
    }
  );
}
function BreadcrumbSeparator({
  children,
  className,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    "li",
    {
      "data-slot": "breadcrumb-separator",
      role: "presentation",
      "aria-hidden": "true",
      className: cn("[&>svg]:size-3.5", className),
      ...props,
      children: children ?? /* @__PURE__ */ jsx(HugeiconsIcon, { icon: ArrowRight01Icon, strokeWidth: 2 })
    }
  );
}
function SiteHeader({ judul, remah = [] }) {
  return /* @__PURE__ */ jsx("header", { className: "flex h-(--header-height) shrink-0 items-center gap-2 border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)", children: /* @__PURE__ */ jsxs("div", { className: "flex w-full items-center gap-1 px-4 lg:gap-2 lg:px-6", children: [
    /* @__PURE__ */ jsx(SidebarTrigger, { className: "-ml-1" }),
    /* @__PURE__ */ jsx(Separator, { orientation: "vertical", className: "mx-2 data-[orientation=vertical]:h-4" }),
    remah.length > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx("h1", { className: "sr-only", children: judul }),
      /* @__PURE__ */ jsx(Breadcrumb, { children: /* @__PURE__ */ jsx(BreadcrumbList, { children: remah.map((r, i) => /* @__PURE__ */ jsxs(Fragment$1, { children: [
        i > 0 ? /* @__PURE__ */ jsx(BreadcrumbSeparator, {}) : null,
        /* @__PURE__ */ jsx(BreadcrumbItem, { children: r.href && i < remah.length - 1 ? /* @__PURE__ */ jsx(BreadcrumbLink, { asChild: true, children: /* @__PURE__ */ jsx(Link, { href: r.href, children: r.label }) }) : /* @__PURE__ */ jsx(BreadcrumbPage, { children: r.label }) })
      ] }, i)) }) })
    ] }) : /* @__PURE__ */ jsx("h1", { className: "truncate text-base font-medium", children: judul }),
    /* @__PURE__ */ jsxs("div", { className: "ml-auto flex items-center gap-1", children: [
      /* @__PURE__ */ jsx(Lonceng, {}),
      /* @__PURE__ */ jsx(TombolTema, {})
    ] })
  ] }) });
}
function TombolTema() {
  const { resolvedTheme, setTheme } = useTheme();
  return /* @__PURE__ */ jsxs(Tooltip, { children: [
    /* @__PURE__ */ jsx(TooltipTrigger, { asChild: true, children: /* @__PURE__ */ jsx(
      Button,
      {
        variant: "ghost",
        size: "icon",
        "aria-label": "Ganti tema",
        onClick: () => setTheme(resolvedTheme === "dark" ? "light" : "dark"),
        children: /* @__PURE__ */ jsx(MatahariBulan, {})
      }
    ) }),
    /* @__PURE__ */ jsx(TooltipContent, { children: "Ganti tema" })
  ] });
}
function MatahariBulan() {
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Sun03Icon, strokeWidth: 1.5, "aria-hidden": "true", className: "size-4 dark:hidden" }),
    /* @__PURE__ */ jsx(HugeiconsIcon, { icon: Moon02Icon, strokeWidth: 1.5, "aria-hidden": "true", className: "hidden size-4 dark:block" })
  ] });
}
function Lonceng() {
  const { notifications } = usePage().props;
  return /* @__PURE__ */ jsxs(DropdownMenu, { children: [
    /* @__PURE__ */ jsx(DropdownMenuTrigger, { asChild: true, children: /* @__PURE__ */ jsxs(Button, { variant: "ghost", size: "icon", className: "relative", "aria-label": "Notifikasi", children: [
      /* @__PURE__ */ jsx(Ikon, { nama: "bi-bell", className: "size-4" }),
      notifications.unread > 0 ? /* @__PURE__ */ jsx("span", { className: "bg-destructive text-primary-foreground absolute -top-0.5 -right-0.5 flex min-w-4 items-center justify-center rounded-full px-1 text-[10px] leading-4", children: notifications.unread > 99 ? "99+" : notifications.unread }) : null
    ] }) }),
    /* @__PURE__ */ jsxs(DropdownMenuContent, { align: "end", className: "max-h-[420px] w-[330px] overflow-auto rounded-2xl", children: [
      /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between px-2 py-1.5", children: [
        /* @__PURE__ */ jsx("span", { className: "text-sm font-medium", children: "Notifikasi" }),
        /* @__PURE__ */ jsx(
          Link,
          {
            href: route("notifications.index"),
            className: "bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-2.5 py-1 text-xs font-semibold transition-colors",
            children: "Lihat semua"
          }
        )
      ] }),
      /* @__PURE__ */ jsx(DropdownMenuSeparator, {}),
      notifications.items.length === 0 ? /* @__PURE__ */ jsx("p", { className: "text-muted-foreground py-6 text-center text-sm", children: "Belum ada notifikasi" }) : (
        /* Baris notifikasi DIBERI JARAK (revisi pemilik butir 3):
                               sebelumnya kedelapan butir menempel jadi satu blok abu-abu
                               dan batas antar-pesan hanya bisa ditebak dari huruf
                               kapitalnya. `gap-1.5` + `rounded-lg` per butir membuat tiap
                               pesan jadi kartunya sendiri.
        
                               Pembungkus `div` aman di dalam `DropdownMenuContent`:
                               navigasi papan-tik Radix memakai collection context, bukan
                               penelusuran anak langsung DOM. */
        /* @__PURE__ */ jsx("div", { className: "flex flex-col gap-1.5 p-1", children: notifications.items.map((n) => /* @__PURE__ */ jsx(DropdownMenuItem, { asChild: true, children: /* @__PURE__ */ jsxs(
          Link,
          {
            href: route("notifications.open", n.id),
            className: cn(
              "flex items-start gap-2 rounded-lg px-2 py-2",
              !n.read_at && "bg-muted font-medium"
            ),
            children: [
              /* @__PURE__ */ jsx(Ikon, { nama: n.icon, className: "mt-0.5 size-4 shrink-0" }),
              /* @__PURE__ */ jsx("span", { className: "flex-1 text-xs whitespace-normal", children: n.message }),
              !n.read_at ? /* @__PURE__ */ jsx("span", { className: "bg-muted-foreground mt-1 size-2 shrink-0 rounded-full" }) : null
            ]
          }
        ) }, n.id)) })
      )
    ] })
  ] });
}
function AppLayout({
  judul,
  remah,
  sub,
  aksi,
  children
}) {
  return /* @__PURE__ */ jsxs(
    SidebarProvider,
    {
      className: "ui-v2",
      style: {
        "--sidebar-width": "calc(var(--spacing) * 72)",
        "--header-height": "calc(var(--spacing) * 12)"
      },
      children: [
        /* @__PURE__ */ jsx(Head, { title: judul }),
        /* @__PURE__ */ jsx(AppSidebar, { variant: "inset" }),
        /* @__PURE__ */ jsxs(SidebarInset, { children: [
          /* @__PURE__ */ jsx(SiteHeader, { judul, remah }),
          /* @__PURE__ */ jsx(FlashToast, {}),
          /* @__PURE__ */ jsx(DialogAntrean, {}),
          /* @__PURE__ */ jsx("div", { className: "flex flex-1 flex-col", children: /* @__PURE__ */ jsx("div", { className: "@container/main flex flex-1 flex-col gap-2", children: /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-4 px-4 py-4 md:gap-6 md:py-6 lg:px-6", children: [
            sub || aksi ? /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-start justify-between gap-3", children: [
              sub ? /* @__PURE__ */ jsx("p", { className: "text-muted-foreground min-w-0 text-sm", children: sub }) : /* @__PURE__ */ jsx("span", {}),
              aksi ? /* @__PURE__ */ jsx("div", { className: "flex flex-wrap items-center gap-2", children: aksi }) : null
            ] }) : null,
            children
          ] }) }) })
        ] })
      ]
    }
  );
}
function FlashToast() {
  const { flash } = usePage().props;
  useEffect(() => {
    if (flash.success) toast.success(flash.success);
    if (flash.error) toast.error(flash.error);
  }, [flash.success, flash.error]);
  return null;
}
export {
  AppLayout as A,
  Badge as B,
  Dialog as D,
  Ikon as I,
  Separator as S,
  Avatar as a,
  DialogTrigger as b,
  DialogContent as c,
  DialogHeader as d,
  DialogTitle as e,
  DialogDescription as f,
  DialogFooter as g,
  DialogClose as h,
  ItemGroup as i,
  Item as j,
  ItemMedia as k,
  ItemContent as l,
  ItemTitle as m,
  ItemDescription as n,
  DropdownMenuItem as o,
  DropdownMenu as p,
  DropdownMenuTrigger as q,
  DropdownMenuContent as r,
  DropdownMenuLabel as s,
  ItemActions as t,
  useIsMobile as u
};
