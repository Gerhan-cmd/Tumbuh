<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi lewat form (mass assignment).
     * xp, streak, dan freeze sengaja TIDAK dimasukkan:
     * nilainya hanya diubah oleh service game, bukan oleh input pengguna.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'water_target',
        'reminder_enabled',
        'walk_reminder_enabled',
        'plant_type',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'water_target' => 'integer',
            'reminder_enabled' => 'boolean',
            'walk_reminder_enabled' => 'boolean',
            'xp' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'streak_freezes' => 'integer',
            'streak_counted_date' => 'date',
            'last_active_date' => 'date',
            'onboarded_at' => 'datetime',
        ];
    }

    /**
     * Sudah menyelesaikan onboarding (atur target & pengingat)?
     */
    public function hasOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    // ---------- Relasi ----------

    public function waterLogs(): HasMany
    {
        return $this->hasMany(WaterLog::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(DailyStat::class);
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function activeBreaks(): HasMany
    {
        return $this->hasMany(ActiveBreak::class);
    }

    public function userMissions(): HasMany
    {
        return $this->hasMany(UserMission::class);
    }

    public function chests(): HasMany
    {
        return $this->hasMany(Chest::class);
    }

    /**
     * Keanggotaan squad (paling banyak satu).
     */
    public function squadMembership(): HasOne
    {
        return $this->hasOne(SquadMember::class);
    }
}
