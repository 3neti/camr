<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapImportLog extends Model
{
    protected $fillable = [
        'import_type',
        'file_name',
        'file_path',
        'source',
        'status',
        'total_rows',
        'processed_rows',
        'inserted_rows',
        'updated_rows',
        'skipped_rows',
        'error_rows',
        'errors',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
