<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk masukan lapangan yang dikirim ke aplikasi mobile.
 *
 * Kuncinya Indonesia dan seragam dengan DocumentResource (`judul`,
 * `no_dokumen`) — layar masukan di mobile memang ditulis ulang untuk SmartPro,
 * jadi tak ada alias kontrak lama yang perlu dipertahankan di sini.
 *
 * @mixin \App\Models\DocumentFeedback
 */
class FeedbackResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $saya = $request->user();

        return [
            'id' => $this->id,
            'nomor' => $this->feedback_number,
            'judul' => $this->document?->title,
            'no_dokumen' => $this->document?->displayNumber(),
            'isi' => $this->isi,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'balasan' => $this->balasan,
            'tanggal' => $this->created_at?->toDateTimeString(),

            /*
            | KOTAK MASUK (PLAN-MOBILE-v6 §2.2). Kunci-kunci di bawah tak dibaca
            | mode "Masukan Saya" — di sana pengirimnya ya pembacanya sendiri —
            | tapi tetap dikirim apa adanya: satu bentuk untuk satu rute lebih
            | mudah dipercaya daripada dua bentuk yang berubah menurut peran.
            |
            | Menambah kunci aman bagi kontrak lama: test kontrak memeriksa
            | struktur sebagai SUBSET, bukan persamaan.
            */
            'pengirim' => [
                'id' => $this->user?->id,
                'nama' => $this->user?->name,
                'nrp' => $this->user?->nrp,
                'departemen' => $this->user?->department?->code,
            ],
            'departemen' => $this->document?->department?->code,

            // Pratinjau dokumen dari kotak masuk: HP menyusun URL berkasnya
            // `{publicUrl}/{jenis}/{berkas}` — bentuk yang SAMA dengan
            // DocumentResource, supaya layar PDF-nya tak perlu tahu ia dibuka
            // dari mana.
            'dokumen_id' => $this->document?->id,
            'jenis' => $this->document?->type?->code,
            'berkas' => $this->document ? $this->document->id.'.pdf' : null,

            // Tombol apa yang digambar. Dijawab SERVER memakai aturan yang sama
            // persis dengan penjaga rutenya — tombol yang tampil lalu berakhir
            // 403 adalah gejala aturan yang disalin.
            'dapat_dibalas' => $saya ? $this->bisaDibalasOleh($saya) : false,
            'dapat_diadopsi' => $saya ? $this->bisaDiadopsiOleh($saya) : false,

            'dibalas_oleh' => $this->replier?->name,
        ];
    }
}
