<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chest extends Model
{
    protected $fillable = ['user_id', 'date', 'reward_type', 'reward_value', 'opened_at'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reward_value' => 'integer',
            'opened_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
