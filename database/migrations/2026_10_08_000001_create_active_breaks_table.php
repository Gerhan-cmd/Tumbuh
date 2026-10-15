<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu jeda aktif (berdiri dan peregangan 2 menit) yang diselesaikan.
     */
    public function up(): void
    {
        Schema::create('active_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('done_at');
            $table->unsignedSmallInteger('xp')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'done_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_breaks');
    }
};
