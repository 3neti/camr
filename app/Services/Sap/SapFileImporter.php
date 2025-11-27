<?php

namespace App\Services\Sap;

use App\Models\SapImportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class SapFileImporter
{
    protected string $importType;
    protected array $config;
    protected ?SapImportLog $log = null;
    protected int $processedRows = 0;
    protected int $insertedRows = 0;
    protected int $updatedRows = 0;
    protected int $skippedRows = 0;
    protected int $errorRows = 0;
    protected array $errors = [];

    public function __construct()
    {
        $this->config = config('sap');
    }

    /**
     * Main import execution method
     */
    public function import(): array
    {
        $results = [
            'success' => false,
            'files_processed' => 0,
            'total_rows' => 0,
            'errors' => [],
        ];

        if (!$this->isEnabled()) {
            $results['errors'][] = "Import for {$this->importType} is disabled";
            return $results;
        }

        $files = $this->detectFiles();
        
        if (empty($files)) {
            Log::info("No files found for {$this->importType} import");
            return $results;
        }

        foreach ($files as $file) {
            if ($this->isLocked()) {
                Log::warning("{$this->importType} import locked, skipping");
                continue;
            }

            $this->lock();
            
            try {
                $this->processFile($file);
                $results['files_processed']++;
                $results['success'] = true;
            } catch (\Exception $e) {
                Log::error("Error processing {$file['path']}: " . $e->getMessage());
                $results['errors'][] = $e->getMessage();
            } finally {
                $this->unlock();
            }
        }

        return $results;
    }

    /**
     * Detect files in configured directories
     */
    protected function detectFiles(): array
    {
        $files = [];
        $typeConfig = $this->getTypeConfig();
        $basePath = $this->config['import']['base_path'];
        
        // Check SEP folder first if configured
        if ($this->config['import']['check_sep_first'] && isset($typeConfig['sep_path'])) {
            $sepPath = $basePath . '/' . $typeConfig['sep_path'];
            if ($this->isDirEmpty($sepPath)) {
                // SEP is empty, check main path
                $mainPath = $basePath . '/' . $typeConfig['path'];
                $files = $this->scanDirectory($mainPath, 'SAP');
            } else {
                // Use SEP files
                $files = $this->scanDirectory($sepPath, 'SEP');
            }
        } else {
            // Only check main path
            $mainPath = $basePath . '/' . $typeConfig['path'];
            $files = $this->scanDirectory($mainPath, 'SAP');
        }

        return $files;
    }

    /**
     * Scan directory for CSV files
     */
    protected function scanDirectory(string $directory, string $source): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $pattern = $this->config['import']['file_pattern'];
        $files = [];
        
        foreach (glob($directory . '/' . $pattern, GLOB_BRACE) as $filepath) {
            if (is_file($filepath)) {
                $files[] = [
                    'path' => $filepath,
                    'name' => basename($filepath),
                    'source' => $source,
                ];
            }
        }

        return $files;
    }

    /**
     * Check if directory is empty
     */
    protected function isDirEmpty(string $dir): bool
    {
        if (!is_readable($dir)) {
            return true;
        }
        
        return count(scandir($dir)) === 2; // Only . and ..
    }

    /**
     * Process a single file
     */
    protected function processFile(array $file): void
    {
        $startTime = now();
        
        // Create import log
        $this->log = SapImportLog::create([
            'import_type' => $this->importType,
            'file_name' => $file['name'],
            'file_path' => $file['path'],
            'source' => $file['source'],
            'status' => 'processing',
            'started_at' => $startTime,
        ]);

        // Reset counters
        $this->processedRows = 0;
        $this->insertedRows = 0;
        $this->updatedRows = 0;
        $this->skippedRows = 0;
        $this->errorRows = 0;
        $this->errors = [];

        try {
            $lines = file($file['path'], FILE_IGNORE_NEW_LINES);
            $totalRows = count($lines);

            Log::info("Processing {$file['name']}: {$totalRows} rows");

            DB::transaction(function () use ($lines) {
                foreach ($lines as $index => $line) {
                    try {
                        $this->processRow($line, $index);
                        $this->processedRows++;
                    } catch (\Exception $e) {
                        $this->errorRows++;
                        $this->errors[] = "Line " . ($index + 1) . ": " . $e->getMessage();
                        Log::error("Error on line {$index}: " . $e->getMessage());
                    }
                }
            });

            // Archive the file
            $this->archiveFile($file);

            // Update log
            $this->log->update([
                'status' => $this->errorRows > 0 ? 'partial' : 'success',
                'total_rows' => $totalRows,
                'processed_rows' => $this->processedRows,
                'inserted_rows' => $this->insertedRows,
                'updated_rows' => $this->updatedRows,
                'skipped_rows' => $this->skippedRows,
                'error_rows' => $this->errorRows,
                'errors' => !empty($this->errors) ? json_encode($this->errors) : null,
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($startTime),
            ]);

            Log::info("Completed {$file['name']}: {$this->insertedRows} inserted, {$this->updatedRows} updated, {$this->skippedRows} skipped, {$this->errorRows} errors");

        } catch (\Exception $e) {
            $this->log->update([
                'status' => 'failed',
                'errors' => json_encode([$e->getMessage()]),
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($startTime),
            ]);
            
            throw $e;
        }
    }

    /**
     * Archive processed file
     */
    protected function archiveFile(array $file): void
    {
        $typeConfig = $this->getTypeConfig();
        $basePath = $this->config['import']['base_path'];
        $archivePath = $basePath . '/' . $typeConfig['archive_path'];

        if (!is_dir($archivePath)) {
            mkdir($archivePath, 0755, true);
        }

        $destination = $archivePath . '/' . $file['name'];
        
        if (rename($file['path'], $destination)) {
            Log::info("Archived {$file['name']} to {$archivePath}");
        } else {
            Log::error("Failed to archive {$file['name']}");
        }
    }

    /**
     * Check if import is locked
     */
    protected function isLocked(): bool
    {
        $lockPath = $this->getLockFilePath();
        return file_exists($lockPath);
    }

    /**
     * Create lock file
     */
    protected function lock(): void
    {
        $lockPath = $this->getLockFilePath();
        $lockDir = dirname($lockPath);

        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }

        file_put_contents($lockPath, '1');
    }

    /**
     * Remove lock file
     */
    protected function unlock(): void
    {
        $lockPath = $this->getLockFilePath();
        if (file_exists($lockPath)) {
            unlink($lockPath);
        }
    }

    /**
     * Get lock file path
     */
    protected function getLockFilePath(): string
    {
        $typeConfig = $this->getTypeConfig();
        $lockDir = $this->config['import']['lock_path'];
        return $lockDir . '/' . $typeConfig['lock_file'];
    }

    /**
     * Parse CSV/TSV row (supports both comma and tab delimited)
     */
    protected function parseRow(string $line): array
    {
        // Use same pattern as legacy scripts
        return preg_split("/[\t,]/", $line);
    }

    /**
     * Check if import type is enabled
     */
    protected function isEnabled(): bool
    {
        $typeConfig = $this->getTypeConfig();
        return $typeConfig['enabled'] ?? true;
    }

    /**
     * Get configuration for this import type
     */
    protected function getTypeConfig(): array
    {
        return $this->config['import'][$this->importType] ?? [];
    }

    /**
     * Process a single row - must be implemented by subclasses
     */
    abstract protected function processRow(string $line, int $index): void;
}
