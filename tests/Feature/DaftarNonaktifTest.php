<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Daftar "Dokumen Tidak Berlaku" berkelompok (PLAN-REVISI-v6 Fase E).
 *
 * Sebelum ini daftarnya DATAR: tiap revisi Tipe B menambah satu baris, jadi
 * satu dokumen berusia 5 revisi memakan 5 baris dan mendorong dokumen lain
 * keluar halaman. Kunci pengelompokannya `doc_number` — ApprovalController
 * sengaja mewariskan nomor yang sama ke versi baru.
 *
 * Yang dikunci di sini adalah hal-hal yang rusak TANPA GEJALA di layar:
 * induk yang salah versi (tampak wajar, isinya usang), grup yang bocor antar
 * dokumen, dan paginasi yang diam-diam kembali menghitung BARIS.
 */
class DaftarNonaktifTest extends TestCase
{
    use DatabaseTransactions;

    /** Departemen baru tiap test — daftar ini berjalan di atas DB pengembangan. */
    private function dept(): Department
    {
        return Department::create([
            'code' => 'NON'.Str::upper(Str::random(3)),
            'name' => 'Departemen Uji Nonaktif',
        ]);
    }

    /** Satu versi obsolete. `doc_number` sama = satu dokumen yang sama. */
    private function versi(Department $dept, string $nomor, int $edisi, int $revisi): Document
    {
        return Document::create([
            'doc_number' => $nomor,
            'doc_number_final' => $nomor,
            'document_type_id' => DocumentType::where('code', 'SOP')->firstOrFail()->id,
            'department_id' => $dept->id,
            'title' => "Prosedur {$nomor} Ed{$edisi} Rev{$revisi}",
            'status' => 'obsolete',
            'edisi' => (string) $edisi,
            'no_revisi' => $revisi,
            'created_by' => $this->aktorGl()->id,
        ]);
    }

    /** Admin melihat semua departemen, jadi filternya yang menyempitkan. */
    private function lihat(Department $dept): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs(User::where('nrp', 'ADM-0001')->firstOrFail())
            ->get(route('documents.obsolete', ['department_id' => $dept->id]));
    }

    public function test_tiga_versi_jadi_satu_baris_induk_versi_tertinggi(): void
    {
        $dept = $this->dept();
        $nomor = 'PPA-ADRO-SOP-'.$dept->code.'-01';
        // Sengaja TIDAK berurutan naik: bila kelak induk dipilih dari baris
        // terakhir yang kebetulan lewat, urutan ini yang menangkapnya.
        $this->versi($dept, $nomor, 1, 0);
        $this->versi($dept, $nomor, 1, 3);
        $terbaru = $this->versi($dept, $nomor, 2, 0);

        $halaman = $this->lihat($dept);
        $halaman->assertOk();

        $documents = $this->propsInertia($halaman)['documents']['data'];
        $this->assertCount(1, $documents, 'Tiga versi harus menyusut jadi satu baris induk.');
        $this->assertSame($terbaru->id, $documents[0]['id'], 'Induk harus versi TERTINGGI (Ed 2 Rev 0).');
        // Dulu `assertSee('3 versi')`: teksnya kini dirangkai TSX dari panjang
        // `versi`, jadi yang diperiksa angkanya sendiri.
        $this->assertCount(3, $documents[0]['versi']);
    }

    public function test_dropdown_memuat_seluruh_versi_terurut_menurun(): void
    {
        $dept = $this->dept();
        $nomor = 'PPA-ADRO-SOP-'.$dept->code.'-01';
        foreach ([[1, 0], [2, 0], [1, 3]] as [$edisi, $revisi]) {
            $this->versi($dept, $nomor, $edisi, $revisi);
        }

        // Versi kini menempel pada barisnya sendiri (props `documents.data[].versi`),
        // bukan peta terpisah `versi` — bentuk yang sama dipakai baris induk dan
        // baris versi, jadi tombolnya mustahil berbeda.
        $baris = $this->propsInertia($this->lihat($dept))['documents']['data'][0];

        $this->assertSame($nomor, $baris['nomor']);
        $this->assertSame(
            [['2', 0], ['1', 3], ['1', 0]],
            array_map(fn (array $v) => [$v['edisi'], $v['no_revisi']], $baris['versi']),
            'Urutan di dalam dropdown selalu Edisi menurun, lalu revisi menurun.'
        );
    }

    public function test_dokumen_satu_versi_tanpa_chevron(): void
    {
        $dept = $this->dept();
        $this->versi($dept, 'PPA-ADRO-SOP-'.$dept->code.'-01', 1, 0);

        // Penyingkapnya hanya digambar bila grupnya berisi LEBIH dari satu versi
        // — dulu diperiksa lewat ikon chevron di markup, kini lewat angka yang
        // menentukannya.
        $baris = $this->propsInertia($this->lihat($dept))['documents']['data'][0];

        $this->assertCount(1, $baris['versi']);
    }

    public function test_paginasi_menghitung_grup_bukan_baris(): void
    {
        $dept = $this->dept();
        // 16 dokumen × 2 versi = 32 baris. Paginasi per BARIS akan memuat
        // 15 baris (≤ 8 dokumen) di halaman pertama; per GRUP memuat 15 dokumen.
        for ($i = 1; $i <= 16; $i++) {
            $nomor = sprintf('PPA-ADRO-SOP-%s-%02d', $dept->code, $i);
            $this->versi($dept, $nomor, 1, 0);
            $this->versi($dept, $nomor, 1, 1);
        }

        $documents = $this->propsInertia($this->lihat($dept))['documents'];

        $this->assertCount(15, $documents['data']);
        $this->assertSame(16, $documents['total']);
    }

    public function test_dua_nomor_berbeda_tak_pernah_tercampur(): void
    {
        $dept = $this->dept();
        $satu = 'PPA-ADRO-SOP-'.$dept->code.'-01';
        $dua = 'PPA-ADRO-SOP-'.$dept->code.'-02';
        $this->versi($dept, $satu, 1, 0);
        $this->versi($dept, $satu, 1, 1);
        $this->versi($dept, $dua, 1, 0);

        $documents = collect($this->propsInertia($this->lihat($dept))['documents']['data'])
            ->keyBy('nomor');

        $this->assertCount(2, $documents);
        $this->assertCount(2, $documents[$satu]['versi']);
        $this->assertCount(1, $documents[$dua]['versi']);
    }
}
