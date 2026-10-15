<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Laporan metrik uji coba, sesuai rencana pengujian di tugas Gamification Design:
 * rata-rata gelas dan jeda per hari, persentase hari target tercapai, retensi hari ke-7 dan ke-14,
 * dan persentase misi yang diselesaikan.
 *
 * Contoh:
 *   php artisan tumbuh:report
 *   php artisan tumbuh:report --from=2026-11-03 --to=2026-11-17
 *   php artisan tumbuh:report --csv=storage/app/laporan-uji-coba.csv
 */
class TumbuhReport extends Command
{
    protected $signature = 'tumbuh:report
                            {--from= : Tanggal mulai (YYYY-MM-DD). Bawaan: 14 hari lalu}
                            {--to= : Tanggal akhir (YYYY-MM-DD). Bawaan: hari ini}
                            {--csv= : Simpan data per peserta ke file CSV}
                            {--with-demo : Ikut sertakan akun demo (@tumbuh.test)}';

    protected $description = 'Hitung metrik uji coba Tumbuh untuk laporan';

    public function handle(): int
    {
        $from = Carbon::parse($this->option('from') ?? today()->subDays(13))->startOfDay();
        $to = Carbon::parse($this->option('to') ?? today())->startOfDay();
        $days = (int) $from->diffInDays($to) + 1;

        $users = User::whereNotNull('onboarded_at')
            ->when(! $this->option('with-demo'), fn ($q) => $q->where('email', 'not like', '%@tumbuh.test'))
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('Belum ada peserta yang menyelesaikan onboarding.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($users as $u) {
            $stats = DB::table('daily_stats')->where('user_id', $u->id)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->get();

            $activeDates = DB::table('water_logs')->where('user_id', $u->id)
                ->whereBetween('logged_at', [$from, $to->copy()->endOfDay()])
                ->selectRaw('DATE(logged_at) as d')->distinct()->pluck('d')->all();

            $missions = DB::table('user_missions')->where('user_id', $u->id)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
            $missionTotal = (clone $missions)->count();
            $missionDone = (clone $missions)->whereNotNull('completed_at')->count();

            // Retensi: masih mencatat gelas pada hari ke-7 / ke-14 (atau sesudahnya) sejak onboarding
            $start = Carbon::parse($u->onboarded_at)->startOfDay();
            $retained = fn (int $n) => collect($activeDates)->contains(fn ($d) => Carbon::parse($d)->gte($start->copy()->addDays($n - 1)));

            $rows[] = [
                'peserta' => $u->name,
                'hari_aktif' => count($activeDates),
                'gelas_per_hari' => round($stats->sum('glasses') / $days, 1),
                'jeda_per_hari' => round($stats->sum('breaks') / $days, 1),
                'hari_target' => $stats->where('goal_met', true)->count(),
                'misi_selesai_pct' => $missionTotal ? (int) round($missionDone / $missionTotal * 100) : 0,
                'retensi_h7' => $retained(7) ? 'ya' : 'tidak',
                'retensi_h14' => $retained(14) ? 'ya' : 'tidak',
                'level' => app(\App\Services\GamificationService::class)->levelOf($u),
                'streak_terpanjang' => $u->longest_streak,
            ];
        }

        $this->info("Laporan uji coba Tumbuh: {$from->format('d/m/Y')} - {$to->format('d/m/Y')} ({$days} hari, ".count($rows).' peserta)');
        $this->table(array_keys($rows[0]), $rows);

        $n = count($rows);
        $avg = fn (string $key) => round(array_sum(array_column($rows, $key)) / $n, 1);
        $pct = fn (string $key) => (int) round(count(array_filter($rows, fn ($r) => $r[$key] === 'ya')) / $n * 100);

        $this->newLine();
        $this->line('Ringkasan semua peserta:');
        $this->table(['Metrik', 'Nilai'], [
            ['Rata-rata gelas per hari', $avg('gelas_per_hari')],
            ['Rata-rata jeda aktif per hari', $avg('jeda_per_hari')],
            ['Rata-rata hari target tercapai', $avg('hari_target').' dari '.$days.' hari'],
            ['Rata-rata misi diselesaikan', $avg('misi_selesai_pct').'%'],
            ['Retensi hari ke-7', $pct('retensi_h7').'%'],
            ['Retensi hari ke-14', $pct('retensi_h14').'%'],
        ]);

        if ($path = $this->option('csv')) {
            $fh = fopen(base_path($path), 'w');
            fputcsv($fh, array_keys($rows[0]));
            foreach ($rows as $r) {
                fputcsv($fh, $r);
            }
            fclose($fh);
            $this->info("Data per peserta disimpan ke {$path}");
        }

        return self::SUCCESS;
    }
}
