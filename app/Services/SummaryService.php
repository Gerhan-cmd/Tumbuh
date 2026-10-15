<?php

namespace App\Services;

use App\Models\DailyStat;
use App\Models\User;
use App\Models\XpTransaction;

/**
 * Ringkasan 7 hari terakhir: grafik gelas per hari, total, perbandingan dengan
 * 7 hari sebelumnya, dan beberapa kalimat umpan balik.
 */
class SummaryService
{
    private const DAY_NAMES = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    private const DAY_FULL = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public function week(User $user): array
    {
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = today()->subDays($i)->toDateString();
        }
        $prevDates = [];
        for ($i = 13; $i >= 7; $i--) {
            $prevDates[] = today()->subDays($i)->toDateString();
        }

        $rows = DailyStat::where('user_id', $user->id)
            ->whereBetween('date', [$prevDates[0], end($dates)])
            ->get()
            ->mapWithKeys(fn (DailyStat $s) => [$s->date->toDateString() => [
                'glasses' => $s->glasses,
                'breaks' => $s->breaks,
                'goal_met' => $s->goal_met,
            ]])
            ->all();

        $xpWeek = (int) XpTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', today()->subDays(6)->startOfDay())
            ->sum('amount');

        return self::build($rows, $dates, $prevDates, $user->water_target, $xpWeek, today()->toDateString());
    }

    // ------------------------------------------------------------------
    // Fungsi murni (tanpa database) — mudah diuji
    // ------------------------------------------------------------------

    /**
     * @param  array<string,array{glasses:int,breaks:int,goal_met:bool}>  $rows  data per tanggal (Y-m-d)
     * @param  list<string>  $dates  7 tanggal minggu ini, urut dari yang terlama
     * @param  list<string>  $prevDates  7 tanggal sebelumnya
     */
    public static function build(array $rows, array $dates, array $prevDates, int $target, int $xpWeek, string $today): array
    {
        $days = [];
        foreach ($dates as $date) {
            $r = $rows[$date] ?? ['glasses' => 0, 'breaks' => 0, 'goal_met' => false];
            $days[] = [
                'date' => $date,
                'label' => self::DAY_NAMES[(int) date('w', strtotime($date))],
                'name' => self::DAY_FULL[(int) date('w', strtotime($date))],
                'glasses' => $r['glasses'],
                'breaks' => $r['breaks'],
                'goal_met' => (bool) $r['goal_met'],
                'is_today' => $date === $today,
            ];
        }

        $glasses = array_sum(array_column($days, 'glasses'));
        $breaks = array_sum(array_column($days, 'breaks'));
        $goalDays = count(array_filter($days, fn ($d) => $d['goal_met']));
        $avg = round($glasses / 7, 1);

        $prevGlasses = 0;
        foreach ($prevDates as $date) {
            $prevGlasses += $rows[$date]['glasses'] ?? 0;
        }
        $changePct = $prevGlasses > 0 ? (int) round(($glasses - $prevGlasses) / $prevGlasses * 100) : null;

        $best = null;
        foreach ($days as $d) {
            if ($d['glasses'] > 0 && ($best === null || $d['glasses'] > $best['glasses'])) {
                $best = $d;
            }
        }

        // Kalimat umpan balik
        $insights = [];

        if ($goalDays === 7) {
            $insights[] = 'Sempurna! Target minum tercapai setiap hari minggu ini.';
        } elseif ($goalDays >= 4) {
            $insights[] = "Target minum tercapai $goalDays dari 7 hari. Pertahankan!";
        } else {
            $insights[] = "Target minum tercapai $goalDays dari 7 hari. Coba mulai dengan segelas air setiap bangun tidur.";
        }

        if ($changePct === null) {
            $insights[] = 'Belum ada data 7 hari sebelumnya untuk dibandingkan.';
        } elseif ($changePct > 0) {
            $insights[] = "Jumlah gelas naik $changePct% dibanding 7 hari sebelumnya.";
        } elseif ($changePct < 0) {
            $insights[] = 'Jumlah gelas turun '.abs($changePct).'% dibanding 7 hari sebelumnya.';
        } else {
            $insights[] = 'Jumlah gelas sama dengan 7 hari sebelumnya.';
        }

        $insights[] = $avg >= $target
            ? 'Rata-rata '.self::num($avg)." gelas sehari, sesuai target $target gelas."
            : 'Rata-rata '.self::num($avg).' gelas sehari, '.self::num($target - $avg)." gelas di bawah target $target gelas.";

        if ($best) {
            $insights[] = "Hari terbaik: {$best['name']} dengan {$best['glasses']} gelas.";
        }

        $insights[] = $breaks === 0
            ? 'Belum ada jeda aktif minggu ini. Coba satu kali saat sedang lama di depan laptop.'
            : "$breaks jeda aktif minggu ini. Badanmu berterima kasih.";

        return [
            'days' => $days,
            'target' => $target,
            'scale' => max($target, max(array_column($days, 'glasses'))),   // tinggi maksimum grafik
            'totals' => [
                'glasses' => $glasses,
                'avg' => $avg,
                'goal_days' => $goalDays,
                'breaks' => $breaks,
                'xp' => $xpWeek,
            ],
            'prev_glasses' => $prevGlasses,
            'change_pct' => $changePct,
            'insights' => $insights,
        ];
    }

    /**
     * Angka desimal gaya Indonesia: 6.5 -> "6,5", 7.0 -> "7".
     */
    public static function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
    }
}
