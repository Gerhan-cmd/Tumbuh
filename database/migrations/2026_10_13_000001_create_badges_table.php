<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar lencana. Setiap lencana punya satu syarat: sebuah angka (rule_type)
     * harus mencapai nilai tertentu (rule_value). Isinya langsung dimasukkan di sini.
     */
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('description');
            $table->string('rule_type', 30);           // water_total, goal_days, longest_streak, breaks_total, missions_done
            $table->unsignedInteger('rule_value');
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['tetes_pertama', 'Tetes Pertama',      'Catat gelas air pertamamu.',             'water_total',    1],
            ['hidrasi_penuh', 'Hidrasi Penuh',      'Capai target minum harian.',             'goal_days',      1],
            ['seratus_gelas', 'Seratus Gelas',      'Catat total 100 gelas air.',             'water_total',    100],
            ['beruntun_3',    'Tiga Hari Beruntun', 'Capai streak 3 hari.',                   'longest_streak', 3],
            ['beruntun_7',    'Seminggu Beruntun',  'Capai streak 7 hari.',                   'longest_streak', 7],
            ['mulai_bergerak','Mulai Bergerak',     'Selesaikan jeda aktif pertamamu.',       'breaks_total',   1],
            ['rajin_regang',  'Rajin Peregangan',   'Selesaikan total 20 jeda aktif.',        'breaks_total',   20],
            ['pemburu_misi',  'Pemburu Misi',       'Selesaikan total 10 misi harian.',       'missions_done',  10],
        ];

        DB::table('badges')->insert(array_map(fn ($r, $i) => [
            'code' => $r[0],
            'name' => $r[1],
            'description' => $r[2],
            'rule_type' => $r[3],
            'rule_value' => $r[4],
            'sort' => $i + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows, array_keys($rows)));
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
