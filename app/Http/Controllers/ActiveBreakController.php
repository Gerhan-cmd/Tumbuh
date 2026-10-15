<?php

namespace App\Http\Controllers;

use App\Models\ActiveBreak;
use App\Services\BadgeService;
use App\Services\GamificationService;
use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActiveBreakController extends Controller
{
    public function __construct(
        private GamificationService $game,
        private MissionService $missions,
        private BadgeService $badges,
    ) {
    }

    /**
     * POST /breaks — catat satu jeda aktif yang selesai.
     *
     * Timer 2 menit berjalan di browser. Server menjaga dua batas:
     * jarak minimal antar-jeda dan jumlah maksimal per hari.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $gap = config('gamification.limits.break_gap_min');
        $max = config('gamification.limits.breaks_per_day');

        return DB::transaction(function () use ($user, $gap, $max) {
            $stat = $this->game->lockedTodayStat($user);
            $levelBefore = $this->game->levelOf($user);

            if ($stat->breaks >= $max) {
                return response()->json([
                    'message' => "Sudah $max jeda aktif hari ini. Hebat! Lanjutkan besok.",
                ], 422);
            }

            $last = ActiveBreak::where('user_id', $user->id)
                ->latest('done_at')
                ->value('done_at');

            if ($last) {
                $minutesSince = $last->diffInMinutes(now());
                if ($minutesSince < $gap) {
                    $wait = (int) ceil($gap - $minutesSince);

                    return response()->json([
                        'message' => "Jeda berikutnya bisa dicatat $wait menit lagi.",
                    ], 422);
                }
            }

            $xpAmount = config('gamification.xp.break');

            ActiveBreak::create([
                'user_id' => $user->id,
                'done_at' => now(),
                'xp' => $xpAmount,
            ]);

            $stat->breaks++;
            $stat->save();

            $this->game->award($user, $xpAmount, 'break', 'Jeda aktif 2 menit');

            // Misi jeda aktif yang terpenuhi langsung diberi XP
            $missionEvents = $this->missions->refresh($user);
            $newBadges = $this->badges->check($user);
            $user->refresh();

            return response()->json([
                'breaks' => $stat->breaks,
                'last_break_at' => $this->game->lastBreakAt($user),
                'xp_gained' => $xpAmount,
                'level_up' => $this->game->levelOf($user) > $levelBefore,
                'progress' => $this->game->progress($user),
                'missions' => $this->missions->state($user),
                'mission_events' => $missionEvents['completed'],
                'chest_unlocked' => $missionEvents['chest_unlocked'],
                'badge_events' => $newBadges,
            ]);
        });
    }
}
