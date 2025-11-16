<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meter extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'gateway_id', 'location_id', 'building_id', 'configuration_file_id',
        'site_code', 'name', 'is_addressable', 'has_load_profile', 'default_name',
        'type', 'brand', 'role', 'remarks', 'customer_name', 'multiplier', 'status',
        'last_log_update', 'software_version', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_addressable' => 'boolean',
        'has_load_profile' => 'boolean',
        'multiplier' => 'decimal:2',
        'last_log_update' => 'datetime',
    ];

    protected $appends = ['status_label'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function configurationFile(): BelongsTo
    {
        return $this->belongsTo(ConfigurationFile::class);
    }

    public function meterData(): HasMany
    {
        return $this->hasMany(MeterData::class, 'meter_name', 'name');
    }

    public function loadProfiles(): HasMany
    {
        return $this->hasMany(LoadProfile::class, 'meter_name', 'name');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'Inactive');
    }

    // Status label attribute
    public function getStatusLabelAttribute(): string
    {
        if (!$this->last_log_update) {
            return 'No Data';
        }

        return $this->last_log_update->isAfter(now()->subDay()) ? 'Online' : 'Offline';
    }
}
