<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lonceng versi API — padanan NotificationController milik web.
 *
 * KENAPA controller terpisah, bukan memakai ulang yang web: kedua method di
 * sana (`open`, `readAll`) mengembalikan REDIRECT. Aplikasi mobile tak bisa
 * mengikuti redirect ke halaman Blade; ia butuh JSON dan menentukan sendiri
 * layar mana yang dibuka. Yang dipakai bersama adalah TABEL-nya
 * (`notifications`), jadi menandai dibaca dari HP ikut mengurangi angka
 * lonceng di web dan sebaliknya — memang itu yang diharapkan.
 *
 * Otorisasi cukup lewat relasi `$request->user()->notifications()`: ia sendiri
 * sudah terpagar `notifiable_id`, jadi `findOrFail` di sana mustahil menyentuh
 * notifikasi milik orang lain. Tak perlu Policy tambahan.
 *
 * ponytail: mobile menarik unread-count secara berkala; pindah ke FCM bila
 * latensi notifikasi jadi keluhan nyata (butuh tabel device_tokens +
 * kredensial Firebase — fase tersendiri, jangan diselundupkan ke sini).
 */
class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $request->boolean('belum_dibaca')
            ? $user->unreadNotifications()
            : $user->notifications();

        $halaman = $query->paginate(20);

        return response()->json([
            'data' => NotificationResource::collection($halaman->items())->resolve(),
            'meta' => [
                'belum_dibaca' => $user->unreadNotifications()->count(),
                'halaman' => $halaman->currentPage(),
                'halaman_terakhir' => $halaman->lastPage(),
                'total' => $halaman->total(),
            ],
        ]);
    }

    /** Endpoint murah khusus polling — sengaja tak memuat satu baris pun. */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'belum_dibaca' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Padanan `open()` versi API: tandai dibaca, lalu KEMBALIKAN tujuannya
     * sebagai data. Navigasi urusan mobile — jangan redirect.
     */
    public function read(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'data' => (new NotificationResource($notification->refresh()))->resolve(),
            'meta' => ['belum_dibaca' => $user->unreadNotifications()->count()],
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['belum_dibaca' => 0]);
    }
}
