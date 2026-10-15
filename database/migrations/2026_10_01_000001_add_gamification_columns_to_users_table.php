<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom gamifikasi Tumbuh di tabel users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Target & pengingat
            $table->unsignedTinyInteger('water_target')->default(8)->after('password');
            $table->boolean('reminder_enabled')->default(true)->after('water_target');
            $table->boolean('walk_reminder_enabled')->default(true)->after('reminder_enabled');

            // Progres game (XP disimpan total, level dihitung dari config)
            $table->unsignedInteger('xp')->default(0)->after('walk_reminder_enabled');
            $table->unsignedSmallInteger('current_streak')->default(0)->after('xp');
            $table->unsignedSmallInteger('longest_streak')->default(0)->after('current_streak');
            $table->unsignedTinyInteger('streak_freezes')->default(0)->after('longest_streak');
            $table->string('plant_type', 30)->default('klasik')->after('streak_freezes');

            // Penanda aktivitas
            $table->date('last_active_date')->nullable()->after('plant_type');
            $table->timestamp('onboarded_at')->nullable()->after('last_active_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'water_target',
                'reminder_enabled',
                'walk_reminder_enabled',
                'xp',
                'current_streak',
                'longest_streak',
                'streak_freezes',
                'plant_type',
                'last_active_date',
                'onboarded_at',
            ]);
        });
    }
};
