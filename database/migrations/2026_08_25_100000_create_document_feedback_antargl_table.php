<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Masukan sejawat antar-Group Leader (PLAN-AKSES-v8 Fase 2a).
 *
 * GL yang tak diberi profil akses kini punya sidebar penuh tanpa satu pun
 * dokumen, padahal rekan sedepartemennya menyusun dokumen yang ia pahami
 * isinya. Tabel ini kanal "ikut membaca dan menyumbang tanpa menyusun".
 *
 * KENAPA TABEL SENDIRI, bukan menumpang `reviews` + `review_annotations`:
 * `reviews` adalah tabel KEPUTUSAN — kolom `decision` dibaca
 * DocumentLogController::dariReviews(), ReviewController::show()
 * ($priorAnnotations), dan DocumentController::edit() ("Rangkuman Peninjau").
 * Menyelipkan baris yang bukan keputusan ke sana membuat masukan sejawat bocor
 * ke layar peninjau resmi dan ke form revisi pembuat, lalu menuntut penyaring
 * baru di tiga tempat sekaligus. Tabel sendiri: nol blast radius.
 *
 * Juga BUKAN `document_feedback` — tabel itu punya siklus hidupnya sendiri
 * (nomor masukan, status baru/dibaca/diadopsi/ditolak, balasan, adopsi jadi
 * revisi) yang tak satu pun berlaku di sini, dan pemilik sudah memutuskan
 * keduanya kanal terpisah: yang itu milik Non-Staff atas dokumen BERLAKU,
 * yang ini milik sesama GL atas dokumen yang MASIH BISA DIUBAH.
 *
 * NAMA TABEL sengaja berprefiks `document_feedback_` supaya berdiri sejajar
 * dengan saudaranya itu di daftar tabel, dan seluruhnya HURUF KECIL: MySQL di
 * Windows menyimpan nama tabel dalam huruf kecil (`lower_case_table_names=1`),
 * jadi nama bercampur besar-kecil akan cocok di sini tapi gagal begitu aplikasi
 * dipasang di server Linux yang membedakan huruf besar-kecil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_feedback_antargl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            // Pemberi masukan. RESTRICT bawaan: menghapus akun yang pernah
            // memberi masukan tak boleh diam-diam menghilangkan catatannya.
            $table->foreignId('user_id')->constrained();
            // Isi textarea "Ringkasan / Catatan Umum" — boleh kosong bila
            // seluruh masukan berupa catatan per bagian.
            $table->text('ringkasan')->nullable();
            // [{section_key, item_ref, komentar}]. JSON, bukan tabel anak:
            // barisnya tak pernah dicari satu-satu, hanya ditampilkan sebagai
            // satu blok — sama seperti `document_contents.value_json`.
            $table->json('catatan_json');
            // Diisi saat PEMILIK dokumen membukanya (bukan Admin/MD yang lewat).
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_feedback_antargl');
    }
};
