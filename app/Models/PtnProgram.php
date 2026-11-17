<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtnProgram extends Model
{
    protected $guarded = [];

    protected $casts = [
        'passing_score' => 'float',
    ];
}
