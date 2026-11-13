<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $guarded = [];

    protected $casts = ['consent' => 'boolean', 'birth_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }
}
