import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** Gabung kelas Tailwind — dipakai seluruh komponen shadcn/ui. */
export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}
