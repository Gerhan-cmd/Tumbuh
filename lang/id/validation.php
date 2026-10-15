<?php

/*
 * Pesan validasi bahasa Indonesia untuk aturan yang dipakai di Tumbuh dan di halaman login/daftar.
 * Aturan lain yang tidak tercantum di sini otomatis memakai pesan bahasa Inggris bawaan Laravel.
 */
return [
    'boolean' => ':attribute harus bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'lowercase' => ':attribute harus huruf kecil.',
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah dipakai.',
    'between' => [
        'numeric' => ':attribute harus di antara :min dan :max.',
        'string' => ':attribute harus di antara :min dan :max karakter.',
    ],
    'max' => [
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'min' => [
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'size' => [
        'string' => ':attribute harus :size karakter.',
    ],
    'password' => [
        'letters' => ':attribute harus mengandung huruf.',
        'mixed' => ':attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => ':attribute harus mengandung angka.',
        'symbols' => ':attribute harus mengandung simbol.',
        'uncompromised' => ':attribute ini pernah bocor di internet. Pilih kata sandi lain.',
    ],

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'current_password' => 'kata sandi sekarang',
        'water_target' => 'target minum',
        'code' => 'kode undangan',
    ],
];
