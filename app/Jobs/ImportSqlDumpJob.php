<?php

namespace App\Jobs;

use App\Models\DataImport;
use App\Services\SqlDumpImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportSqlDumpJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public DataImport $dataImport)
    {
    }

    public function handle(): void
    {
        try {
            $importer = new SqlDumpImporter($this->dataImport);
            $importer->import();
        } catch (\Exception $e) {
            Log::error('ImportSqlDumpJob failed', ['error' => $e->getMessage()]);
        }
    }
}
