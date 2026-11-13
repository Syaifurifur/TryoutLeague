<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $guarded = [];

    protected $casts = ['started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime', 'last_activity_at' => 'datetime', 'last_autosave_at' => 'datetime', 'auto_submitted' => 'boolean', 'ip_history' => 'array'];

    public function registration()
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function questions()
    {
        return $this->hasMany(AttemptQuestion::class, 'attempt_id')->orderBy('position');
    }

    public function score()
    {
        return $this->hasOne(Score::class, 'attempt_id');
    }

    public function scoringFormula()
    {
        return $this->belongsTo(ScoringFormula::class);
    }
}
