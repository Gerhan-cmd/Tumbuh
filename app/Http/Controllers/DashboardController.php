<?php

namespace App\Http\Controllers;

use App\Services\BadgeService;
use App\Services\GamificationService;
use App\Services\MissionService;
use App\Services\SquadService;
use App\Services\StreakService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * GET /dashboard — beranda: tanaman, level, XP, streak, minum, dan jeda aktif hari ini.
     */
    public function __invoke(Request $request, GamificationService $game, StreakService $streak, MissionService $missions, BadgeService $badges, SquadService $squads): View
    {
        $user = $request->user();

        // Hitung dulu hari-hari yang terlewat sejak terakhir dibuka
        $streakSync = $streak->sync($user);

        // Buat misi hari ini (kalau belum ada) dan perbarui progresnya
        $missions->refresh($user);

        // Berikan lencana yang syaratnya sudah terpenuhi (misalnya dari data lama)
        $newBadges = $badges->check($user);

        // Squad (kalau ada): perbarui tantangan minggu ini
        $squad = $squads->squadOf($user);
        if ($squad) {
            $squads->refreshChallenge($squad);
            $user->refresh();
        }

        $stat = $game->todayStat($user);

        return view('dashboard', [
            'user' => $user,
            'initial' => [
                'glasses' => $stat->glasses,
                'target' => $user->water_target,
                'goal_met' => $stat->goal_met,
                'limit' => config('gamification.limits.water_per_day'),
                'xp_per_glass' => config('gamification.xp.water'),
                'progress' => $game->progress($user),

                // Misi harian dan Peti Harian
                'missions' => $missions->state($user),

                'new_badges' => $newBadges,
                'squad' => $squad ? $squads->summary($squad, $user) : null,
                'squad_url' => route('squad'),

                // Streak
                'streak' => $streak->state($user),
                'streak_notice' => $this->streakNotice($streakSync),

                // Jeda aktif
                'breaks' => $stat->breaks,
                'last_break_at' => $game->lastBreakAt($user),
                'break_cfg' => [
                    'seconds' => config('gamification.break_seconds'),
                    'gap_min' => config('gamification.limits.break_gap_min'),
                    'max_per_day' => config('gamification.limits.breaks_per_day'),
                    'xp' => config('gamification.xp.break'),
                ],

                // Pengingat
                'user_id' => $user->id,
                'last_glass_at' => $game->lastGlassAt($user),
                'reminders' => array_merge(config('gamification.reminders'), [
                    'water_enabled' => $user->reminder_enabled,
                    'walk_enabled' => $user->walk_reminder_enabled,
                ]),
            ],
        ]);
    }

    /**
     * Kalimat pemberitahuan setelah ada hari yang terlewat (atau null jika tidak ada).
     */
    private function streakNotice(array $sync): ?string
    {
        if ($sync['broken']) {
            return "Streak {$sync['lost_streak']} harimu terputus. Tidak apa-apa, mulai lagi hari ini.";
        }

        if ($sync['frozen_days'] > 0) {
            return "{$sync['frozen_days']} pelindung streak terpakai untuk hari yang terlewat. Streak-mu aman.";
        }

        return null;
    }
}
