<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveBreak extends Model
{
    protected $fillable = ['user_id', 'done_at', 'xp'];

    protected function casts(): array
    {
        return [
            'done_at' => 'datetime',
            'xp' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
