import {
    Alert02Icon, Archive01Icon, ArrowTurnBackwardIcon, BookMarkedIcon, Building02Icon,
    Calendar03Icon, CalendarAdd01Icon, CalendarRemove01Icon, CancelCircleIcon, CheckListIcon,
    CheckmarkBadge01Icon, CheckmarkCircle01Icon, CheckmarkCircle02Icon, CircleIcon, CircleSlashIcon,
    ClipboardCheckIcon, ClipboardIcon, ClipboardListIcon, DashboardSpeed01Icon, Delete02Icon,
    Download01Icon, DropletIcon, Exchange01Icon, FactoryIcon, FileAddIcon, FileEditIcon,
    Files01Icon, FileValidationIcon, FolderCheckIcon, FolderOpenIcon, GridViewIcon, HashIcon,
    HelpCircleIcon, HierarchyIcon, HierarchySquare01Icon, HourglassIcon, IdentityCardIcon,
    Image01Icon, InboxIcon, InformationCircleIcon, InformationSquareIcon, LayoutGridIcon,
    LinkSquare01Icon, ListViewIcon, LockIcon, Login01Icon, Logout01Icon, Mail01Icon, MailOpen01Icon,
    Megaphone01Icon, Message01Icon, Notification01Icon, NotebookTextIcon, OctagonXIcon,
    PencilEdit01Icon, PencilIcon, PieChartIcon, QuoteDownIcon, RadioIcon, RefreshCwIcon, ReplyIcon,
    SentIcon, ServerStack01Icon, Settings01Icon, Share01Icon, ShieldAlertIcon, ShieldCheckIcon,
    ShieldKeyIcon, FilterHorizontalIcon, SparklesIcon, SpellCheckIcon, TextFontIcon, Tick02Icon,
    ToggleOnIcon, TruckIcon, UserAdd01Icon, UserCheck01Icon, UserMultipleIcon, UserSquareIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

/**
 * Peta Bootstrap Icons → hugeicons — kembaran V2 dari `components/Ikon.tsx`.
 *
 * KUNCINYA SAMA PERSIS, dan itu bukan kerapian melainkan keharusan: setiap
 * `bi-*` di bawah datang dari `Document::STATUS_META`, `NavigasiSidebar`,
 * `AntreanTugas`, kolom `informasi_kategori.ikon`, atau payload notifikasi —
 * seluruhnya konstanta PHP atau baris tabel. Satu kunci yang bergeser di sini
 * tidak melempar galat; ia jatuh diam-diam ke lingkaran netral, dan tak
 * seorang pun tahu sampai ada yang membandingkan dua tangkapan layar.
 *
 * Karena itu himpunan kuncinya dijaga tes: `PratinjauUiTest` memastikan berkas
 * ini memuat SETIAP kunci yang ada di `components/Ikon.tsx`.
 *
 * `components/Ikon.tsx` (lucide) sengaja TIDAK disentuh — 41 halaman lama
 * masih memakainya sebagai pembanding. Tranche 4 menukar keduanya dan
 * mencopot `lucide-react`.
 *
 * API hugeicons bukan komponen per ikon seperti lucide melainkan satu
 * `<HugeiconsIcon icon={…} />`, jadi nilainya di sini objek data, bukan
 * komponen — itulah kenapa pembungkusnya wajib dan impor langsung dilarang.
 */
type Glif = typeof CircleIcon;

const PETA: Record<string, Glif> = {
    'bi-archive': Archive01Icon,
    'bi-arrow-counterclockwise': ArrowTurnBackwardIcon,
    'bi-arrow-left-right': Exchange01Icon,
    'bi-arrow-repeat': RefreshCwIcon,
    'bi-bell': Notification01Icon,
    'bi-box-arrow-in-down': Download01Icon,
    'bi-box-arrow-in-right': Login01Icon,
    'bi-box-arrow-right': Logout01Icon,
    'bi-box-arrow-up-right': LinkSquare01Icon,
    'bi-broadcast': RadioIcon,
    'bi-building': Building02Icon,
    'bi-building-fill-gear': FactoryIcon,
    'bi-calendar-plus': CalendarAdd01Icon,
    'bi-calendar-x': CalendarRemove01Icon,
    'bi-calendar': Calendar03Icon,
    'bi-card-list': ListViewIcon,
    'bi-chat-left-dots': Message01Icon,
    'bi-chat-left-quote': QuoteDownIcon,
    'bi-chat-left-text': Message01Icon,
    'bi-chat-quote': QuoteDownIcon,
    'bi-chat-square-text': Message01Icon,
    'bi-check-circle': CheckmarkCircle01Icon,
    'bi-check2': Tick02Icon,
    'bi-check2-circle': CheckmarkCircle02Icon,
    'bi-clipboard': ClipboardIcon,
    'bi-clipboard-check': ClipboardCheckIcon,
    'bi-clipboard-data': ClipboardListIcon,
    'bi-diagram-2': HierarchyIcon,
    'bi-diagram-3': HierarchySquare01Icon,
    // Ikon CADANGAN yang dikirim server untuk aksi audit yang tak dikenal
    // (`DocumentController::timeline()`, `DasborTampilan::aktivitas()`).
    // Dipetakan EKSPLISIT walau `ikon()` di bawah sudah jatuh ke `CircleIcon`:
    // penjaga `PratinjauUiTest` membandingkan daftar kunci, dan kunci yang
    // hanya "kebetulan benar" lewat jalur cadangan tak pernah bisa dibedakan
    // dari kunci yang memang terlupa.
    'bi-dot': CircleIcon,
    'bi-droplet-half': DropletIcon,
    'bi-envelope': Mail01Icon,
    'bi-envelope-open': MailOpen01Icon,
    'bi-exclamation-triangle': Alert02Icon,
    'bi-file-earmark-plus': FileAddIcon,
    'bi-file-earmark-ruled': FileValidationIcon,
    'bi-file-earmark-text': FileEditIcon,
    'bi-files': Files01Icon,
    'bi-folder-check': FolderCheckIcon,
    'bi-folder2-open': FolderOpenIcon,
    'bi-fonts': TextFontIcon,
    'bi-gear-fill': Settings01Icon,
    'bi-grid': GridViewIcon,
    'bi-hash': HashIcon,
    'bi-hdd-network-fill': ServerStack01Icon,
    'bi-hourglass-split': HourglassIcon,
    'bi-image': Image01Icon,
    'bi-inbox': InboxIcon,
    'bi-info-circle': InformationCircleIcon,
    'bi-info-square': InformationSquareIcon,
    'bi-journal-bookmark': BookMarkedIcon,
    'bi-journal-text': NotebookTextIcon,
    'bi-list-check': CheckListIcon,
    'bi-lock-fill': LockIcon,
    'bi-megaphone': Megaphone01Icon,
    'bi-patch-check': CheckmarkBadge01Icon,
    'bi-patch-check-fill': CheckmarkBadge01Icon,
    'bi-patch-question': HelpCircleIcon,
    'bi-pencil': PencilIcon,
    'bi-pencil-square': PencilEdit01Icon,
    'bi-people': UserMultipleIcon,
    'bi-people-fill': UserMultipleIcon,
    'bi-person-badge': IdentityCardIcon,
    'bi-person-check': UserCheck01Icon,
    'bi-person-lines-fill': UserSquareIcon,
    'bi-person-plus': UserAdd01Icon,
    'bi-pie-chart': PieChartIcon,
    'bi-reply': ReplyIcon,
    'bi-send': SentIcon,
    'bi-share-fill': Share01Icon,
    'bi-shield-exclamation': ShieldAlertIcon,
    'bi-shield-fill-check': ShieldCheckIcon,
    'bi-shield-lock': ShieldKeyIcon,
    'bi-slash-circle': CircleSlashIcon,
    'bi-sliders': FilterHorizontalIcon,
    'bi-sliders2': FilterHorizontalIcon,
    'bi-speedometer2': DashboardSpeed01Icon,
    'bi-spellcheck': SpellCheckIcon,
    'bi-stars': SparklesIcon,
    'bi-toggles': ToggleOnIcon,
    'bi-trash': Delete02Icon,
    'bi-truck': TruckIcon,
    'bi-ui-checks-grid': LayoutGridIcon,
    'bi-x-circle': CancelCircleIcon,
    'bi-x-octagon': OctagonXIcon,
};

export function ikonHuge(nama: string | null | undefined): Glif {
    return (nama && PETA[nama]) || CircleIcon;
}

/**
 * Menggambar satu ikon dari nama `bi-*`.
 *
 * `strokeWidth` 1.5 adalah bawaan gaya maia — ikon 2px terasa berat di samping
 * teks `text-sm`. Ukurannya tidak disetel di sini melainkan diserahkan pada
 * `className` (`size-4`/`size-5`), supaya ia ikut skala Tailwind seperti
 * kembarannya yang lucide dan bukan angka piksel lepas.
 */
export function Ikon({ nama, className }: { nama: string | null | undefined; className?: string }) {
    return (
        <HugeiconsIcon
            icon={ikonHuge(nama)}
            strokeWidth={1.5}
            className={className}
            aria-hidden="true"
        />
    );
}
