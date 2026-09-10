<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status centang per tindakan pengendalian dalam satu pelaksanaan pekerjaan.
 *
 * Struktur JSA: Langkah → Bahaya → Pengendalian (1-N per bahaya).
 * Tiga kolom indeks (langkah_ke / bahaya_ke / pengendalian_ke) menunjuk
 * tepat satu tindakan pengendalian di dalam analisa_snapshot milik
 * job_executions, sehingga tidak perlu menduplikasi teks pengendaliannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_execution_id')
                ->constrained('job_executions')
                ->cascadeOnDelete();

            // Indeks 0-based menunjuk posisi di dalam analisa_snapshot.
            $table->unsignedSmallInteger('langkah_ke');
            $table->unsignedSmallInteger('bahaya_ke');
            $table->unsignedSmallInteger('pengendalian_ke');

            $table->boolean('checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->string('catatan')->nullable();

            $table->timestamps();

            // Satu kombinasi job + posisi hanya boleh muncul sekali.
            $table->unique(
                ['job_execution_id', 'langkah_ke', 'bahaya_ke', 'pengendalian_ke'],
                'uq_checklist_position'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_checklist_items');
    }
};
