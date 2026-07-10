<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleOccurrence extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected $casts = [
        'starts_at' => 'immutable_datetime',
        'ends_at'   => 'immutable_datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
