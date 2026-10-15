<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Aturan streak Tumbuh.
 *
 * - Streak bertambah 1 begitu target minum hari itu tercapai (tidak menunggu tengah malam).
 * - Hari yang terlewat tanpa mencapai target memakai 1 pelindung streak (streak freeze).
 *   Kalau pelindung habis, streak kembali ke 0.
 * - Setiap kelipatan N hari streak (bawaan 7), pengguna mendapat 1 pelindung, maksimal 2.
 *
 * Tidak perlu cron: hari yang terlewat dihitung saat pengguna membuka aplikasi lagi.
 */
class StreakService
{
    /**
     * Menangani hari-hari yang terlewat. Aman dipanggil berkali-kali.
     *
     * @return array{frozen_days:int,broken:bool,lost_streak:int}
     */
    public function sync(User $user): array
    {
        $result = ['frozen_days' => 0, 'broken' => false, 'lost_streak' => 0];

        if ($user->current_streak === 0) {
            return $result;   // tidak ada streak yang bisa putus
        }

        $last = $user->streak_counted_date;

        // Hari yang terlewat = hari di antara tanggal terakhir dihitung dan hari ini.
        // Contoh: terakhir dihitung Senin, sekarang Kamis -> Selasa dan Rabu terlewat (2 hari).
        $missed = $last ? max(0, self::daysBetween($last, today()) - 1) : PHP_INT_MAX;

        if ($missed === 0) {
            return $result;
        }

        $resolved = self::resolveMisses($user->current_streak, $user->streak_freezes, $missed);

        $result['frozen_days'] = $resolved['used'];
        $user->streak_freezes = $resolved['freezes'];

        if ($resolved['broken']) {
            $result['broken'] = true;
            $result['lost_streak'] = $user->current_streak;
            $user->current_streak = 0;
            $user->streak_counted_date = null;
        } else {
            // Hari yang dilindungi dianggap sudah dihitung, supaya pelindung tidak terpakai dua kali
            $user->streak_counted_date = $last->copy()->addDays($resolved['used']);
        }

        $user->save();

        return $result;
    }

    /**
     * Dipanggil saat target hari ini baru saja tercapai.
     *
     * @return array{counted:bool,freeze_earned:bool}
     */
    public function markTodayMet(User $user): array
    {
        $this->sync($user);   // jaga-jaga halaman dibiarkan terbuka melewati tengah malam

        if ($user->streak_counted_date?->isSameDay(today())) {
            return ['counted' => false, 'freeze_earned' => false];   // hari ini sudah dihitung
        }

        $user->current_streak++;
        $user->longest_streak = max($user->longest_streak, $user->current_streak);
        $user->streak_counted_date = today();

        $every = config('gamification.streak.freeze_every');
        $max = config('gamification.streak.max_freezes');
        $earned = $user->current_streak % $every === 0 && $user->streak_freezes < $max;

        if ($earned) {
            $user->streak_freezes++;
        }

        $user->save();

        return ['counted' => true, 'freeze_earned' => $earned];
    }

    /**
     * Dipanggil saat gelas dibatalkan sehingga target hari ini tidak lagi tercapai.
     * Pelindung yang sudah didapat tidak ditarik kembali.
     */
    public function unmarkToday(User $user): void
    {
        if (! $user->streak_counted_date?->isSameDay(today())) {
            return;
        }

        $user->current_streak = max(0, $user->current_streak - 1);
        $user->streak_counted_date = $user->current_streak > 0 ? today()->subDay() : null;
        $user->save();
    }

    /**
     * Data streak untuk ditampilkan di beranda.
     *
     * @return array{current:int,longest:int,freezes:int,counted_today:bool}
     */
    public function state(User $user): array
    {
        return [
            'current' => $user->current_streak,
            'longest' => $user->longest_streak,
            'freezes' => $user->streak_freezes,
            'counted_today' => (bool) $user->streak_counted_date?->isSameDay(today()),
        ];
    }

    // ------------------------------------------------------------------
    // Fungsi murni (tanpa database) — mudah diuji
    // ------------------------------------------------------------------

    /**
     * Apa yang terjadi pada streak jika ada sekian hari terlewat?
     *
     * @return array{streak:int,freezes:int,used:int,broken:bool}
     */
    public static function resolveMisses(int $streak, int $freezes, int $missedDays): array
    {
        $used = min($freezes, $missedDays);       // tiap hari terlewat memakai 1 pelindung
        $broken = $missedDays > $freezes;         // pelindung tidak cukup -> streak putus

        return [
            'streak' => $broken ? 0 : $streak,
            'freezes' => $freezes - $used,
            'used' => $used,
            'broken' => $broken,
        ];
    }

    /**
     * Selisih hari kalender antara dua tanggal (jam diabaikan).
     */
    public static function daysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) round(
            ($to->copy()->startOfDay()->getTimestamp() - $from->copy()->startOfDay()->getTimestamp()) / 86400
        );
    }
}
