<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantImport extends Model
{
    protected $guarded = [];

    protected $casts = ['payload' => 'array', 'imported_at' => 'datetime'];
}
