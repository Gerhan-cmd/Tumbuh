<?php

namespace App\Services;

use App\Models\Chest;
use App\Models\Mission;
use App\Models\User;
use App\Models\UserMission;
use App\Models\WaterLog;
use Illuminate\Support\Collection;

/**
 * Misi harian dan Peti Harian.
 *
 * - Setiap hari pengguna mendapat 4 misi: "Capai target minum hari ini" + 3 misi acak.
 * - Progres misi dihitung ulang dari data yang sudah ada (gelas, jeda aktif, target),
 *   bukan ditambah satu per satu. Jadi tetap benar walaupun ada gelas yang dibatalkan.
 * - Misi yang sudah selesai tetap selesai dan XP-nya hanya diberikan sekali.
 * - Setelah 3 misi selesai, Peti Harian terbuka. Isinya ditentukan saat peti dibuka.
 */
class MissionService
{
    public function __construct(private GamificationService $game)
    {
    }

    /**
     * Misi hari ini milik pengguna. Kalau belum ada, dibuat sekarang.
     *
     * @return Collection<int, UserMission>
     */
    public function today(User $user): Collection
    {
        $existing = UserMission::with('mission')
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->orderBy('id')
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $all = Mission::all();
        $codes = self::pickMissions(
            $all->map(fn (Mission $m) => ['code' => $m->code, 'type' => $m->type, 'before_hour' => $m->before_hour])->all(),
            $user->id,
            today()->toDateString(),
            now()->hour,
            config('gamification.missions.per_day'),
        );

        foreach ($codes as $code) {
            // firstOrCreate: aman kalau dua request datang bersamaan
            UserMission::firstOrCreate([
                'user_id' => $user->id,
                'mission_id' => $all->firstWhere('code', $code)->id,
                'date' => today()->toDateString(),
            ]);
        }

        return UserMission::with('mission')
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->orderBy('id')
            ->get();
    }

    /**
     * Hitung ulang progres misi hari ini. Misi yang baru selesai langsung diberi XP.
     *
     * @return array{completed: list<array{title:string,xp:int}>, chest_unlocked: bool}
     */
    public function refresh(User $user): array
    {
        $stat = $this->game->todayStat($user);
        $completed = [];

        foreach ($this->today($user) as $um) {
            if ($um->completed_at) {
                continue;   // sudah selesai sebelumnya
            }

            $mission = $um->mission;
            $value = match ($mission->type) {
                'water_count' => $stat->glasses,
                'goal' => $stat->goal_met ? 1 : 0,
                'break_count' => $stat->breaks,
                'water_before' => WaterLog::where('user_id', $user->id)
                    ->whereDate('logged_at', today())
                    ->whereTime('logged_at', '<', sprintf('%02d:00:00', $mission->before_hour))
                    ->count(),
                default => 0,
            };

            $um->progress = min($value, $mission->target);

            if ($um->progress >= $mission->target) {
                $um->completed_at = now();
                $this->game->award($user, $mission->xp_reward, 'mission', "Misi: {$mission->title}");
                $completed[] = ['title' => $mission->title, 'xp' => $mission->xp_reward];
            }

            $um->save();
        }

        return [
            'completed' => $completed,
            'chest_unlocked' => $this->unlockChestIfEarned($user),
        ];
    }

    /**
     * Buat Peti Harian kalau misi selesai sudah cukup dan peti hari ini belum ada.
     */
    private function unlockChestIfEarned(User $user): bool
    {
        $done = UserMission::where('user_id', $user->id)
            ->whereDate('date', today())
            ->whereNotNull('completed_at')
            ->count();

        if ($done < config('gamification.missions.for_chest')) {
            return false;
        }

        $chest = Chest::firstOrCreate(['user_id' => $user->id, 'date' => today()->toDateString()]);

        return $chest->wasRecentlyCreated;   // true hanya saat peti baru saja muncul
    }

