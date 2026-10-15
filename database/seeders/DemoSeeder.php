<?php

namespace Database\Seeders;

use App\Models\DailyStat;
use App\Models\Squad;
use App\Models\SquadMember;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\XpTransaction;
use App\Services\BadgeService;
use App\Services\MissionService;
use App\Services\SquadService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Akun contoh untuk demo: Raka dengan riwayat minum 7 hari.
 *
 * Jalankan:  php artisan db:seed --class=DemoSeeder
 * Login:     raka@tumbuh.test  /  password   (teman squad: nisa@, fajar@, dinda@tumbuh.test)
 *
 * Aman dijalankan berulang kali: akun demo lama dihapus dulu lalu dibuat ulang.
 */
class DemoSeeder extends Seeder
{
    private const EMAIL = 'raka@tumbuh.test';

    // Teman satu squad untuk demo leaderboard: nama => gelas per hari (indeks 0 = Senin)
    private const FRIENDS = [
        'Nisa' => [9, 8, 10, 9, 8, 9, 7],
        'Fajar' => [6, 7, 5, 8, 6, 7, 4],
        'Dinda' => [8, 8, 7, 8, 9, 8, 5],
    ];

    private const SQUAD_CODE = 'TUMBUH';

    // Gelas per hari, dari 6 hari lalu sampai hari ini (elemen terakhir = hari ini)
    private const GLASSES = [8, 6, 9, 8, 5, 8, 3];

    // XP akhir yang diinginkan: 10 XP lagi menuju Level 4 (ambang 750),
    // supaya satu klik "Catat 1 Gelas" saat demo langsung memunculkan naik level.
    private const TARGET_XP = 740;

