<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank misi: daftar semua misi yang bisa muncul sebagai misi harian.
     * Isinya langsung dimasukkan di sini supaya misi pasti tersedia tanpa perlu menjalankan seeder.
     */
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title');
            $table->string('type', 20);                          // water_count, water_before, goal, break_count
            $table->unsignedTinyInteger('target');
            $table->unsignedTinyInteger('before_hour')->nullable(); // khusus water_before
            $table->unsignedSmallInteger('xp_reward');
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['target_harian',    'Capai target minum hari ini',         'goal',         1, null, 30],
            ['minum_pagi',       'Minum 1 gelas sebelum jam 9 pagi',    'water_before', 1, 9,    15],
            ['minum_sebelum_12', 'Minum 2 gelas sebelum jam 12 siang',  'water_before', 2, 12,   20],
            ['minum_3',          'Catat 3 gelas air',                   'water_count',  3, null, 15],
            ['minum_5',          'Catat 5 gelas air',                   'water_count',  5, null, 25],
            ['jeda_1',           'Selesaikan 1 jeda aktif',             'break_count',  1, null, 15],
            ['jeda_3',           'Selesaikan 3 jeda aktif',             'break_count',  3, null, 30],
        ];

        DB::table('missions')->insert(array_map(fn ($r) => [
            'code' => $r[0],
            'title' => $r[1],
            'type' => $r[2],
            'target' => $r[3],
            'before_hour' => $r[4],
            'xp_reward' => $r[5],
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows));
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
