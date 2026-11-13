<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $guarded = [];

    protected $casts = ['answered_at' => 'datetime', 'selected_option_ids' => 'array', 'matrix_answers' => 'array'];
}
