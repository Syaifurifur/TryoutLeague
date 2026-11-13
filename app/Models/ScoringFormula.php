<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringFormula extends Model
{
    protected $guarded = [];

    protected $casts = ['partial_scoring' => 'boolean'];
}
