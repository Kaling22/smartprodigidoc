<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris lonceng untuk aplikasi mobile.
 *
 * Sumbernya kolom `data` milik tabel `notifications` — JSON bebas bentuk yang
 * ditulis DocumentNotification::toArray(). Karena barisnya PERMANEN, tabel itu
 * masih memuat notifikasi dari versi lama yang bentuknya belum tentu sama;
 * karena itu setiap kunci diambil dengan `?? null`, jangan diasumsikan ada.
 * Satu notifikasi lama tanpa `doc_number` tak boleh membuat seluruh daftar
 * lonceng di HP gagal dimuat.
 *
 * @mixin \Illuminate\Notifications\DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->data['title'] ?? null,
            'pesan' => $this->data['message'] ?? null,
            'ikon' => $this->data['icon'] ?? 'bi-bell',

            // Nama rute WEB. Mobile tak bisa membukanya begitu saja — ia
            // memetakan sendiri nama ini ke layarnya, dan `document_id` di
            // bawah adalah yang benar-benar dipakai untuk berpindah.
            'route' => $this->data['route'] ?? null,
            'document_id' => $this->data['document_id'] ?? null,
            'no_dokumen' => $this->data['doc_number'] ?? null,

            'dibaca' => $this->read_at !== null,
            'tanggal' => $this->created_at?->toIso8601String(),
        ];
    }
}
