<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
       protected $fillable = [
        'patient_id',
        'start_datetime',
        'end_datetime',
        'first_time',
        'observations',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
        'first_time'     => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }
}
