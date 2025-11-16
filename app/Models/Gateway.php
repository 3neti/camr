<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'location_id', 'site_code', 'serial_number', 'mac_address',
        'ip_address', 'connection_type', 'ip_netmask', 'ip_gateway', 'server_ip',
        'description', 'update_csv', 'update_site_code', 'ssh_enabled',
        'force_load_profile', 'idf_number', 'switch_name', 'idf_port',
        'last_log_update', 'software_version', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'update_csv' => 'boolean',
        'update_site_code' => 'boolean',
        'ssh_enabled' => 'boolean',
        'force_load_profile' => 'boolean',
        'last_log_update' => 'datetime',
    ];

    protected $appends = ['status'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
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
    public function scopeOnline($query)
    {
        return $query->where('last_log_update', '>=', now()->subDay());
    }

    public function scopeOffline($query)
    {
        return $query->where(function ($q) {
            $q->where('last_log_update', '<', now()->subDay())
              ->orWhereNull('last_log_update');
        });
    }

    // Status attribute
    public function getStatusAttribute(): string
    {
        if (!$this->last_log_update) {
            return 'No Data';
        }

        return $this->last_log_update->isAfter(now()->subDay()) ? 'Online' : 'Offline';
    }
}
