<?php

namespace App\Console\Commands;

use Database\Seeders\SqlDumpParser;
use Illuminate\Console\Command;

class InspectSqlDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump:inspect 
                            {--stats : Show statistics about all tables}
                            {--sample= : Show sample data from a table}
                            {--export= : Export table to JSON}
                            {--tables : List all tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect and analyze the legacy SQL dump file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dumpPath = env('SQL_DUMP_PATH', '/Users/rli/Documents/DEC/backup/meter_reading/meter_reading.sql');
        
        if (!file_exists($dumpPath)) {
            $this->error("SQL dump not found at: {$dumpPath}");
            $this->comment('Set SQL_DUMP_PATH in your .env file to specify a different location.');
            return 1;
        }
        
        $this->info('📄 Parsing SQL dump...');
        $this->newLine();
        
        $parser = new SqlDumpParser($dumpPath);
        $parser->parse();
        
        // Show tables list
        if ($this->option('tables')) {
            $this->showTables($parser);
            return 0;
        }
        
        // Show statistics
        if ($this->option('stats')) {
            $this->showStatistics($parser);
            return 0;
        }
        
        // Show sample data
        if ($tableName = $this->option('sample')) {
            $this->showSample($parser, $tableName);
            return 0;
        }
        
        // Export table
        if ($tableName = $this->option('export')) {
            $this->exportTable($parser, $tableName);
            return 0;
        }
        
        // Default: show summary
        $this->showSummary($parser);
        
        return 0;
    }
    
    /**
     * Show list of tables
     */
    private function showTables(SqlDumpParser $parser): void
    {
        $tables = $parser->getTableNames();
        
        $this->info('📊 Tables found in dump:');
        $this->newLine();
        
        foreach ($tables as $index => $table) {
            $this->line(sprintf('  %2d. %s', $index + 1, $table));
        }
        
        $this->newLine();
        $this->info('Total: ' . count($tables) . ' tables');
    }
    
    /**
     * Show statistics
     */
    private function showStatistics(SqlDumpParser $parser): void
    {
        $stats = $parser->getStatistics();
        
        $this->info('📊 Table Statistics:');
        $this->newLine();
        
        $headers = ['Table', 'Columns', 'Rows', 'Sample Columns'];
        $rows = [];
        
        foreach ($stats as $table => $data) {
            $sampleCols = array_slice($data['column_names'], 0, 3);
            $colsDisplay = implode(', ', $sampleCols);
            if (count($data['column_names']) > 3) {
                $colsDisplay .= '...';
            }
            
            $rows[] = [
                $table,
                $data['columns'],
                number_format($data['rows']),
                $colsDisplay
            ];
        }
        
        $this->table($headers, $rows);
        
        $totalRows = array_sum(array_column($stats, 'rows'));
        $this->newLine();
        $this->info('Total rows across all tables: ' . number_format($totalRows));
    }
    
    /**
     * Show sample data
     */
    private function showSample(SqlDumpParser $parser, string $tableName): void
    {
        $sample = $parser->getTableSample($tableName, 5);
        
        if (empty($sample)) {
            $this->error("No data found for table: {$tableName}");
            return;
        }
        
        $this->info("📄 Sample data from {$tableName} (first 5 rows):");
        $this->newLine();
        
        // Get columns from first row
        $headers = array_keys($sample[0]);
        
        // Format rows for table display
        $rows = array_map(function($row) {
            return array_map(function($value) {
                if ($value === null) return 'NULL';
                if (is_string($value) && strlen($value) > 50) {
                    return substr($value, 0, 47) . '...';
                }
                return $value;
            }, array_values($row));
        }, $sample);
        
        $this->table($headers, $rows);
    }
    
    /**
     * Export table to JSON
     */
    private function exportTable(SqlDumpParser $parser, string $tableName): void
    {
        $outputPath = storage_path("app/{$tableName}.json");
        
        $this->info("Exporting {$tableName} to JSON...");
        
        try {
            $parser->exportTableToJson($tableName, $outputPath);
            
            $fileSize = filesize($outputPath);
            $this->newLine();
            $this->info("✓ Exported to: {$outputPath}");
            $this->info("  File size: " . number_format($fileSize) . " bytes");
        } catch (\Exception $e) {
            $this->error("Export failed: " . $e->getMessage());
        }
    }
    
    /**
     * Show summary
     */
    private function showSummary(SqlDumpParser $parser): void
    {
        $stats = $parser->getStatistics();
        $tables = $parser->getTableNames();
        
        $this->info('📊 SQL Dump Summary');
        $this->newLine();
        
        $this->line('  Total tables: ' . count($tables));
        $this->line('  Total rows: ' . number_format(array_sum(array_column($stats, 'rows'))));
        $this->newLine();
        
        $this->line('Key tables:');
        $keyTables = ['meter_data', 'meter_details', 'meter_rtu', 'meter_site', 'user_tb'];
        
        foreach ($keyTables as $table) {
            if (isset($stats[$table])) {
                $this->line(sprintf(
                    '  • %-20s %s rows, %s columns',
                    $table,
                    str_pad(number_format($stats[$table]['rows']), 10, ' ', STR_PAD_LEFT),
                    $stats[$table]['columns']
                ));
            }
        }
        
        $this->newLine();
        $this->comment('💡 Use --stats to see all tables');
        $this->comment('💡 Use --tables to list all table names');
        $this->comment('💡 Use --sample=table_name to see sample data');
        $this->comment('💡 Use --export=table_name to export to JSON');
    }
}
