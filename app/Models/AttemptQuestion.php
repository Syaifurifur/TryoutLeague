<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptQuestion extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['option_order' => 'array', 'question_snapshot' => 'array', 'is_flagged' => 'boolean'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->hasOne(Answer::class);
    }
}
