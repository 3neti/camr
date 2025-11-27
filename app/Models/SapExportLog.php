<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapExportLog extends Model
{
    protected $fillable = [
        'site_id',
        'business_entity',
        'company_code',
        'cut_off_date',
        'file_name',
        'file_path',
        'status',
        'total_meters',
        'exported_meters',
        'skipped_meters',
        'validation_summary',
        'errors',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'cut_off_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
