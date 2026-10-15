<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStat extends Model
{
    protected $fillable = ['user_id', 'date', 'glasses', 'breaks', 'goal_met'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'glasses' => 'integer',
            'breaks' => 'integer',
            'goal_met' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
