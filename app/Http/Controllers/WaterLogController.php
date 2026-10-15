<?php

namespace App\Http\Controllers;

use App\Models\DailyStat;
use App\Models\User;
use App\Models\WaterLog;
use App\Services\BadgeService;
use App\Services\GamificationService;
use App\Services\MissionService;
use App\Services\SquadService;
use App\Services\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaterLogController extends Controller
{
    public function __construct(
        private GamificationService $game,
        private StreakService $streak,
        private MissionService $missions,
        private BadgeService $badges,
        private SquadService $squads,
    ) {
    }

    /**
     * POST /water — catat 1 gelas air.
     *
     * Gelas selalu dihitung ke target harian, tetapi XP hanya diberikan
     * jika sudah lewat `water_xp_gap_min` menit sejak gelas ber-XP terakhir.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = config('gamification.limits.water_per_day');
        $gap = config('gamification.limits.water_xp_gap_min');

        return DB::transaction(function () use ($user, $limit, $gap) {
            $stat = $this->game->lockedTodayStat($user);
            $levelBefore = $this->game->levelOf($user);

            if ($stat->glasses >= $limit) {
                return response()->json([
                    'message' => "Sudah $limit gelas hari ini. Cukup dulu ya, minum berlebihan juga tidak baik.",
                ], 422);
            }

            // Kapan terakhir kali dapat XP dari minum?
            $lastRewarded = WaterLog::where('user_id', $user->id)
                ->where('xp', '>', 0)
                ->latest('logged_at')
                ->value('logged_at');

            $minutesSince = $lastRewarded ? $lastRewarded->diffInMinutes(now()) : null;
            $earnsXp = $minutesSince === null || $minutesSince >= $gap;
            $xpAmount = $earnsXp ? config('gamification.xp.water') : 0;

            WaterLog::create([
                'user_id' => $user->id,
                'logged_at' => now(),
                'xp' => $xpAmount,
            ]);

            $wasMet = $stat->goal_met;
            $stat->glasses++;
            $stat->goal_met = $stat->glasses >= $user->water_target;
            $stat->save();

            // Target baru saja tercapai: streak bertambah saat itu juga
            $goalJustMet = ! $wasMet && $stat->goal_met;
            $streakEvent = $goalJustMet
                ? $this->streak->markTodayMet($user)
                : ['counted' => false, 'freeze_earned' => false];

            if ($xpAmount > 0) {
                $this->game->award($user, $xpAmount, 'water', 'Minum 1 gelas');
            }

            // Misi yang terpenuhi oleh gelas ini langsung diberi XP
            $missionEvents = $this->missions->refresh($user);

            // Tantangan squad: kalau gelas ini membuatnya tercapai, semua anggota dapat bonus XP
            $squad = $this->squads->squadOf($user);
            $missionEvents['squad_completed'] = $squad ? $this->squads->refreshChallenge($squad) : false;

            $missionEvents['badges'] = $this->badges->check($user);
            $user->refresh();   // XP bisa bertambah lewat objek pengguna lain (bonus squad)
            $levelUp = $this->game->levelOf($user) > $levelBefore;

            return response()->json($this->payload($user, $stat, $xpAmount, $levelUp, $missionEvents, [
                'goal_just_met' => $goalJustMet,
                'freeze_earned' => $streakEvent['freeze_earned'],
                // Menit tersisa sampai gelas berikutnya bisa dapat XP lagi
                'next_xp_in' => $earnsXp ? $gap : (int) ceil($gap - $minutesSince),
            ]));
        });
    }

    /**
     * DELETE /water/last — batalkan gelas terakhir hari ini (salah ketuk).
     * XP yang dikurangi = XP yang dulu didapat gelas itu (bisa 0).
     */
    public function destroyLast(Request $request): JsonResponse
    {
        $user = $request->user();

        return DB::transaction(function () use ($user) {
            $stat = $this->game->lockedTodayStat($user);

            $last = WaterLog::where('user_id', $user->id)
                ->whereDate('logged_at', today())
                ->latest('logged_at')
                ->first();

            if (! $last || $stat->glasses === 0) {
                return response()->json(['message' => 'Belum ada gelas yang dicatat hari ini.'], 422);
            }

            $refund = $last->xp;
            $last->delete();

            $wasMet = $stat->goal_met;
            $stat->glasses--;
            $stat->goal_met = $stat->glasses >= $user->water_target;
            $stat->save();

            // Target tidak lagi tercapai: streak hari ini ditarik kembali
            if ($wasMet && ! $stat->goal_met) {
                $this->streak->unmarkToday($user);
            }

            if ($refund > 0) {
                $this->game->award($user, -$refund, 'water_undo', 'Batal 1 gelas');
            }

            // Progres misi ikut turun; misi yang sudah selesai tetap selesai
            $missionEvents = $this->missions->refresh($user);

            return response()->json($this->payload($user, $stat, -$refund, false, $missionEvents, [
                'goal_just_met' => false,
                'freeze_earned' => false,
            ]));
        });
    }

    /**
     * Data yang dikirim balik ke browser untuk memperbarui tampilan.
     */
    private function payload(User $user, DailyStat $stat, int $xpGained, bool $levelUp, array $missionEvents, array $extra = []): array
    {
        $user->refresh();

        return array_merge([
            'glasses' => $stat->glasses,
            'target' => $user->water_target,
            'goal_met' => $stat->goal_met,
            'xp_gained' => $xpGained,
            'level_up' => $levelUp,
            'progress' => $this->game->progress($user),
            'last_glass_at' => $this->game->lastGlassAt($user),
            'streak' => $this->streak->state($user),
            'missions' => $this->missions->state($user),
            'mission_events' => $missionEvents['completed'],
            'chest_unlocked' => $missionEvents['chest_unlocked'],
            'badge_events' => $missionEvents['badges'] ?? [],
            'squad_completed' => $missionEvents['squad_completed'] ?? false,
            'squad' => ($squad = $this->squads->squadOf($user)) ? $this->squads->summary($squad, $user) : null,
        ], $extra);
    }
}
