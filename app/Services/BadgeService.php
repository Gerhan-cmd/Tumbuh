<?php

namespace App\Services;

use App\Models\ActiveBreak;
use App\Models\Badge;
use App\Models\DailyStat;
use App\Models\User;
use App\Models\UserMission;
use App\Models\WaterLog;
use Illuminate\Support\Facades\DB;

/**
 * Lencana: penghargaan permanen untuk pencapaian jangka panjang.
 *
 * Setiap lencana punya syarat berupa satu angka yang harus dicapai.
 * Angka-angka itu dihitung dari data yang sudah ada, lalu lencana yang
 * syaratnya terpenuhi dan belum dimiliki langsung diberikan.
 */
class BadgeService
{
    /**
     * Berikan lencana yang baru terpenuhi.
     *
     * @return list<array{code:string,name:string}>  lencana yang baru didapat
     */
    public function check(User $user): array
    {
        $badges = Badge::orderBy('sort')->get();
        $owned = $this->ownedCodes($user);

        $newCodes = self::earned(
            $badges->map(fn (Badge $b) => ['code' => $b->code, 'rule_type' => $b->rule_type, 'rule_value' => $b->rule_value])->all(),
            $this->metrics($user),
            $owned,
        );

        $new = [];
        foreach ($badges->whereIn('code', $newCodes) as $badge) {
            // insertOrIgnore: aman kalau dua request datang bersamaan
            DB::table('user_badges')->insertOrIgnore([
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'earned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $new[] = ['code' => $badge->code, 'name' => $badge->name];
        }

        return $new;
    }

    /**
     * Semua lencana beserta status dan progresnya, untuk halaman Lencana.
     */
    public function all(User $user): array
    {
        $metrics = $this->metrics($user);
        $earnedAt = DB::table('user_badges')->where('user_id', $user->id)->pluck('earned_at', 'badge_id');

        return Badge::orderBy('sort')->get()->map(fn (Badge $b) => [
            'code' => $b->code,
            'name' => $b->name,
            'description' => $b->description,
            'earned' => $earnedAt->has($b->id),
            'earned_at' => $earnedAt->get($b->id),
            'current' => min($metrics[$b->rule_type] ?? 0, $b->rule_value),
            'target' => $b->rule_value,
        ])->all();
    }

    /**
     * Angka-angka yang dipakai sebagai syarat lencana.
     *
     * @return array<string,int>
     */
    public function metrics(User $user): array
    {
        return [
            'water_total' => WaterLog::where('user_id', $user->id)->count(),
            'goal_days' => DailyStat::where('user_id', $user->id)->where('goal_met', true)->count(),
            'longest_streak' => (int) $user->longest_streak,
            'breaks_total' => ActiveBreak::where('user_id', $user->id)->count(),
            'missions_done' => UserMission::where('user_id', $user->id)->whereNotNull('completed_at')->count(),
        ];
    }

    /**
     * @return list<string>
     */
    private function ownedCodes(User $user): array
    {
        return DB::table('user_badges')
            ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
            ->where('user_badges.user_id', $user->id)
            ->pluck('badges.code')
            ->all();
    }

    // ------------------------------------------------------------------
    // Fungsi murni (tanpa database) — mudah diuji
    // ------------------------------------------------------------------

    /**
     * Kode lencana yang syaratnya terpenuhi tapi belum dimiliki.
     *
     * @param  list<array{code:string,rule_type:string,rule_value:int}>  $badges
     * @param  array<string,int>  $metrics
     * @param  list<string>  $owned
     * @return list<string>
     */
    public static function earned(array $badges, array $metrics, array $owned): array
    {
        $result = [];
        foreach ($badges as $b) {
            if (in_array($b['code'], $owned, true)) {
                continue;
            }
            if (($metrics[$b['rule_type']] ?? 0) >= $b['rule_value']) {
                $result[] = $b['code'];
            }
        }

        return $result;
    }
}
