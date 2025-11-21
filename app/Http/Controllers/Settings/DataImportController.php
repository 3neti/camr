<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCsvImportJob;
use App\Jobs\ProcessSqlDumpJob;
use App\Models\ImportJob;
use App\Services\FileValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class DataImportController extends Controller
{
    /**
     * Show the data import page
     */
    public function index(Request $request): Response
    {
        $imports = ImportJob::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return Inertia::render('settings/DataImport', [
            'imports' => $imports,
        ]);
    }

    /**
     * Upload files for import
     */
    public function upload(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sql,csv,zip',
            'file' => 'required|file|max:102400', // 100MB max
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        // Validate file extension
        if ($type === 'sql' && $file->getClientOriginalExtension() !== 'sql') {
            return response()->json(['error' => 'Invalid file type. Expected .sql file.'], 422);
        }

        if ($type === 'csv' && $file->getClientOriginalExtension() !== 'csv') {
            return response()->json(['error' => 'Invalid file type. Expected .csv file.'], 422);
        }

        if ($type === 'zip' && $file->getClientOriginalExtension() !== 'zip') {
            return response()->json(['error' => 'Invalid file type. Expected .zip file.'], 422);
        }

        // Handle zip file extraction
        if ($type === 'zip') {
            return $this->handleZipUpload($file);
        }

        // Store file
        $directory = $type === 'sql' ? 'imports/sql' : 'imports/csv';
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs($directory, $filename);
        $fullPath = Storage::path($path);

        // Validate file before processing
        $validator = new FileValidator();
        $isValid = $type === 'sql' 
            ? $validator->validateSqlDump($fullPath)
            : $validator->validateCsv($fullPath);
        
        $validationResult = $validator->getValidationResult();

        // If validation failed, return error to user
        if (!$isValid) {
            Storage::delete($path); // Clean up the file
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $validationResult['errors'],
                'warnings' => $validationResult['warnings'],
            ], 422);
        }

        // Quick validation and get row count for preview
        $info = $this->getFileInfo($path, $type);

        return response()->json([
            'success' => true,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'info' => $info,
            'statistics' => $validationResult['statistics'],
            'warnings' => $validationResult['warnings'],
        ]);
    }

    /**
     * Start the import process
     */
    public function import(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*.path' => 'required|string',
            'files.*.type' => 'required|in:sql,csv',
            'files.*.filename' => 'required|string',
            'options' => 'nullable|array',
            'options.clear_existing' => 'boolean',
            'options.create_missing_meters' => 'boolean',
            'options.update_timestamps' => 'boolean',
        ]);

        $files = $request->input('files');
        $options = $request->input('options', []);
        $jobs = [];

        foreach ($files as $fileData) {
            // Create import job record
            $importJob = ImportJob::create([
                'type' => $fileData['type'] === 'sql' ? 'sql_dump' : 'csv_import',
                'filename' => $fileData['filename'],
                'status' => 'pending',
                'options' => $options,
                'user_id' => auth()->id(),
            ]);

            // Dispatch appropriate job
            if ($fileData['type'] === 'sql') {
                ProcessSqlDumpJob::dispatch($importJob->id, $fileData['path'], $options);
            } else {
                ProcessCsvImportJob::dispatch($importJob->id, $fileData['path'], $options);
            }

            $jobs[] = $importJob;
        }

        return response()->json([
            'success' => true,
            'message' => count($jobs) . ' import(s) started',
            'jobs' => $jobs,
        ]);
    }

    /**
     * Get import progress
     */
    public function progress(Request $request)
    {
        $request->validate([
            'job_ids' => 'required|array',
            'job_ids.*' => 'exists:import_jobs,id',
        ]);

        $jobs = ImportJob::whereIn('id', $request->input('job_ids'))
            ->where('user_id', auth()->id())
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'filename' => $job->filename,
                    'type' => $job->type,
                    'status' => $job->status,
                    'progress' => $job->progress_percentage,
                    'processed' => $job->processed_records,
                    'total' => $job->total_records,
                    'error' => $job->error,
                    'result' => $job->result,
                    'duration' => $job->duration,
                    'started_at' => $job->started_at?->toIso8601String(),
                    'completed_at' => $job->completed_at?->toIso8601String(),
                ];
            });

        return response()->json(['jobs' => $jobs]);
    }

    /**
     * Cancel an import
     */
    public function cancel(Request $request, ImportJob $importJob)
    {
        // Check authorization
        if ($importJob->user_id !== auth()->id()) {
            abort(403);
        }

        // Can only cancel pending or processing jobs
        if (!in_array($importJob->status, ['pending', 'processing'])) {
            return response()->json([
                'error' => 'Cannot cancel a job that is ' . $importJob->status,
            ], 422);
        }

        $importJob->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Import cancelled',
        ]);
    }

    /**
     * Delete an import job record
     */
    public function destroy(ImportJob $importJob)
    {
        // Check authorization
        if ($importJob->user_id !== auth()->id()) {
            abort(403);
        }

        $importJob->delete();

        return redirect()->back()->with('success', 'Import record deleted');
    }

    /**
     * Handle zip file upload and extraction
     */
    private function handleZipUpload($file)
    {
        $zipPath = $file->storeAs('imports/zip', time() . '_' . $file->getClientOriginalName());
        $fullZipPath = Storage::path($zipPath);
        
        $zip = new ZipArchive();
        if ($zip->open($fullZipPath) !== true) {
            Storage::delete($zipPath);
            return response()->json(['error' => 'Failed to open zip file.'], 422);
        }

        $extractedFiles = [];
        $extractPath = Storage::path('imports/extracted/' . time());
        
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Scan for SQL and CSV files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $filename = $fileInfo->getFilename();
                
                // Skip Mac metadata files and hidden files
                if (str_starts_with($filename, '._') || str_starts_with($filename, '.')) {
                    continue;
                }
                
                $extension = strtolower($fileInfo->getExtension());
                
                if (in_array($extension, ['sql', 'csv'])) {
                    $type = $extension;
                    $directory = $type === 'sql' ? 'imports/sql' : 'imports/csv';
                    $newFilename = time() . '_' . $filename;
                    
                    // Ensure storage directory exists
                    $storageDirPath = Storage::path($directory);
                    if (!file_exists($storageDirPath)) {
                        mkdir($storageDirPath, 0755, true);
                    }
                    
                    // Copy to proper storage location
                    $storagePath = $storageDirPath . '/' . $newFilename;
                    copy($fileInfo->getPathname(), $storagePath);
                    
                    $relativePath = $directory . '/' . $newFilename;
                    $info = $this->getFileInfo($relativePath, $type);
                    
                    $extractedFiles[] = [
                        'path' => $relativePath,
                        'filename' => $filename,
                        'size' => $fileInfo->getSize(),
                        'type' => $type,
                        'info' => $info,
                    ];
                }
            }
        }

        // Clean up extracted directory and zip file
        $this->deleteDirectory($extractPath);
        Storage::delete($zipPath);

        if (empty($extractedFiles)) {
            return response()->json(['error' => 'No SQL or CSV files found in zip archive.'], 422);
        }

        return response()->json([
            'success' => true,
            'files' => $extractedFiles,
            'message' => count($extractedFiles) . ' file(s) extracted',
        ]);
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Get file information for preview
     */
    private function getFileInfo(string $path, string $type): array
    {
        $fullPath = Storage::path($path);
        
        if ($type === 'sql') {
            return $this->getSqlFileInfo($fullPath);
        } else {
            return $this->getCsvFileInfo($fullPath);
        }
    }

    /**
     * Get SQL file information
     */
    private function getSqlFileInfo(string $path): array
    {
        $content = file_get_contents($path);
        
        // Count INSERT statements as rough estimate
        $insertCount = substr_count(strtolower($content), 'insert into');
        
        // Try to detect tables
        preg_match_all('/CREATE TABLE.*?`(\w+)`/i', $content, $matches);
        $tables = $matches[1] ?? [];

        return [
            'tables' => $tables,
            'estimated_records' => $insertCount,
        ];
    }

    /**
     * Get CSV file information
     */
    private function getCsvFileInfo(string $path): array
    {
        $handle = fopen($path, 'r');
        
        // Read header
        $header = fgetcsv($handle);
        
        // Count rows
        $rowCount = 0;
        while (fgets($handle) !== false) {
            $rowCount++;
        }
        
        // Get first few rows for preview
        rewind($handle);
        fgetcsv($handle); // Skip header
        $preview = [];
        for ($i = 0; $i < 5 && !feof($handle); $i++) {
            $row = fgetcsv($handle);
            if ($row) {
                $preview[] = array_combine($header, $row);
            }
        }
        
        fclose($handle);

        return [
            'columns' => $header,
            'row_count' => $rowCount,
            'preview' => $preview,
        ];
    }
}
