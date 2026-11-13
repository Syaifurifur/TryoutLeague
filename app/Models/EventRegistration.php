<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    protected $guarded = [];

    protected $casts = ['verified_at' => 'datetime', 'checked_in_at' => 'datetime'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class, 'registration_id');
    }
}
