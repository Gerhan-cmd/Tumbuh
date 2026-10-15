<?php

namespace App\Http\Controllers;

use App\Models\Chest;
use App\Services\GamificationService;
use App\Services\MissionService;
use App\Services\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChestController extends Controller
{
    /**
     * POST /chests/{chest}/open — buka Peti Harian.
     *
     * {chest} otomatis diubah Laravel menjadi model Chest berdasarkan id (route model binding).
     */
    public function open(
        Request $request,
        Chest $chest,
        GamificationService $game,
        MissionService $missions,
        StreakService $streak,
    ): JsonResponse {
        $user = $request->user();

        // Hanya pemilik peti yang boleh membukanya
        abort_unless($chest->user_id === $user->id, 403);

        return DB::transaction(function () use ($user, $chest, $game, $missions, $streak) {
            // Kunci baris peti supaya tidak bisa dibuka dua kali lewat dua klik cepat
            $chest = Chest::whereKey($chest->id)->lockForUpdate()->firstOrFail();

            if ($chest->opened_at) {
                return response()->json(['message' => 'Peti hari ini sudah dibuka.'], 422);
            }

            $levelBefore = $game->levelOf($user);
            $reward = $missions->openChest($user, $chest);
            $user->refresh();

            return response()->json([
                'reward' => $reward,
                'level_up' => $game->levelOf($user) > $levelBefore,
                'progress' => $game->progress($user),
                'streak' => $streak->state($user),
                'missions' => $missions->state($user),
            ]);
        });
    }
}
