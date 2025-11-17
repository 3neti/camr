<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadProfile extends Model
{
    protected $fillable = [
        'meter_name',
        'reading_datetime',
        'event_id',
        'channel_1',
        'channel_2',
        'channel_3',
        'channel_4',
        'channel_5',
        'channel_6',
        'channel_7',
        'channel_8',
    ];

    protected $casts = [
        'reading_datetime' => 'datetime',
    ];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class, 'meter_name', 'name');
    }
}
