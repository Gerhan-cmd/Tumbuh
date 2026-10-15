<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tantangan mingguan squad: target total gelas bersama, satu per minggu (Senin-Minggu).
     */
    public function up(): void
    {
        Schema::create('squad_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('squad_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->unsignedSmallInteger('target_glasses')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['squad_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_challenges');
    }
};
