<?php

namespace App\Console\Commands\Sap;

use App\Services\Sap\UserListImporter;
use Illuminate\Console\Command;

class ImportUserListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:import-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import user access lists from SAP CSV files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SAP user list import...');
        
        $importer = new UserListImporter();
        $result = $importer->import();
        
        if ($result['success']) {
            $this->info("Successfully processed {$result['files_processed']} file(s)");
        } else {
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->error($error);
                }
            }
            $this->warn('No files were processed or errors occurred');
        }
        
        return $result['success'] ? 0 : 1;
    }
}
