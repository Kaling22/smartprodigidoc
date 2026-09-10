<?php

namespace App\Http\Requests;

/**
 * Ubah peran akun yang sudah ada.
 *
 * Aturan `role` & `department_id` sengaja DIWARISI dari StoreUserRequest: kalau
 * disalin, suatu saat "Pimpinan tanpa departemen" akan berlaku saat membuat tapi
 * tidak saat mengubah — dan akun PJO bisa berakhir terikat satu departemen,
 * padahal ia justru harus lintas 7 departemen.
 *
 * NRP & password TIDAK ikut: NRP identitas login yang dipakai audit log, dan
 * penggantian sandi punya jalurnya sendiri.
 */
class UpdateUserRoleRequest extends StoreUserRequest
{
    /**
     * Larangan mengubah peran sendiri ditegakkan DI SINI, bukan di controller.
     *
     * authorize() berjalan SEBELUM rules(); saat penjaga ini masih berupa
     * abort_if di controller, kiriman yang kebetulan tak lolos validasi
     * (mis. peran non-Pimpinan tanpa departemen) pulang sebagai 302 berisi
     * pesan validasi — bukan 403. Penolakannya jadi bergantung pada bentuk
     * isian, padahal ia sama sekali tak boleh bergantung pada apa pun.
     */
    public function authorize(): bool
    {
        return parent::authorize()
            && $this->user()->id !== $this->route('user')->id;
    }

    public function rules(): array
    {
        $dasar = parent::rules();

        return [
            'role' => $dasar['role'],
            'department_id' => $dasar['department_id'],
        ];
    }
}
