<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SquadChallenge extends Model
{
    protected $fillable = ['squad_id', 'week_start', 'target_glasses', 'completed_at'];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'target_glasses' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function squad(): BelongsTo
    {
        return $this->belongsTo(Squad::class);
    }
}
