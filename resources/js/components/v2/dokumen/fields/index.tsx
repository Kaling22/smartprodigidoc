/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/fields/index.tsx` (REDESAIN-UI-V2 T2).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser. Yang berubah hanya
 * dari kit mana komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya
 * (lucide → hugeicons, §5). Berkas aslinya sengaja TIDAK disentuh: 41 halaman
 * lama masih memakainya sebagai pembanding selama jendela pratinjau. Tranche 4
 * menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 */
import type { ComponentType } from 'react';

import { JsaAnalysis } from './JsaAnalysis';
import { RepeatableGroup } from './RepeatableGroup';
import { RichList } from './RichList';
import { Text } from './Text';
import { UserPicker } from './UserPicker';
import type { PropsField } from './konteks';

export { PenyediaWizard, useWizard, type KonteksWizard, type PropsField } from './konteks';
export { barisLogBaru, barisLogBerisi, RevisionLog, type BarisLog, type SubBarisLog } from './RevisionLog';

/**
 * `section.type` → komponen — pengganti larik `$partials` +
 * `@include($partial)` dinamis di `edit.blade.php:112`.
 *
 * Inilah satu-satunya tempat jenis seksi dipetakan ke rupanya. Menambah tipe
 * seksi baru = menambah satu baris di sini; menambah SEKSI baru (dari tipe yang
 * sudah ada) tidak menyentuh berkas ini sama sekali — cukup `schema_json`
 * (pakem P1).
 *
 * `signature` sengaja TIDAK dipetakan, sama seperti di Blade: tak ada satu pun
 * schema yang memakainya, dan `_signature.blade.php` memang tak pernah dirender
 * (peninjau & penyetuju dipilih lewat `user_picker`, bukan lewat tipe seksi
 * tersendiri).
 */
const PETA: Record<string, ComponentType<PropsField>> = {
    rich_list: RichList,
    reference_picker: RichList,
    repeatable_group: RepeatableGroup,
    jsa_analysis: JsaAnalysis,
    user_picker: UserPicker,
    text: Text,
    textarea: Text,
    date: Text, // widget tanggal (bukan diketik)
};

/**
 * Satu seksi schema, digambar oleh komponen yang sesuai tipenya.
 *
 * Tipe yang tak dikenal jatuh ke `Text` — persis seperti Blade
 * (`$partials[$section['type']] ?? 'documents.fields._text'`). Kolom teks yang
 * salah rupa jauh lebih baik daripada bab yang lenyap tanpa jejak.
 */
export function Seksi({ section, value, onChange }: PropsField) {
    const Komponen = PETA[section.type] ?? Text;

    return <Komponen section={section} value={value} onChange={onChange} />;
}
