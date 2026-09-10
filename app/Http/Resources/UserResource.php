<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk data user yang dikirim ke aplikasi mobile.
 *
 * Kunci `nama`, `departemen`, dan `role` memakai bahasa Indonesia karena itulah
 * yang sudah dibaca aplikasi mobile (`lib/auth_service.dart`). Model kita
 * memakai `name` dan relasi `department`, jadi pemetaan ulang dilakukan DI SINI
 * — jangan pernah mengirim model mentah, sebab itu ikut membocorkan kolom
 * seperti `password` dan `remember_token`.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nrp' => $this->nrp,
            'nama' => $this->name,

            // Kode departemen versi SmartPro (mis. ICTMD, FAW-SCM). Aplikasi
            // mobile lama memakai kosakata sendiri (COE = ICTMD,
            // FALOG = FAW-SCM); penyelarasannya dikerjakan di sisi mobile.
            'departemen' => $this->department?->code,
            'departemen_nama' => $this->department?->name,

            // Jabatan struktural — penentu alur persetujuan (PRD v2 §2.1).
            'role' => $this->jabatan,
            'role_label' => $this->jabatanLabel(),

            /*
            | ABSOLUT, berbeda dari sisi web yang memakai URL relatif.
            |
            | `photoUrl()` mengembalikan `/storage/avatars/x.jpg` — di peramban
            | itu benar (ikut host yang sedang dibuka), tapi di HP `NetworkImage`
            | menerimanya sebagai URL tak sah dan avatar TAK PERNAH tampil,
            | tanpa satu pun pesan galat. `url()` menempelkan host permintaan
            | yang sedang berjalan, jadi ia ikut pindah sendiri saat aplikasi
            | diarahkan dari IP lokal ke domain hosting.
            */
            'foto' => $this->photo_path ? url($this->photoUrl()) : null,

            // Dua-duanya DIBACA layar "Informasi Akun" di HP (Pengguna.noHp /
            // .email). Selama tak dikirim, kotaknya selalu kosong dan menyimpan
            // perubahan justru menghapus data yang sudah ada.
            'no_hp' => $this->nomor_hp,
            'email' => $this->email,

            /*
            | KAPABILITAS — bukan izin mentah, melainkan jawaban atas pertanyaan
            | yang benar-benar ditanyakan aplikasi: "menu apa yang saya pasang?"
            |
            | Tanpa ini aplikasi hanya tahu `jabatan`, dan harus menebak sendiri
            | siapa boleh meninjau — tebakan yang akan basi begitu matriks peran
            | berubah. Server yang menjawab, aplikasi yang menurut.
            |
            | Menambah kunci aman bagi kontrak lama: AuthApiTest memeriksa
            | struktur sebagai SUBSET, bukan persamaan.
            */
            'bisa_tinjau' => $this->can('document.review') || $this->canReviewJsa(),
            'bisa_tinjau_jsa' => $this->canReviewJsa(),
            'bisa_beri_masukan' => $this->jabatan === \App\Models\User::JABATAN_STAFF,

            // MELAKSANAKAN pekerjaan JSA — Non-Staff & GL. SH/DH & PJO tetap
            // MELIHAT daftarnya (lingkupTim), jadi ini menentukan tombol
            // "Mulai Pekerjaan", bukan menu Riwayat Pekerjaan.
            'bisa_kerja_jsa' => $this->bisaKerjaJsa(),

            // Mencatat cuti / off day / dinas luar — kebalikannya: semua KECUALI
            // Non-Staff.
            'bisa_ketersediaan' => $this->bisaCatatKetersediaan(),

            /*
            | Menu KENDALI. Empat pertanyaan terpisah karena lingkupnya memang
            | berbeda: GL melihat perjalanan & distribusi dokumen yang IA SUSUN,
            | SH/DH sedepartemen, PJO/Admin tujuh departemen — dan daftar induk
            | hanya untuk pemegangnya (bukan MD, yang meninjau penulisan).
            */
            'bisa_perjalanan' => $this->bisaKendaliDokumen(),
            'bisa_status_dokumen' => $this->bisaKendaliDokumen(),
            // Sama dengan penjaga halaman webnya, DITAMBAH GL: di HP ia punya
            // layar "dokumen yang saya susun" (KendaliApiController::
            // lingkupDistribusi) yang tak ada padanan webnya.
            'bisa_distribusi' => $this->bisaLihatDistribusi()
                || $this->jabatan === \App\Models\User::JABATAN_GROUP_LEADER,
            'bisa_daftar_induk' => $this->can('document.publish') || $this->can('document.review'),

            // Arsip "Tidak Berlaku". Sampai butir 15 bendera ini TIDAK dikirim
            // dan aplikasi menyimpulkannya sendiri dari `role != 'staff'` —
            // tebakan yang kebetulan masih benar hari ini, tapi akan diam-diam
            // menyimpang begitu User::bisaLihatArsip() berubah. Server yang
            // menjawab, aplikasi yang menurut.
            'bisa_lihat_arsip' => $this->bisaLihatArsip(),

            // Tujuh departemen, atau terkunci di departemen sendiri. Layar
            // memakainya untuk memasang/menyembunyikan pemilih departemen.
            'lintas_departemen' => $this->can('document.view_all'),
        ];
    }

    /** GL, SH/DH, MD, PJO & Admin — mereka yang punya menu Kendali sama sekali. */
    private function bisaKendaliDokumen(): bool
    {
        return $this->can('document.view_all') || in_array($this->jabatan, [
            \App\Models\User::JABATAN_GROUP_LEADER,
            \App\Models\User::JABATAN_SECTION_HEAD,
            \App\Models\User::JABATAN_DEPARTEMEN_HEAD,
        ], true);
    }
}
