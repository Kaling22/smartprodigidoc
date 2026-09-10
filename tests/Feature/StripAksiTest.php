<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Strip aksi mendatar pada kolom Aksi (PLAN C §C3).
 *
 * Yang dijaga di sini cuma satu hal, dan justru yang paling tak terlihat:
 * strip aksi TIDAK boleh merender tombolnya bila isinya kosong. Sejak Fase C,
 * SH/DH & PJO hanya membaca Dokumen Berlaku — tanpa penjaga itu mereka mendapat
 * tombol "Aksi" yang membuka pil kosong, dan itu tak akan pernah ketahuan dari
 * test lain mana pun.
 *
 * Sejak Fase 8 halamannya Inertia dan `components/dokumen/StripAksi.tsx` yang
 * memutuskannya, dari isinya sendiri (`Children.toArray(...).length`). Yang
 * diperiksa di sini karena itu bukan lagi markupnya melainkan kelima `boleh_*`
 * yang mengisi menu itu: kalau semuanya `false`, tak ada yang bisa digambar.
 */
class StripAksiTest extends TestCase
{
    use DatabaseTransactions;

    private function dokumenBerlaku(User $gl): Document
    {
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji Strip Aksi'
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    public function test_pemilik_dokumen_mendapat_strip_aksi(): void
    {
        $gl = $this->aktorGl();
        $this->dokumenBerlaku($gl);

        // Disaring ke satu judul: basis data pengembangan berisi dokumen lain
        // yang mungkin memberi peran ini aksi dari jalur berbeda, dan itu akan
        // membuat test ini lulus/gagal karena sebab yang bukan yang diuji.
        $this->assertTrue($this->adaAksi($gl), 'pemilik dokumen mendapat isi strip aksi');
    }

    /**
     * Ada aksi yang akan mengisi strip untuk baris "SOP Uji Strip Aksi"?
     *
     * Kelima `boleh_*` inilah SATU-SATUNYA sumber isi menunya
     * (`pages/Documents/Published.tsx`), jadi menghitungnya di sini setara
     * dengan menghitung item yang tergambar — tanpa perlu merender React.
     */
    private function adaAksi(User $sebagai): bool
    {
        $baris = collect($this->propsInertia(
            $this->actingAs($sebagai)
                ->get(route('documents.published', ['q' => 'SOP Uji Strip Aksi']))->assertOk()
        )['documents']['data'])->firstWhere('judul', 'SOP Uji Strip Aksi');

        $this->assertNotNull($baris, 'dokumennya harus terlihat oleh peran ini');

        return (bool) collect([
            'boleh_edit_arsip', 'boleh_beri_masukan', 'boleh_revisi',
            'boleh_batal_revisi', 'boleh_musnahkan',
        ])->first(fn (string $k) => $baris[$k]);
    }

    public function test_pembaca_tanpa_aksi_tak_mendapat_tombol_kosong(): void
    {
        $gl = $this->aktorGl();
        $this->dokumenBerlaku($gl);
        $sh = $this->aktorSh();

        $this->assertFalse($this->adaAksi($sh), 'pembaca tanpa aksi tak mendapat tombol kosong');
    }
}
