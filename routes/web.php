<?php

use App\Http\Controllers\ActiveBreakController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\ChestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SquadController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\WaterLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Halaman utama aplikasi: wajib login DAN sudah onboarding
Route::middleware(['auth', 'verified', 'onboarded'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Catat minum (dipanggil lewat fetch, mengembalikan JSON)
    Route::post('/water', [WaterLogController::class, 'store'])->name('water.store');
    Route::delete('/water/last', [WaterLogController::class, 'destroyLast'])->name('water.undo');

    // Jeda aktif selesai (dipanggil setelah timer 2 menit habis)
    Route::post('/breaks', [ActiveBreakController::class, 'store'])->name('breaks.store');

    // Buka Peti Harian (setelah 3 misi selesai)
    Route::post('/chests/{chest}/open', [ChestController::class, 'open'])->name('chests.open');

    // Halaman lencana, ringkasan mingguan, dan pengaturan
    Route::get('/badges', BadgeController::class)->name('badges');
    Route::get('/summary', SummaryController::class)->name('summary');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Squad: buat, gabung, keluar, dan lihat peringkat
    Route::get('/squad', [SquadController::class, 'show'])->name('squad');
    Route::post('/squad', [SquadController::class, 'store'])->name('squad.store');
    Route::post('/squad/join', [SquadController::class, 'join'])->name('squad.join');
    Route::delete('/squad', [SquadController::class, 'leave'])->name('squad.leave');
});

Route::middleware('auth')->group(function () {
    // Onboarding: atur target minum & pengingat (sekali setelah daftar)
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::patch('/onboarding', [OnboardingController::class, 'update'])->name('onboarding.update');

    // Bawaan Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