    /**
     * Buka peti: tentukan isinya, lalu berikan hadiahnya.
     *
     * @return array{type:string,value:int}
     */
    public function openChest(User $user, Chest $chest): array
    {
        $maxFreezes = config('gamification.streak.max_freezes');

        $reward = self::rollReward(
            random_int(0, 99),
            random_int(config('gamification.chest.xp_min'), config('gamification.chest.xp_max')),
            $user->streak_freezes < $maxFreezes,
            config('gamification.chest.freeze_chance'),
        );

        if ($reward['type'] === 'freeze') {
            $user->streak_freezes++;
            $user->save();
        } else {
            $this->game->award($user, $reward['value'], 'chest', 'Peti Harian');
        }

        $chest->reward_type = $reward['type'];
        $chest->reward_value = $reward['value'];
        $chest->opened_at = now();
        $chest->save();

        return $reward;
    }

    /**
     * Data misi dan peti untuk ditampilkan di beranda.
     */
    public function state(User $user): array
    {
        $missions = $this->today($user)->map(fn (UserMission $um) => [
            'title' => $um->mission->title,
            'xp' => $um->mission->xp_reward,
            'progress' => $um->progress,
            'target' => $um->mission->target,
            'done' => $um->completed_at !== null,
        ])->values()->all();

        $chest = Chest::where('user_id', $user->id)->whereDate('date', today())->first();

        return [
            'list' => $missions,
            'done' => count(array_filter($missions, fn ($m) => $m['done'])),
            'for_chest' => config('gamification.missions.for_chest'),
            'chest' => $chest ? [
                'id' => $chest->id,
                'opened' => $chest->opened_at !== null,
                'reward_type' => $chest->reward_type,
                'reward_value' => $chest->reward_value,
            ] : null,
        ];
    }

    // ------------------------------------------------------------------
    // Fungsi murni (tanpa database) — mudah diuji
    // ------------------------------------------------------------------

    /**
     * Pilih kode misi hari ini: "target_harian" selalu ada, sisanya diambil acak.
     *
     * Acaknya tetap (deterministik): pengguna yang sama di tanggal yang sama selalu
     * mendapat misi yang sama, walaupun halaman dibuka berkali-kali.
     * Misi "sebelum jam X" tidak dipilih kalau jam X sudah lewat.
     *
     * @param  list<array{code:string,type:string,before_hour:?int}>  $missions
     * @return list<string>
     */
    public static function pickMissions(array $missions, int $userId, string $date, int $hourNow, int $count): array
    {
        $fixed = 'target_harian';
        $pool = array_values(array_filter($missions, fn ($m) =>
            $m['code'] !== $fixed
            && ! ($m['type'] === 'water_before' && $m['before_hour'] <= $hourNow)
        ));

        // Urutkan berdasarkan hash (kode + pengguna + tanggal) -> acak tapi selalu sama
        usort($pool, fn ($a, $b) =>
            strcmp(md5("{$userId}|{$date}|{$a['code']}"), md5("{$userId}|{$date}|{$b['code']}")));

        $picked = array_column(array_slice($pool, 0, $count - 1), 'code');

        $hasFixed = in_array($fixed, array_column($missions, 'code'), true);

        return $hasFixed ? array_merge([$fixed], $picked) : array_slice(array_column($pool, 'code'), 0, $count);
    }

    /**
     * Tentukan isi peti dari angka acak.
     *
     * @param  int  $roll  angka 0-99
     * @param  int  $xp  bonus XP yang sudah diacak
     * @param  bool  $canFreeze  false jika pelindung streak sudah penuh
     * @return array{type:string,value:int}
     */
    public static function rollReward(int $roll, int $xp, bool $canFreeze, int $freezeChance): array
    {
        if ($canFreeze && $roll < $freezeChance) {
            return ['type' => 'freeze', 'value' => 1];
        }

        return ['type' => 'xp', 'value' => $xp];
    }
}
