<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];

    protected $casts = ['registration_open_at' => 'datetime', 'registration_close_at' => 'datetime', 'subject_selection_open_at' => 'datetime', 'subject_selection_close_at' => 'datetime', 'event_start_at' => 'datetime', 'event_end_at' => 'datetime', 'result_published_at' => 'datetime', 'allow_individual_registration' => 'boolean', 'allow_school_registration' => 'boolean', 'settings' => 'array'];
}
