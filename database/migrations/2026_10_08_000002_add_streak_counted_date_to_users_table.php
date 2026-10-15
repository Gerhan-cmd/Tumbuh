<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal terakhir yang sudah dihitung ke dalam streak.
     * Dari kolom ini aplikasi tahu berapa hari yang terlewat sejak streak terakhir bertambah.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('streak_counted_date')->nullable()->after('streak_freezes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('streak_counted_date');
        });
    }
};
