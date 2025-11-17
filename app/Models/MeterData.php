<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterData extends Model
{
    protected $fillable = [
        'location',
        'meter_name',
        'reading_datetime',
        'vrms_a', 'vrms_b', 'vrms_c',
        'irms_a', 'irms_b', 'irms_c',
        'frequency', 'power_factor', 'watt', 'va', 'var',
        'wh_delivered', 'wh_received', 'wh_net', 'wh_total',
        'varh_negative', 'varh_positive', 'varh_net', 'varh_total',
        'vah_total',
        'max_rec_kw_demand', 'max_rec_kw_demand_time',
        'max_del_kw_demand', 'max_del_kw_demand_time',
        'max_pos_kvar_demand', 'max_pos_kvar_demand_time',
        'max_neg_kvar_demand', 'max_neg_kvar_demand_time',
        'v_phase_angle_a', 'v_phase_angle_b', 'v_phase_angle_c',
        'i_phase_angle_a', 'i_phase_angle_b', 'i_phase_angle_c',
        'mac_address', 'software_version', 'relay_status', 'genset_status',
    ];

    protected $casts = [
        'reading_datetime' => 'datetime',
        'max_rec_kw_demand_time' => 'datetime',
        'max_del_kw_demand_time' => 'datetime',
        'max_pos_kvar_demand_time' => 'datetime',
        'max_neg_kvar_demand_time' => 'datetime',
        'relay_status' => 'boolean',
        'genset_status' => 'boolean',
    ];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class, 'meter_name', 'name');
    }
}
