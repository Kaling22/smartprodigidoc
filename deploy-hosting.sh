#!/usr/bin/env bash
# Dijalankan DI SERVER, sekali, sesudah zip di-extract di public_html.
#   bash deploy-hosting.sh
#
# Urutannya terkunci di sini supaya tak ada langkah yang terlewat saat rilis
# berikutnya. Yang TIDAK dikerjakan skrip ini, dan memang tak boleh:
#   - menyentuh .env  (milik server, tak pernah ikut zip)
#   - menyentuh storage/ dan public/storage  (data produksi)
set -e
cd "$(dirname "$0")"

PHP="${PHP:-/opt/alt/php85/usr/bin/php}"
echo "== PHP: $($PHP -v | head -1)"

# .env WAJIB milik server. Kalau MAIL_HOST masih Mailpit, .env laptop terbawa.
if grep -q '^MAIL_HOST=127.0.0.1' .env; then
  echo "BERHENTI: .env laptop terbawa ke server (MAIL_HOST=127.0.0.1)."
  exit 1
fi

[ -d vendor ] || composer install --no-dev --optimize-autoloader

# Migration dijalankan hanya bila diminta EKSPLISIT. Salah satu migration yang
# tertunda MENGHAPUS kolom, dan di produksi itu tak bisa dibatalkan tanpa dump.
PENDING=$($PHP artisan migrate:status 2>/dev/null | grep -ci 'pending' || true)
echo "== Migration tertunda: $PENDING"
if [ "$PENDING" -gt 0 ]; then
  $PHP artisan migrate:status | grep -i pending || true
  if [ "${MIGRASI:-}" = "ya" ]; then
    $PHP artisan migrate --force
  else
    echo
    echo "BERHENTI: ada $PENDING migration tertunda dan belum dikonfirmasi."
    echo "1) Cadangkan basis data lebih dulu (hPanel > Databases > Export)."
    echo "2) Ulangi dengan:  MIGRASI=ya bash deploy-hosting.sh"
    exit 1
  fi
fi

# Cache dibuang dulu, bukan langsung ditulis ulang: config lama tetap terbaca
# sampai berkasnya benar-benar hilang.
$PHP artisan config:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan queue:restart

echo "== Verifikasi"
$PHP artisan config:show mail | grep -E 'default|host|port|scheme' || true
$PHP -r 'echo file_exists("public/build/manifest.json") ? "manifest OK" : "MANIFEST HILANG"; echo PHP_EOL;'
ls -l public/storage || echo "public/storage TIDAK ADA - jalankan: $PHP artisan storage:link"
$PHP artisan tinker --execute='echo "jobs=".DB::table("jobs")->count()." | failed=".DB::table("failed_jobs")->count().PHP_EOL;'
echo "== Selesai"
