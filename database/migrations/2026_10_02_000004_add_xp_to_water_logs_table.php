<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan XP yang didapat tiap gelas.
     * 0 = gelas tercatat tetapi tanpa XP (dicatat terlalu cepat setelah gelas sebelumnya).
     */
    public function up(): void
    {
        Schema::table('water_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('xp')->default(0)->after('logged_at');
        });

        // Gelas yang sudah tercatat sebelum kolom ini ada dianggap mendapat XP penuh
        DB::table('water_logs')->update(['xp' => config('gamification.xp.water', 10)]);
    }

    public function down(): void
    {
        Schema::table('water_logs', function (Blueprint $table) {
            $table->dropColumn('xp');
        });
    }
};
