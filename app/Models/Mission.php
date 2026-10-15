<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    protected $fillable = ['code', 'title', 'type', 'target', 'before_hour', 'xp_reward'];

    protected function casts(): array
    {
        return [
            'target' => 'integer',
            'before_hour' => 'integer',
            'xp_reward' => 'integer',
        ];
    }
}
