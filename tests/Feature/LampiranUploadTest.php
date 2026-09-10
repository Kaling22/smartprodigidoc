<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LampiranUploadTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Unggah gambar lewat kiriman form biasa.
     *
     * Sejak Fase B, bab SOP yang menampung GAMBAR adalah FLOWCHART — lampiran
     * berubah jadi rujukan ke dokumen lain (document_picker) dan tak lagi punya
     * kolom gambar. Jalur unggahnya sendiri tak berubah sedikit pun.
     */
    public function test_lampiran_image_is_stored_and_pathed(): void
    {
        Storage::fake('public');

        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Uji Lampiran');
        $doc->update(['current_step' => 2]);

        $this->actingAs($gl)
            ->post(route('documents.saveStep', $doc), [
                'step' => 2,
                'action' => 'save',
                'sections' => ['flowchart' => [['judul' => 'Alur Perangkat', 'gambar' => '', 'keterangan' => 'Contoh perangkat']]],
                'files' => ['flowchart' => [['gambar' => UploadedFile::fake()->image('foto.png', 300, 200)]]],
            ])
            ->assertRedirect();

        $doc->refresh();
        $gambar = $doc->contentMap()['flowchart'][0]['gambar'] ?? null;

        $this->assertNotNull($gambar, 'gambar flowchart should not be null after upload');
        $this->assertStringStartsWith('lampiran/', (string) $gambar, 'gambar should be a stored image path');
        Storage::disk('public')->assertExists($gambar);
        $this->assertSame(1, $doc->attachments()->count(), 'an attachment row should be created');
    }

    public function test_ajax_lampiran_upload_returns_stored_path(): void
    {
        Storage::fake('public');

        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Uji Upload AJAX');

        $response = $this->actingAs($gl)
            ->postJson(route('documents.uploadAttachment', $doc), [
                'section' => 'lampiran',
                'image' => UploadedFile::fake()->image('foto.png', 300, 200),
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $path = $response->json('path');
        $this->assertStringStartsWith('lampiran/', (string) $path, 'endpoint mengembalikan path tersimpan');
        Storage::disk('public')->assertExists($path);
        $this->assertSame(1, $doc->attachments()->count(), 'satu baris attachment dibuat');
    }

    /**
     * Batas naik 2MB -> 5MB (W-3). Berkas 3MB dulu ditolak (>2048), sekarang
     * harus lolos (<5120).
     */
    public function test_ajax_lampiran_upload_menerima_berkas_3mb(): void
    {
        Storage::fake('public');

        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Uji Upload 3MB');

        $response = $this->actingAs($gl)
            ->postJson(route('documents.uploadAttachment', $doc), [
                'section' => 'lampiran',
                'image' => UploadedFile::fake()->image('foto.png', 300, 200)->size(3072),
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($response->json('path'));
    }
}
