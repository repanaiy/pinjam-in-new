<?php

return [
    /*
    | Admin awal untuk produksi (php artisan db:seed --class=ProductionSeeder).
    | Isi lewat .env; kata sandi wajib diisi dan minimal 12 karakter.
    */
    'initial_admin' => [
        'name' => env('INITIAL_ADMIN_NAME', 'Administrator Sarpras'),
        'username' => env('INITIAL_ADMIN_USERNAME', 'admin'),
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],
];
