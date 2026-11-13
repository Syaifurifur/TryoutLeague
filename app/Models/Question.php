<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = [];

    protected $casts = ['tags' => 'array', 'matrix_rows' => 'array', 'reviewed_at' => 'datetime'];

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
