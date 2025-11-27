<?php

namespace App\Console\Commands\Sap;

use App\Services\Sap\MeterReadingExporter;
use Illuminate\Console\Command;

class ExportMeterReadingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:export-readings
                            {--cutoff-day= : Specific cut-off day to export (default: today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export meter readings to SAP CSV format for billing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SAP meter readings export...');
        
        $cutoffDay = $this->option('cutoff-day');
        
        $exporter = new MeterReadingExporter();
        $result = $exporter->export($cutoffDay);
        
        if ($result['success']) {
            $this->info("Exported readings for {$result['sites_processed']} site(s)");
            $this->info("Total meters exported: {$result['total_meters_exported']}");
            
            if (!empty($result['files_created'])) {
                $this->info("Files created:");
                foreach ($result['files_created'] as $file) {
                    $this->line("  - " . basename($file));
                }
            }
        } else {
            if (!empty($result['errors'])) {
                $this->error('Errors occurred during export:');
                foreach ($result['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }
            $this->warn('No exports were generated or errors occurred');
        }
        
        return $result['success'] ? 0 : 1;
    }
}
