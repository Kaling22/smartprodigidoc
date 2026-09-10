<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Support\Collection;

/**
 * Kandidat PENINJAU & PENYETUJU sesuai matriks docs/aturan-alur-v2.md.
 *
 * Terpusat di satu tempat agar mudah diubah: SOP/IK/SP memakai dept sendiri;
 * JSA lintas-departemen — peninjaunya ditentukan WEWENANG orangnya
 * (User::canReviewJsa: SHE otomatis + profil akses), bukan departemennya.
 */
class DocumentParticipantResolver
{
    private const HEAD_ROLES = [Roles::ROLE_SECTION_HEAD, Roles::ROLE_DEPARTEMEN_HEAD];

    /**
     * Semua orang yang BERWENANG meninjau JSA.
     *
     * Fase 4 mengganti pertanyaannya: dulu "departemen apa" (SHE, sebelumnya
     * SHE+PLANT ditulis mentah), sekarang "siapa yang berwenang" — SHE
     * otomatis, sisanya lewat profil akses. Sumber jawabannya tunggal:
     * User::canReviewJsa(). Kalau daftar ini punya aturannya sendiri, suatu
     * hari papan pemilihan akan menawarkan orang yang 403 saat membukanya.
     *
     * Disaring di PHP, bukan di SQL: aturannya menggabungkan izin spatie,
     * kode departemen, dan kolom profil. `with()` membuatnya nol query per
     * orang, dan jumlah GL memang sekelas puluhan.
     */
    private function peninjauJsa(): Collection
    {
        return User::with(['department', 'accessProfile'])->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->where('name', Roles::ROLE_GROUP_LEADER))
            ->get()
            ->filter->canReviewJsa();
    }

    /**
     * SH/DH pada departemen tertentu.
     *
     * Publik sejak butir 0: pendaftaran dokumen LAMA memberi kabar ke SH/DH
     * departemennya (dokumen itu melewati alur tinjau–setuju, jadi atasannya
     * berhak tahu). Daftarnya diminta ke sini alih-alih di-query ulang di
     * controller — "siapa SH/DH departemen X" hanya boleh punya satu jawaban.
     */
    public function heads(array $deptIds): Collection
    {
        return User::with('department')->where('status', 'active')
            ->whereIn('department_id', array_values(array_filter($deptIds)))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::HEAD_ROLES))
            ->get();
    }

    /**
     * Pimpinan (PJO).
     *
     * Publik sejak Fase F: tahap TERAKHIR nonaktif berjenjang perlu tahu siapa
     * yang harus dikabari, dan "siapa PJO" hanya boleh punya satu jawaban.
     */
    public function pjo(): Collection
    {
        return User::with('department')->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->where('name', Roles::ROLE_PIMPINAN))
            ->get();
    }

    private function sortUnique(Collection $c): Collection
    {
        return $c->unique('id')->sortBy('name')->values();
    }

    /**
     * Kandidat PENINJAU. PJO TIDAK PERNAH meninjau — dia hanya penyetuju.
     */
    public function reviewerCandidates(Document $document): Collection
    {
        $dept = $document->department_id;

        // JSA (v7 Fase 3–4) — peninjauannya dipegang GL yang BERWENANG, bukan
        // GL dept pembuat. SHE otomatis; GL departemen lain (termasuk Plant,
        // yang dicabut di Fase 3) mendapatkannya lewat profil akses.
        //
        // Yang DIKECUALIKAN adalah ORANGNYA, bukan departemennya (keputusan
        // pemilik, sesi Fase 3). Bedanya nyata hanya pada JSA milik SHE
        // sendiri: dulu SELURUH GL SHE dibuang sehingga JSA buatan SHE tak
        // punya satu pun peninjau GL; sekarang GL SHE yang LAIN boleh
        // meninjaunya — konflik kepentingan itu melekat pada penyusunnya,
        // bukan pada papan nama departemennya, dan SHE memang punya lebih dari
        // satu GL. Untuk enam departemen lain aturan ini tak mengubah apa pun:
        // penyusunnya GL departemen itu, tak pernah GL SHE.
        //
        // Yang dibuang = SELURUH penyusun (pembuat utama DAN pembuat
        // tambahan). Pembuat tambahan sengaja ikut: ia GL/Non-Staff
        // sedepartemen yang namanya tercetak di baris "Dibuat Oleh", jadi
        // membiarkannya meninjau sama saja dengan menilai tulisannya sendiri.
        //
        // SH/DH dept pembuat dibuka sebagai jalur cadangan bila departemen itu
        // SENDIRI memasok peninjau JSA — bila tidak, satu-satunya GL berwenang
        // di sana bisa jadi penyusunnya, dan dokumennya tak punya peninjau
        // sama sekali. Dulu syaratnya "dept-nya SHE"; sekarang mengikuti profil
        // akses, jadi dept mana pun yang GL-nya diberi wewenang ikut terbuka.
        if ($document->type->code === 'JSA') {
            $penyusun = $document->authors()->pluck('user_id')
                ->push($document->created_by)->filter()->unique()->all();

            $berwenang = $this->peninjauJsa();
            $gl = $berwenang->reject(fn ($u) => in_array($u->id, $penyusun, true));
            $heads = $berwenang->contains(fn ($u) => $u->department_id === $dept)
                ? $this->heads([$dept]) : collect();

            return $this->sortUnique($gl->merge($heads));
        }

        // SOP / IK / SP → SH/DH dept sendiri.
        return $this->sortUnique($this->heads([$dept]));
    }

    /** Kandidat PENYETUJU. PJO selalu boleh menyetujui dokumen apa pun. */
    public function approverCandidates(Document $document): Collection
    {
        $code = $document->type->code;

        // SOP → PJO saja.
        if ($code === 'SOP') {
            return $this->sortUnique($this->pjo());
        }

        // JSA (v3 rev) → SH/DH departemen PEMBUAT, atau PJO. Peninjauannya
        // memang diserahkan ke SHE, tapi pengesahannya tetap tanggung jawab
        // atasan di departemen yang membuat pekerjaan itu.
        if ($code === 'JSA') {
            return $this->sortUnique($this->heads([$document->department_id])->merge($this->pjo()));
        }

        // IK / SP → SH/DH dept sendiri SAJA (satu orang meninjau sekaligus
        // menyetujui; PJO tidak terlibat) — referensi PPA-ADRO-SP/IK.
        return $this->sortUnique($this->heads([$document->department_id]));
    }

    /** Validasi: apakah $userId kandidat sah sebagai peninjau/penyetuju? */
    public function isValidReviewer(Document $document, ?int $userId): bool
    {
        return $userId !== null && $this->reviewerCandidates($document)->contains('id', $userId);
    }

    public function isValidApprover(Document $document, ?int $userId): bool
    {
        return $userId !== null && $this->approverCandidates($document)->contains('id', $userId);
    }
}
