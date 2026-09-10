<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        // Berkas privat (tak pernah disajikan ke publik). `serve` DIMATIKAN:
        // saat menyala, Laravel mendaftarkan route `GET storage/{path}` yang
        // menunjuk folder privat ini dan MENIMPA route penyaji foto di
        // routes/web.php (route dengan URI sama saling menggantikan). Tak ada
        // satu pun kode yang memanggil Storage::url() pada disk ini, jadi
        // mematikannya tidak menghilangkan apa pun.
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // Foto profil & lampiran. Dua setelan di bawah ini sengaja BEDA dari
        // bawaan Laravel, keduanya karena hosting:
        //   - `url` RELATIF: URL foto ikut host yang sedang dibuka, bukan
        //     APP_URL. Bawaan Laravel menempel APP_URL, jadi begitu APP_URL
        //     tertinggal nilai lokal (atau http sementara situsnya https),
        //     SEMUA foto mati padahal filenya ada.
        //   - `throw` NYALA: kalau storage/app/public tidak writable di server,
        //     `store()` mengembalikan false tanpa suara — `photo_path` terisi
        //     false, tersimpan '0', dan user tetap melihat "berhasil disimpan".
        //     Lebih baik meledak dan tercatat di log daripada hilang diam-diam.
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
