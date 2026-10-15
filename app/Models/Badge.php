<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = ['code', 'name', 'description', 'rule_type', 'rule_value', 'sort'];

    protected function casts(): array
    {
        return [
            'rule_value' => 'integer',
            'sort' => 'integer',
        ];
    }
}
