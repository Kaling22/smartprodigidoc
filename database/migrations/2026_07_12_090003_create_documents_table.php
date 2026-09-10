<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel documents dalam bentuk FINAL.
 *
 * Di project lama bentuk ini terbentuk lewat 4 migration susulan
 * (`expand_documents_status_enum`, `add_waiting_for_review_status`,
 * `add_temp_final_numbers`, `add_revises_document_id`). Semuanya dilebur —
 * urutan kolom & isi enum dijaga PERSIS (docs/schema-baseline.sql).
 */
return new class extends Migration
{
    /**
     * Nilai status. Delapan pertama yang dipakai alur sekarang; tiga terakhir
     * WARISAN v1/v2 yang sudah tak dipakai kode mana pun.
     *
     * Warisan sengaja DIPERTAHANKAN agar skema tetap identik dengan project
     * lama sehingga data lama bisa diimpor apa adanya. Membuangnya adalah
     * kandidat bersih-bersih TERSENDIRI — hanya setelah paritas terbukti dan
     * dipastikan tak ada satu baris pun yang masih memakainya.
     */
    private const STATUSES = [
        'draft', 'waiting_for_review', 'in_review', 'rejected', 'pending_approval',
        'published', 'sedang_direvisi', 'obsolete',
        // warisan v1/v2 — tidak dipakai lagi
        'submitted', 'needs_revision', 'archived',
    ];

    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            // Penomoran dua tahap: SEMENTARA saat penyusunan, FINAL dikunci saat
            // disahkan. `doc_number` dipertahankan sebagai nomor tampil warisan.
            $table->string('doc_number_temp')->nullable()->index();
            $table->string('doc_number_final')->nullable()->index();
            $table->string('doc_number')->nullable()->index();      // PPA-ADRO-SOP-ICTMD-01
            $table->boolean('doc_number_manual')->default(false);

            $table->foreignId('document_type_id')->constrained();
            $table->foreignId('department_id')->index();
            $table->string('title');

            $table->enum('status', self::STATUSES)->default('draft')->index();
            $table->unsignedInteger('current_step')->default(1);
            $table->unsignedInteger('revision_round')->default(0);  // revisi akibat penolakan (Tipe A)
            $table->unsignedInteger('no_revisi')->default(0);       // revisi pasca-terbit (Tipe B)

            // Penanda draft revisi Tipe B — menunjuk dokumen Berlaku yang direvisi.
            // Inilah penentu "ini revisi", BUKAN `no_revisi > 0` (roll-over
            // mengembalikan revisi ke 0 saat naik edisi).
            $table->foreignId('revises_document_id')->nullable()
                ->constrained('documents')->nullOnDelete();

            $table->string('edisi')->nullable();
            $table->boolean('is_controlled')->default(true);        // terkendali vs tidak terkendali

            // Peserta alur, dipilih pembuat saat mengisi wizard.
            $table->foreignId('reviewer_id')->nullable()->index();
            $table->foreignId('approver_id')->nullable()->index();
            $table->foreignId('created_by')->index();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();          // penentu cap APPROVED di PDF
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
