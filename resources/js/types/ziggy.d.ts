/**
 * Ziggy — `@routes` di `resources/views/app.blade.php` menyisipkan daftar rute
 * Laravel beserta fungsi `route()` ke global peramban.
 *
 * Tipenya dideklarasikan seadanya di sini, bukan dibangkitkan
 * `php artisan ziggy:generate --types`: berkas bangkitan itu harus ikut
 * di-commit dan disegarkan tiap kali sebuah rute lahir, dan berkas yang basi
 * justru berbohong. Nama rute tetap dijaga server — `route()` melempar galat
 * bila namanya tak ada.
 */
declare function route(nama: string, parameter?: unknown, absolut?: boolean): string;
