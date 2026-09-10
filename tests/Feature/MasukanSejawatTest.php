<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\MasukanSejawat;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Masukan sejawat antar-GL (PLAN-AKSES-v8 Fase 2).
 *
 * Yang dikunci di sini adalah BATAS-nya, sebab kanal ini duduk persis di sebelah
 * dua kanal lain yang mudah tertukar:
 *   • Masukan LAPANGAN — milik Non-Staff, atas dokumen BERLAKU. Tak boleh ikut
 *     bertambah, dan aturannya tak boleh bergeser (gate `beri-masukan` utuh).
 *   • PENINJAUAN resmi — milik SH/DH. Masukan sejawat tak boleh menyentuh status
 *     dokumen sama sekali; menaikkannya jadi `in_review` akan mencabut hak Tarik
 *     milik pembuatnya, padahal yang terjadi cuma rekan membaca.
 *
 * SELURUH aktor & departemennya lahir di dalam transaksi (PLAN-AKSES-v8 §3
 * jalan B): basis data uji kehilangan sebagian akun contoh seeder, dan
 * departemen nyata sudah memuat dokumen orang lain.
 */
class MasukanSejawatTest extends TestCase
{
    use DatabaseTransactions;

    private function dept(): Department
    {
        $tanda = Str::upper(Str::random(6));

        return Department::create(['code' => 'UJM'.$tanda, 'name' => 'Departemen Uji '.$tanda]);
    }

    private function orang(string $role, ?Department $dept = null): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'MSJ-'.$tanda,
            'jabatan' => $role === Roles::ROLE_ADMIN ? User::JABATAN_STAFF : $role,
            'department_id' => $dept?->id,
            'password' => Hash::make('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function draft(User $pembuat, Department $dept): Document
    {
        return app(DocumentService::class)->createDraft(
            $pembuat, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, 'SOP Uji Masukan '.Str::random(6)
        );
    }

    /** Kiriman formulir yang sah: satu ringkasan + satu catatan berbagian. */
    private function kiriman(): array
    {
        return [
            'summary' => 'Alur di bagian tujuan perlu dipertegas.',
            'annotations' => [
                'tujuan' => [0 => 'Sebutkan sasaran keselamatannya, bukan hanya kegiatannya.'],
                // Kolom kosong memang IKUT terkirim oleh formulir tinjauan —
                // satu textarea per item. Ia wajib dibuang, bukan disimpan.
                'ruang_lingkup' => [0 => '', 1 => '   '],
            ],
        ];
    }

    public function test_gl_memberi_masukan_atas_dokumen_rekan_sedepartemen(): void
    {
        Notification::fake();

        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($glA, $dept);

        /*
        | Layar pemberi masukan: layar TINJAU yang sama, tanpa panel AI dan
        | tanpa tombol keputusan. Sejak Fase 10 ketiganya dikendalikan props,
        | bukan markup — `aiUrl` null berarti `PanelAi` tak dirender sama
        | sekali, dan `tanpaKeputusan` menukar dua tombol keputusan dengan
        | Batal & Kirim (`TombolKeputusan` di `Review/Show.tsx`).
        |
        | `anotasiLama` KOSONG juga diperiksa di sini: catatan peninjau resmi
        | adalah percakapannya dengan pembuat, bukan urusan rekan sejawat.
        */
        $this->actingAs($glB)->get(route('masukan-sejawat.create', $doc))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Review/Show')
                ->missing('aiUrl')
                ->missing('alihKandidat')
                ->where('tanpaKeputusan', true)
                ->where('pakaiVerdict', false)
                ->where('anotasiLama', [])
                ->where('formAction', route('masukan-sejawat.store', $doc))
            );

        // MEMBACA dokumen rekan tak boleh menggeser statusnya — di situlah
        // hak Tarik milik pembuatnya bergantung.
        $this->assertSame('draft', $doc->fresh()->status);

        $this->actingAs($glB)->post(route('masukan-sejawat.store', $doc), $this->kiriman())
            ->assertRedirect(route('documents.staffStatus'));

        $this->assertSame('draft', $doc->fresh()->status);

        $masukan = MasukanSejawat::where('document_id', $doc->id)->sole();
        $this->assertSame($glB->id, $masukan->user_id);
        // Kolom kosong dibuang; hanya catatan yang benar-benar berisi tersimpan.
        $this->assertCount(1, $masukan->catatan_json);
        $this->assertSame('tujuan', $masukan->catatan_json[0]['section_key']);

        // Kanal lapangan Non-Staff tak ikut bertambah sedikit pun.
        $this->assertSame(0, $doc->feedback()->count());
    }

