<?php

/*
|--------------------------------------------------------------------------
| Aturan game Tumbuh
|--------------------------------------------------------------------------
| Semua angka game ada di sini supaya mudah diubah tanpa menyentuh kode.
| Dipanggil dengan config('gamification.xp.water'), dst.
*/

return [

    // XP untuk setiap aksi
    'xp' => [
        'water' => 10,   // per gelas
        'break' => 20,   // per jeda aktif
    ],

    // XP yang dibutuhkan untuk naik dari level 1->2, 2->3, ... 7->8.
    // Level maksimal = jumlah elemen + 1 = 8.
    'level_steps' => [150, 250, 350, 500, 700, 900, 1200],

    // Nama wujud tanaman per level (indeks 0 = level 1)
    'plants' => [
        'Bibit',
        'Kecambah',
        'Tunas',
        'Tunas Kuat',
        'Pohon Muda',
        'Pohon Rindang',
        'Pohon Berbuah',
        'Pohon Legenda',
    ],

    // Batas harian (anti-curang & aman untuk kesehatan)
    'limits' => [
        'water_per_day' => 12,   // gelas maksimal yang bisa dicatat per hari
        'water_xp_gap_min' => 10, // jeda minimal antar-gelas agar dapat XP (menit)
        'break_gap_min' => 45,   // menit minimal antar-jeda aktif
        'breaks_per_day' => 8,   // jeda aktif maksimal per hari
    ],

    // Jeda aktif: lama timer di layar (detik)
    'break_seconds' => 120,

    // Misi harian dan Peti Harian
    'missions' => [
        'per_day' => 4,          // jumlah misi per hari (1 misi target harian + 3 acak)
        'for_chest' => 3,        // misi selesai yang dibutuhkan untuk membuka peti
    ],
    'chest' => [
        'xp_min' => 20,          // isi peti: bonus XP acak di antara dua angka ini
        'xp_max' => 50,
        'freeze_chance' => 30,   // peluang (persen) isi peti berupa 1 pelindung streak
    ],

    // Squad
    'squad' => [
        'max_members' => 8,
        'challenge_days' => 5,    // target tantangan = jumlah target harian semua anggota x 5 hari
        'challenge_xp' => 50,     // bonus XP untuk SETIAP anggota saat tantangan tercapai
    ],

    // Streak
    'streak' => [
        'freeze_every' => 7,   // setiap kelipatan 7 hari streak, dapat 1 pelindung
        'max_freezes' => 2,    // pelindung maksimal yang bisa disimpan
    ],

    // Pilihan target minum di halaman onboarding
    'water_target' => [
        'min' => 6,
        'max' => 12,
        'default' => 8,
    ],

    // Pengingat (berjalan di browser selama aplikasi terbuka)
    'reminders' => [
        'water_every' => 90,    // menit sejak gelas terakhir
        'walk_every' => 120,    // menit sejak aplikasi dibuka / ajakan jalan terakhir
        'max_per_day' => 6,     // total pengingat per hari
        'quiet_start' => 22,    // jam senyap mulai (22.00)
        'quiet_end' => 6,       // jam senyap selesai (06.00)
    ],

];
