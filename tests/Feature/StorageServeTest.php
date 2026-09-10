<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Foto profil & lampiran harus tetap tersaji walau symlink `public/storage`
 * tidak ada — kasus paling sering di shared hosting (lihat routes/web.php).
 */
class StorageServeTest extends TestCase
{
    public function test_foto_tersaji_tanpa_symlink(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('avatar.png')->store('avatars', 'public');

        $this->get('/storage/'.$path)
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_berkas_tak_ada_menjadi_404(): void
    {
        Storage::fake('public');

        $this->get('/storage/avatars/tidak-ada.png')->assertNotFound();
    }

    /** Folder di luar avatars/lampiran tidak boleh ikut tersaji. */
    public function test_folder_lain_tidak_tersaji(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('rahasia/kunci.txt', 'jangan dibaca');

        $this->get('/storage/rahasia/kunci.txt')->assertNotFound();
    }
}
