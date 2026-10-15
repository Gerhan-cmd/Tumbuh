<?php

namespace App\Services;

use App\Models\ActiveBreak;
use App\Models\DailyStat;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    /**
     * Tambah (atau kurangi) XP pengguna dan catat riwayatnya.
     *
     * @return array{xp_gained:int,total_xp:int,level:int,level_up:bool,plant:string}
     */
    public function award(User $user, int $amount, string $source, ?string $description = null): array
    {
        return DB::transaction(function () use ($user, $amount, $source, $description) {
            $levelBefore = $this->levelOf($user);

            XpTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'source' => $source,
                'description' => $description,
            ]);

            // XP total tidak boleh di bawah 0
            $user->xp = max(0, $user->xp + $amount);
            $user->save();

            $levelAfter = $this->levelOf($user);

            return [
                'xp_gained' => $amount,
                'total_xp' => $user->xp,
                'level' => $levelAfter,
                'level_up' => $levelAfter > $levelBefore,
                'plant' => self::plantName($levelAfter, config('gamification.plants')),
            ];
        });
    }

    /**
     * Level pengguna saat ini (1-8).
     */
    public function levelOf(User $user): int
    {
        return self::levelForXp($user->xp, config('gamification.level_steps'));
    }

    /**
     * Data progres untuk ditampilkan di beranda.
     *
     * @return array{level:int,plant:string,xp_total:int,xp_in_level:int,xp_needed:int,percent:int,is_max:bool}
     */
    public function progress(User $user): array
    {
        return self::progressForXp(
            $user->xp,
            config('gamification.level_steps'),
            config('gamification.plants'),
        );
    }

    /**
     * Ambil (atau buat) rekap hari ini milik pengguna.
     */
    public function todayStat(User $user): DailyStat
    {
        return DailyStat::firstOrCreate(
            ['user_id' => $user->id, 'date' => today()->toDateString()],
            ['glasses' => 0, 'breaks' => 0, 'goal_met' => false],
        );
    }

    /**
     * Rekap hari ini yang dikunci selama transaksi, supaya dua klik cepat
     * tidak menghasilkan hitungan yang salah. Harus dipanggil di dalam DB::transaction.
     */
    public function lockedTodayStat(User $user): DailyStat
    {
        $stat = $this->todayStat($user);

        return DailyStat::whereKey($stat->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * Waktu jeda aktif terakhir hari ini (format ISO 8601), atau null.
     */
    public function lastBreakAt(User $user): ?string
    {
        $at = ActiveBreak::where('user_id', $user->id)
            ->whereDate('done_at', today())
            ->latest('done_at')
            ->value('done_at');

        return $at?->toIso8601String();
    }

    /**
     * Waktu gelas terakhir hari ini (format ISO 8601), atau null jika belum minum.
     * Dipakai browser untuk menghitung kapan pengingat minum berikutnya.
     */
    public function lastGlassAt(User $user): ?string
    {
        $at = WaterLog::where('user_id', $user->id)
            ->whereDate('logged_at', today())
            ->latest('logged_at')
            ->value('logged_at');

        return $at?->toIso8601String();
    }

    // ------------------------------------------------------------------
    // Fungsi murni (tanpa database) — mudah diuji
    // ------------------------------------------------------------------

    /**
     * Hitung level dari total XP dengan ambang kumulatif.
     * Contoh dengan [150, 250, ...]: 0-149 = L1, 150-399 = L2, 400-749 = L3, ...
     *
     * @param  list<int>  $steps
     */
    public static function levelForXp(int $xp, array $steps): int
    {
        $level = 1;
        $threshold = 0;

        foreach ($steps as $need) {
            $threshold += $need;
            if ($xp < $threshold) {
                break;
            }
            $level++;
        }

        return $level;
    }

    /**
     * @param  list<int>  $steps
     * @param  list<string>  $plants
     * @return array{level:int,plant:string,xp_total:int,xp_in_level:int,xp_needed:int,percent:int,is_max:bool}
     */
    public static function progressForXp(int $xp, array $steps, array $plants): array
    {
        $level = self::levelForXp($xp, $steps);
        $isMax = $level > count($steps);

        // XP yang sudah dipakai untuk mencapai level sekarang
        $spent = array_sum(array_slice($steps, 0, $level - 1));
        $inLevel = $xp - $spent;
        $needed = $isMax ? 0 : $steps[$level - 1];

        return [
            'level' => $level,
            'plant' => self::plantName($level, $plants),
            'xp_total' => $xp,
            'xp_in_level' => $inLevel,
            'xp_needed' => $needed,
            'percent' => $isMax ? 100 : (int) floor($inLevel / $needed * 100),
            'is_max' => $isMax,
        ];
    }

    /**
     * @param  list<string>  $plants
     */
    public static function plantName(int $level, array $plants): string
    {
        return $plants[$level - 1] ?? $plants[count($plants) - 1];
    }
}
