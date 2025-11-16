<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'division_id',
        'primary_building_id',
        'code',
        'last_log_update',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_log_update' => 'datetime',
    ];

    protected $appends = ['status'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function primaryBuilding(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'primary_building_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function gateways(): HasMany
    {
        return $this->hasMany(Gateway::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('expires_at')
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes for online/offline status
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
