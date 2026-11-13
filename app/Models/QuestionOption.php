<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['is_correct'];
}
