@echo off
REM ============================================================================
REM  SmartPro - jalankan server + pekerja antrean sekaligus.
REM
REM  KENAPA ADA: notifikasi EMAIL dikirim lewat antrean (lihat
REM  DocumentNotification::viaConnections) supaya aksi Tinjau/Setujui tidak
REM  ikut menunggu SMTP. Konsekuensinya, tanpa `queue:work` yang berjalan,
REM  email MENGENDAP di tabel `jobs` dan Mailpit tetap kosong selamanya —
REM  tanpa satu pun pesan salah. Lonceng tetap muncul (kanal database sengaja
REM  sinkron), jadi kegagalannya SEPARUH dan justru sulit disadari.
REM
REM  Skrip ini membuka dua jendela supaya keduanya mustahil terlupa.
REM ============================================================================

cd /d "%~dp0"

echo Alamat aplikasi : http://10.7.110.101:9092
echo Kotak surat uji : http://10.7.110.101:8025   (Mailpit)
echo.
echo Membuka dua jendela: server dan pekerja antrean.
echo Tutup jendela ini kapan saja; keduanya berjalan sendiri.
echo.

start "SmartPro - Server"  cmd /k php artisan serve --host=0.0.0.0 --port=9092
start "SmartPro - Antrean" cmd /k php artisan queue:work --tries=3

echo Selesai. Pastikan Mailpit juga berjalan.
timeout /t 5 >nul
