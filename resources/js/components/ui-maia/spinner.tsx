import { cn } from "@/lib/utils"
import { HugeiconsIcon } from "@hugeicons/react"
import { Loading03Icon } from "@hugeicons/core-free-icons"

/*
 | SUNTINGAN TANGAN atas berkas hasil generate — dibenarkan REDESAIN-UI-V2 §4.1
 | sebagai "cacat generate yang membuat build gagal". Yang diramalkan di sana
 | (`icon-placeholder` di sidebar) ternyata sudah dibuang CLI; yang benar-benar
 | muncul adalah ini.
 |
 | Registry maia menyatakan props Spinner sebagai props <svg>, yang mendeklarasi
 | `strokeWidth?: string | number`. `HugeiconsIcon` hanya menerima `number`, jadi
 | spread `{...props}` menabrak `strokeWidth={2}` di bawah dan `tsc --noEmit`
 | merah. Diperbaiki dengan membuang SATU kunci itu dari tipe props — bukan
 | mengganti tipenya — supaya diff-nya sekecil mungkin dan `npx shadcn diff`
 | tetap mudah dibaca saat komponen ini kelak diperbarui.
 */
function Spinner({ className, ...props }: Omit<React.ComponentProps<"svg">, "strokeWidth">) {
  return (
    <HugeiconsIcon icon={Loading03Icon} strokeWidth={2} data-slot="spinner" role="status" aria-label="Loading" className={cn("size-4 animate-spin", className)} {...props} />
  )
}

export { Spinner }
