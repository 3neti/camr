<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSqlDumpRequest;
use App\Jobs\ImportSqlDumpJob;
use App\Models\DataImport;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class DataImportController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('admin'),
            new Middleware('throttle:120,1'),
        ];
    }

    /**
     * Show data import page
     */
    public function show(): Response
    {
        $imports = DataImport::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Admin/DataImport', [
            'imports' => $imports,
        ]);
    }

    /**
     * Handle file upload
     */
    public function upload(UploadSqlDumpRequest $request)
    {
        $file = $request->file('file');

        // Ensure imports directory exists
        $importsDir = storage_path('app/imports');
        if (! is_dir($importsDir)) {
            mkdir($importsDir, 0755, true);
        }

        // Store file directly to ensure correct path
        $filename = $file->getClientOriginalName();
        $fullPath = $importsDir . DIRECTORY_SEPARATOR . $filename;
        $file->move($importsDir, $filename);

        // Create import record
        $import = DataImport::create([
            'user_id' => auth()->id(),
            'filename' => $filename,
            'file_path' => $fullPath,
            'status' => 'queued',
        ]);

        // Dispatch job
        ImportSqlDumpJob::dispatch($import)->onQueue('default');

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully. Processing will start shortly.',
            'import_id' => $import->id,
        ]);
    }

    /**
     * Get import status
     */
    public function status($importId)
    {
        $import = DataImport::findOrFail($importId);

        // Verify user owns this import
        if ($import->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $import->id,
            'filename' => $import->filename,
            'status' => $import->status,
            'progress' => $import->progress,
            'statistics' => $import->statistics,
            'error_message' => $import->error_message,
            'started_at' => $import->started_at?->toIso8601String(),
            'completed_at' => $import->completed_at?->toIso8601String(),
            'progress_percentage' => $import->getProgressPercentage(),
            'duration' => $import->getDuration(),
        ]);
    }

    /**
     * Cancel import
     */
    public function cancel($importId)
    {
        $import = DataImport::findOrFail($importId);

        // Verify user owns this import
        if ($import->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Can only cancel if not completed or failed
        if ($import->isCompleted() || $import->isFailed()) {
            return response()->json([
                'error' => 'Cannot cancel a completed or failed import',
            ], 422);
        }

        // Update status
        $import->update(['status' => 'cancelled']);

        // Clean up file
        if ($import->file_path && file_exists($import->file_path)) {
            @unlink($import->file_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Import cancelled successfully.',
        ]);
    }
}