    public function test_pembuat_melihat_lencana_lalu_membaca_isinya(): void
    {
        Notification::fake();

        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($glA, $dept);

        $this->actingAs($glB)->post(route('masukan-sejawat.store', $doc), $this->kiriman());

        // Lencana di "Dokumen Saya". Dulu diperiksa lewat URL-nya di markup;
        // sejak Fase 8 tautannya dirangkai Ziggy di klien, jadi yang diperiksa
        // angka yang menentukan lencananya muncul — `null` berarti lencananya
        // memang tak ditawarkan kepada peran ini.
        $baris = collect($this->propsInertia(
            $this->actingAs($glA)->get(route('documents.index'))->assertOk()
        )['documents']['data'])->firstWhere('id', $doc->id);

        $this->assertSame(1, $baris['masukan_sejawat']);

        // Isinya terbaca dengan NAMA BAGIAN dari schema, bukan `section_key`.
        $this->actingAs($glA)->get(route('masukan-sejawat.show', $doc))
            ->assertOk()
            ->assertSee('I. TUJUAN')
            ->assertDontSee('>tujuan<', false)
            ->assertSee('Sebutkan sasaran keselamatannya, bukan hanya kegiatannya.')
            ->assertSee('Alur di bagian tujuan perlu dipertegas.');

        // Dibaca oleh PEMILIKNYA → penanda terisi.
        $this->assertNotNull(MasukanSejawat::where('document_id', $doc->id)->sole()->dibaca_at);
    }

    public function test_pemberi_masukan_boleh_membaca_ulang_kiriman_sendiri(): void
    {
        Notification::fake();

        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($glA, $dept);

        $this->actingAs($glB)->post(route('masukan-sejawat.store', $doc), $this->kiriman());

        $this->actingAs($glB)->get(route('masukan-sejawat.show', $doc))->assertOk();

        // Ia BUKAN pemilik dokumen — membukanya tak boleh menghabiskan sorotan
        // "baru" milik pembuatnya.
        $this->assertNull(MasukanSejawat::where('document_id', $doc->id)->sole()->dibaca_at);
    }

    public function test_pembuat_tidak_bisa_memberi_masukan_atas_dokumennya_sendiri(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($gl, $dept);

        $this->actingAs($gl)->get(route('masukan-sejawat.create', $doc))->assertForbidden();
        $this->actingAs($gl)->post(route('masukan-sejawat.store', $doc), $this->kiriman())->assertForbidden();
    }

    public function test_gl_departemen_lain_ditolak(): void
    {
        $deptA = $this->dept();
        $deptB = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $deptA);
        $glLain = $this->orang(User::JABATAN_GROUP_LEADER, $deptB);
        $doc = $this->draft($glA, $deptA);

        $this->actingAs($glLain)->get(route('masukan-sejawat.create', $doc))->assertForbidden();
        $this->actingAs($glLain)->post(route('masukan-sejawat.store', $doc), $this->kiriman())->assertForbidden();
    }

    public function test_dokumen_berlaku_ditolak_karena_itu_jalur_masukan_lapangan(): void
    {
        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);

        $doc = $this->draft($glA, $dept);
        $doc->update(['status' => 'published']);

        $this->actingAs($glB)->get(route('masukan-sejawat.create', $doc))->assertForbidden();

        // Obsolete pun tertutup: tak ada lagi yang bisa diperbaiki.
        $doc->update(['status' => 'obsolete']);
        $this->actingAs($glB)->get(route('masukan-sejawat.create', $doc))->assertForbidden();
    }

    public function test_non_staff_dan_section_head_tidak_lewat_kanal_ini(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $nonStaff = $this->orang(User::JABATAN_STAFF, $dept);
        $sh = $this->orang(User::JABATAN_SECTION_HEAD, $dept);
        $doc = $this->draft($gl, $dept);

        // Non-Staff: kanalnya Masukan Lapangan, dan hanya atas dokumen Berlaku.
        $this->actingAs($nonStaff)->get(route('masukan-sejawat.create', $doc))->assertForbidden();

        // SH/DH: ia PENINJAU dokumen ini; kanal kedua hanya akan menyaingi
        // jalur tinjauan resminya.
        $this->actingAs($sh)->get(route('masukan-sejawat.create', $doc))->assertForbidden();
    }

    public function test_kiriman_kosong_dipantulkan_tanpa_menulis_apa_pun(): void
    {
        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($glA, $dept);

        $this->actingAs($glB)->post(route('masukan-sejawat.store', $doc), [
            'summary' => '',
            'annotations' => ['tujuan' => [0 => '']],
        ])->assertRedirect();

        $this->assertSame(0, MasukanSejawat::where('document_id', $doc->id)->count());
    }

    public function test_orang_luar_tidak_bisa_membaca_halaman_masukan(): void
    {
        Notification::fake();

        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glC = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($glA, $dept);

        $this->actingAs($glB)->post(route('masukan-sejawat.store', $doc), $this->kiriman());

        // GL-C sedepartemen, tapi bukan penyusun & bukan pemberi masukan:
        // catatan ini percakapan antara pemberi & pembuatnya.
        $this->actingAs($glC)->get(route('masukan-sejawat.show', $doc))->assertForbidden();
    }
}
