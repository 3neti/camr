<?php

use App\Models\ConfigurationFile;
use App\Models\Meter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all unique brand values that look like config files (end with .cfg)
        $brands = Meter::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->where('brand', 'like', '%.cfg')
            ->distinct()
            ->pluck('brand');

        echo "Found " . $brands->count() . " configuration files in brand field\n";

        foreach ($brands as $brandConfigFile) {
            // Create or find configuration file
            $configFile = ConfigurationFile::firstOrCreate(
                ['meter_model' => $brandConfigFile],
                [
                    'config_file_content' => '',
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );

            echo "Config file: {$brandConfigFile} (ID: {$configFile->id})\n";

            // Update all meters with this brand to use the configuration file
            $updated = Meter::where('brand', $brandConfigFile)
                ->whereNull('configuration_file_id')
                ->update(['configuration_file_id' => $configFile->id]);

            echo "  Updated {$updated} meters\n";
        }

        echo "Migration completed!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the migration by clearing configuration_file_id
        // for meters that have brand values ending in .cfg
        Meter::where('brand', 'like', '%.cfg')
            ->whereNotNull('configuration_file_id')
            ->update(['configuration_file_id' => null]);

        // Optionally delete the created configuration files
        // (commented out to preserve data)
        // ConfigurationFile::where('meter_model', 'like', '%.cfg')->delete();
    }
};
