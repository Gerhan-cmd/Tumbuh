<?php

namespace App\Services;

use App\Models\DailyStat;
use App\Models\Squad;
use App\Models\SquadChallenge;
use App\Models\SquadMember;
use App\Models\User;
use App\Models\XpTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Squad: kelompok teman, leaderboard XP mingguan, dan tantangan bersama.
 *
 * - Satu pengguna hanya bisa berada di satu squad, maksimal 8 anggota.
 * - Minggu dihitung Senin sampai Minggu. Leaderboard dan tantangan mulai dari nol setiap Senin.
 * - Tantangan: total gelas semua anggota minggu ini harus mencapai
 *   (jumlah target harian semua anggota) x 5 hari. Saat tercapai, SETIAP anggota mendapat bonus XP.
 */
class SquadService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';   // tanpa O/0, I/1, L yang mudah tertukar

    public function __construct(private GamificationService $game)
    {
    }

    public function squadOf(User $user): ?Squad
    {
        return SquadMember::where('user_id', $user->id)->first()?->squad;
    }

    public function create(User $user, string $name): Squad
    {
        $squad = Squad::create([
            'name' => $name,
            'invite_code' => $this->uniqueCode(),
            'owner_id' => $user->id,
        ]);

        SquadMember::create(['squad_id' => $squad->id, 'user_id' => $user->id, 'joined_at' => now()]);

        return $squad;
    }

    public function join(User $user, Squad $squad): void
    {
        SquadMember::create(['squad_id' => $squad->id, 'user_id' => $user->id, 'joined_at' => now()]);
    }

    /**
     * Keluar dari squad. Kalau pemiliknya keluar, kepemilikan pindah ke anggota terlama.
     * Kalau anggota terakhir keluar, squad dihapus.
     */
    public function leave(User $user): void
    {
        $membership = SquadMember::where('user_id', $user->id)->first();
        if (! $membership) {
            return;
        }

        $squad = $membership->squad;
        $membership->delete();

        $next = $squad->members()->orderBy('joined_at')->first();

        if (! $next) {
            $squad->delete();
        } elseif ($squad->owner_id === $user->id) {
            $squad->owner_id = $next->user_id;
            $squad->save();
        }
    }

    public function isFull(Squad $squad): bool
    {
        return $squad->members()->count() >= config('gamification.squad.max_members');
    }

    public function weekStart(): Carbon
    {
        return today()->startOfWeek(CarbonInterface::MONDAY);
    }

    /**
     * Perbarui tantangan minggu ini. Kalau baru saja tercapai, semua anggota mendapat bonus XP.
     *
     * @return bool  true jika tantangan baru saja selesai pada pemanggilan ini
     */
    public function refreshChallenge(Squad $squad): bool
    {
        $challenge = SquadChallenge::firstOrCreate(
            ['squad_id' => $squad->id, 'week_start' => $this->weekStart()->toDateString()],
            ['target_glasses' => 0],
        );

        if ($challenge->completed_at) {
            return false;
        }

        $members = $squad->members()->with('user')->get();
        $challenge->target_glasses = self::challengeTarget(
            $members->map(fn (SquadMember $m) => $m->user->water_target)->all(),
            config('gamification.squad.challenge_days'),
        );

        $progress = $this->weekGlasses($members->pluck('user_id')->all());
        $completedNow = $challenge->target_glasses > 0 && $progress >= $challenge->target_glasses;

        if ($completedNow) {
            $challenge->completed_at = now();
            $xp = config('gamification.squad.challenge_xp');
            foreach ($members as $m) {
                $this->game->award($m->user, $xp, 'squad', "Tantangan squad {$squad->name}");
            }
        }

        $challenge->save();

        return $completedNow;
    }

    /**
     * Data lengkap squad untuk halaman Squad.
     */
    public function state(Squad $squad, User $viewer): array
    {
        $members = $squad->members()->with('user')->get();
        $ids = $members->pluck('user_id')->all();
        $since = $this->weekStart();

        $xp = XpTransaction::whereIn('user_id', $ids)
            ->where('created_at', '>=', $since)
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        $glasses = DailyStat::whereIn('user_id', $ids)
            ->where('date', '>=', $since->toDateString())
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(glasses) as total')
            ->pluck('total', 'user_id');

        $rows = $members->map(fn (SquadMember $m) => [
            'name' => $m->user->name,
            'xp' => (int) ($xp[$m->user_id] ?? 0),
            'glasses' => (int) ($glasses[$m->user_id] ?? 0),
            'is_me' => $m->user_id === $viewer->id,
            'is_owner' => $m->user_id === $squad->owner_id,
        ])->all();

        $challenge = SquadChallenge::where('squad_id', $squad->id)
            ->whereDate('week_start', $since)
            ->first();

        return [
            'name' => $squad->name,
            'invite_code' => $squad->invite_code,
            'member_count' => count($rows),
            'max_members' => config('gamification.squad.max_members'),
            'is_owner' => $squad->owner_id === $viewer->id,
            'leaderboard' => self::rank($rows),
            'challenge' => [
                'target' => $challenge?->target_glasses ?? 0,
                'progress' => array_sum(array_column($rows, 'glasses')),
                'completed' => (bool) $challenge?->completed_at,
                'xp' => config('gamification.squad.challenge_xp'),
                'days_left' => self::daysLeft($since->toDateString(), today()->toDateString()),
            ],
        ];
    }

    /**
     * Versi ringkas untuk kartu di beranda.
     */
    public function summary(Squad $squad, User $viewer): array
    {
        $full = $this->state($squad, $viewer);
        $me = collect($full['leaderboard'])->firstWhere('is_me', true);

        return [
            'name' => $full['name'],
            'challenge' => $full['challenge'],
            'my_rank' => $me['rank'] ?? null,
            'member_count' => $full['member_count'],
        ];
    }

    private function weekGlasses(array $userIds): int
    {
        return (int) DailyStat::whereIn('user_id', $userIds)
            ->where('date', '>=', $this->weekStart()->toDateString())
            ->sum('glasses');
    }

    private function uniqueCode(): string
    {
        do {
            $code = self::makeCode(6, fn (int $max) => random_int(0, $max));
        } while (Squad::where('invite_code', $code)->exists());

        return $code;
    }

    // ------------------------------------------------------------------
    // Fungsi murni (tanpa database) — mudah diuji
    // ------------------------------------------------------------------

    /**
     * @param  list<int>  $waterTargets  target harian tiap anggota
     */
    public static function challengeTarget(array $waterTargets, int $days): int
    {
        return array_sum($waterTargets) * $days;
    }

    /**
     * Urutkan leaderboard: XP terbanyak di atas, lalu gelas terbanyak, lalu nama.
     * Nilai sama mendapat peringkat sama (contoh: 1, 2, 2, 4).
     *
     * @param  list<array{name:string,xp:int,glasses:int}>  $rows
     */
    public static function rank(array $rows): array
    {
        usort($rows, fn ($a, $b) => [$b['xp'], $b['glasses'], $a['name']] <=> [$a['xp'], $a['glasses'], $b['name']]);

        $rank = 0;
        $prev = null;
        foreach ($rows as $i => &$row) {
            $key = [$row['xp'], $row['glasses']];
            if ($key !== $prev) {
                $rank = $i + 1;
                $prev = $key;
            }
            $row['rank'] = $rank;
        }
        unset($row);

        return $rows;
    }

    /**
     * Sisa hari minggu ini, termasuk hari ini (Senin = 7, Minggu = 1).
     */
    public static function daysLeft(string $weekStart, string $today): int
    {
        $passed = (int) round((strtotime($today) - strtotime($weekStart)) / 86400);

        return max(1, 7 - $passed);
    }

    /**
     * @param  callable(int):int  $rand  pengacak: menerima batas atas, mengembalikan 0..batas
     */
    public static function makeCode(int $length, callable $rand): string
    {
        $alphabet = self::CODE_ALPHABET;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[$rand(strlen($alphabet) - 1)];
        }

        return $code;
    }
}
