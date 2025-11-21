<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'file_path',
        'status',
        'progress',
        'statistics',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'progress' => 'array',
        'statistics' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who initiated the import
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if import is complete
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if import is in progress
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['uploading', 'queued', 'processing']);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): int
    {
        if (! $this->progress || ! isset($this->progress['total'])) {
            return 0;
        }

        $total = $this->progress['total'] ?? 1;
        $imported = $this->progress['imported'] ?? 0;

        return (int) round(($imported / $total) * 100);
    }

    /**
     * Get human-readable duration
     */
    public function getDuration(): ?string
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        $seconds = $this->started_at->diffInSeconds($end);

        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = intdiv($seconds, 60);

            return "{$minutes}m ".($seconds % 60).'s';
        } else {
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);

            return "{$hours}h {$minutes}m";
        }
    }

    /**
     * Update progress
     */
    public function updateProgress(int $imported, int $total, int $errors = 0): void
    {
        $this->update([
            'progress' => [
                'imported' => $imported,
                'total' => $total,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(array $statistics): void
    {
        $this->update([
            'status' => 'completed',
            'statistics' => $statistics,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}