    public function run(): void
    {
        DB::transaction(function () {
            // Hapus akun demo lama. Data terkait ikut terhapus (cascadeOnDelete).
            User::where('email', self::EMAIL)->delete();
            foreach (array_keys(self::FRIENDS) as $name) {
                User::where('email', strtolower($name).'@tumbuh.test')->delete();
            }
            Squad::where('invite_code', self::SQUAD_CODE)->delete();

            $user = User::create([
                'name' => 'Raka Pratama',
                'email' => self::EMAIL,
                'password' => Hash::make('password'),
                'water_target' => 8,
                'reminder_enabled' => true,
                'walk_reminder_enabled' => true,
            ]);

            $xpPerGlass = config('gamification.xp.water');
            $totalXp = 0;
            $days = count(self::GLASSES);

            foreach (self::GLASSES as $i => $glasses) {
                $daysAgo = $days - 1 - $i;
                $date = today()->subDays($daysAgo);
                $isToday = $daysAgo === 0;

                for ($g = 0; $g < $glasses; $g++) {
                    // Hari lalu: gelas tersebar tiap 90 menit mulai 07.00.
                    // Hari ini: gelas terakhir 30 menit lalu, sebelumnya berjarak 60 menit.
                    $loggedAt = $isToday
                        ? now()->subMinutes(30 + ($glasses - 1 - $g) * 60)
                        : $date->copy()->setTime(7, 0)->addMinutes($g * 90);

                    // Jika seeder dijalankan dini hari, jangan sampai gelas "hari ini" jatuh ke kemarin
                    if ($isToday && $loggedAt->lt(today())) {
                        $loggedAt = today()->addMinutes($g + 1);
                    }

                    WaterLog::create([
                        'user_id' => $user->id,
                        'logged_at' => $loggedAt,
                        'xp' => $xpPerGlass,
                    ]);

                    $this->xpRow($user, $xpPerGlass, 'water', 'Minum 1 gelas', $loggedAt);
                    $totalXp += $xpPerGlass;
                }

                DailyStat::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'glasses' => $glasses,
                    'breaks' => 0,
                    'goal_met' => $glasses >= $user->water_target,
                ]);
            }

            // Kolom di luar $fillable diisi secara eksplisit
            $user->xp = $totalXp;
            $user->current_streak = 1;                      // kemarin mencapai target
            $user->longest_streak = 2;                      // hari ke-3 dan ke-4 berturut-turut
            $user->streak_counted_date = today()->subDay(); // kemarin sudah dihitung ke streak
            $user->streak_freezes = 1;                      // punya 1 pelindung untuk diperlihatkan
            $user->last_active_date = today();
            $user->onboarded_at = now()->subDays($days - 1);
            $user->save();

            // Squad demo "Anak TI 23": Raka + 3 teman dengan data minggu ini
            $this->seedSquad($user);

            // Buat misi hari ini sekarang. Misi yang sudah terpenuhi (misalnya "Catat 3 gelas")
            // langsung selesai dan memberi XP di sini, bukan saat beranda pertama dibuka.
            app(MissionService::class)->refresh($user);
            $user->refresh();

            // Bonus supaya total XP tepat di TARGET_XP (10 XP sebelum naik ke Level 4)
            $bonus = self::TARGET_XP - $user->xp;
            if ($bonus > 0) {
                $this->xpRow($user, $bonus, 'bonus', 'Bonus akun demo', today()->subDays($days - 1)->setTime(6, 0));
                $user->xp = self::TARGET_XP;
                $user->save();
            }
            $totalXp = $user->xp;

            // Lencana yang syaratnya sudah terpenuhi dari data 7 hari
            app(BadgeService::class)->check($user);

            $this->command?->info("Akun demo siap: ".self::EMAIL." / password ({$totalXp} XP)");
        });
    }

    /**
     * Buat squad demo berisi Raka dan teman-temannya, lengkap dengan gelas minggu ini.
     */
    private function seedSquad(User $raka): void
    {
        $squad = Squad::create(['name' => 'Anak TI 23', 'invite_code' => self::SQUAD_CODE, 'owner_id' => $raka->id]);
        SquadMember::create(['squad_id' => $squad->id, 'user_id' => $raka->id, 'joined_at' => now()->subDays(6)]);

        $weekStart = app(SquadService::class)->weekStart();
        $xpPerGlass = config('gamification.xp.water');

        foreach (self::FRIENDS as $name => $pattern) {
            $friend = User::create([
                'name' => $name,
                'email' => strtolower($name).'@tumbuh.test',
                'password' => Hash::make('password'),
                'water_target' => 8,
            ]);
            $totalXp = 0;

            // Setiap hari dari Senin sampai hari ini
            for ($day = $weekStart->copy(); $day->lte(today()); $day->addDay()) {
                $glasses = $pattern[(int) round($weekStart->diffInDays($day)) % 7];
                if ($day->isToday()) {
                    $glasses = (int) ceil($glasses / 2);   // hari ini baru setengah jalan
                }

                for ($g = 0; $g < $glasses; $g++) {
                    $at = $day->isToday()
                        ? now()->subMinutes(20 + $g * 70)
                        : $day->copy()->setTime(7, 30)->addMinutes($g * 80);
                    if ($at->lt($day->copy()->startOfDay())) {
                        $at = $day->copy()->startOfDay()->addMinutes($g + 1);
                    }

                    WaterLog::create(['user_id' => $friend->id, 'logged_at' => $at, 'xp' => $xpPerGlass]);
                    $this->xpRow($friend, $xpPerGlass, 'water', 'Minum 1 gelas', $at);
                    $totalXp += $xpPerGlass;
                }

                DailyStat::create([
                    'user_id' => $friend->id,
                    'date' => $day->toDateString(),
                    'glasses' => $glasses,
                    'breaks' => 0,
                    'goal_met' => $glasses >= 8,
                ]);
            }

            $friend->xp = $totalXp;
            $friend->onboarded_at = now()->subDays(7);
            $friend->save();

            SquadMember::create(['squad_id' => $squad->id, 'user_id' => $friend->id, 'joined_at' => now()->subDays(5)]);
        }

        app(SquadService::class)->refreshChallenge($squad);
    }

    /**
     * Catat satu baris riwayat XP dengan tanggal yang sesuai (bukan tanggal hari ini).
     */
    private function xpRow(User $user, int $amount, string $source, string $description, $at): void
    {
        $row = new XpTransaction([
            'user_id' => $user->id,
            'amount' => $amount,
            'source' => $source,
            'description' => $description,
        ]);
        $row->created_at = $at;
        $row->updated_at = $at;
        $row->save();
    }
}
