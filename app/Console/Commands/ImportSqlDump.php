<?php

namespace App\Console\Commands;

use App\Models\DataImport;
use App\Services\SqlDumpImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class ImportSqlDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:sql {file : The path to the SQL dump file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SQL dump file directly from command line';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Validate file extension
        if (!str_ends_with($filePath, '.sql')) {
            $this->error("File must be a .sql file");
            return 1;
        }

        $this->info("Starting import of: {$filePath}");
        $this->info("File size: " . number_format(filesize($filePath) / 1024 / 1024, 2) . " MB");

        // Create DataImport record
        $import = DataImport::create([
            'filename' => basename($filePath),
            'file_path' => $filePath,
            'status' => 'queued',
            'user_id' => 1, // System user
        ]);

        $this->info("Created DataImport record (ID: {$import->id})");

        // Run the import
        try {
            $importer = new SqlDumpImporter($import);
            
            $this->newLine();
            $this->info('Starting import process...');
            $this->newLine();
            
            $importer->import();
            
            $import->refresh();
            
            $this->newLine();
            $this->info('✅ Import completed successfully!');
            $this->newLine();
            
            // Display statistics
            if ($import->statistics) {
                $this->table(
                    ['Type', 'Count'],
                    collect($import->statistics)->map(fn($count, $type) => [$type, $count])
                );
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Import failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            return 1;
        }
    }
}
