<?php

namespace App\Http\Controllers;

use App\Services\BadgeService;
use App\Services\GamificationService;
use App\Services\MissionService;
use App\Services\StreakService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * GET /settings — atur target minum dan pengingat.
     */
    public function edit(Request $request): View
    {
        return view('settings', [
            'user' => $request->user(),
            'min' => config('gamification.water_target.min'),
            'max' => config('gamification.water_target.max'),
            'reminders' => config('gamification.reminders'),
        ]);
    }

    /**
     * PATCH /settings — simpan pengaturan.
     *
     * Kalau target berubah, status "target tercapai" hari ini dihitung ulang,
     * lalu streak, misi, dan lencana ikut menyesuaikan.
     */
    public function update(
        Request $request,
        GamificationService $game,
        StreakService $streak,
        MissionService $missions,
        BadgeService $badges,
    ): RedirectResponse {
        $min = config('gamification.water_target.min');
        $max = config('gamification.water_target.max');

        $data = $request->validate([
            'water_target' => ['required', 'integer', "between:$min,$max"],
            'reminder_enabled' => ['required', 'boolean'],
            'walk_reminder_enabled' => ['required', 'boolean'],
        ], [
            'water_target.between' => "Target minum harus antara $min dan $max gelas.",
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data, $game, $streak, $missions, $badges) {
            $user->fill($data)->save();

            $stat = $game->lockedTodayStat($user);
            $wasMet = $stat->goal_met;
            $stat->goal_met = $stat->glasses >= $user->water_target;
            $stat->save();

            if (! $wasMet && $stat->goal_met) {
                $streak->markTodayMet($user);
            } elseif ($wasMet && ! $stat->goal_met) {
                $streak->unmarkToday($user);
            }

            $missions->refresh($user);
            $badges->check($user);
        });

        return redirect()->route('settings.edit')->with('status', 'Pengaturan disimpan.');
    }
}
