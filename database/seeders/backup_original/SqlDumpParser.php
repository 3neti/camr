<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

/**
 * SQL Dump Parser
 * 
 * Parses the legacy meter_reading SQL dump and extracts data for seeding.
 * This preserves the parsing logic for potential full migration.
 */
class SqlDumpParser
{
    private string $sqlDumpPath;
    private array $tables = [];
    
    public function __construct(string $sqlDumpPath)
    {
        $this->sqlDumpPath = $sqlDumpPath;
    }
    
    /**
     * Parse the entire SQL dump file
     */
    public function parse(): array
    {
        if (!file_exists($this->sqlDumpPath)) {
            throw new \Exception("SQL dump file not found: {$this->sqlDumpPath}");
        }
        
        $content = file_get_contents($this->sqlDumpPath);
        
        // Extract table structures and data
        $this->extractTables($content);
        
        return $this->tables;
    }
    
    /**
     * Extract all tables from SQL dump
     */
    private function extractTables(string $content): void
    {
        // Pattern to match table creation and inserts
        preg_match_all(
            '/CREATE TABLE[^`]*`([^`]+)`[^;]+;.*?(?:INSERT INTO `\1`[^;]+;)?/s',
            $content,
            $matches,
            PREG_SET_ORDER
        );
        
        foreach ($matches as $match) {
            $tableName = $match[1];
            $this->tables[$tableName] = [
                'structure' => $match[0],
                'data' => []
            ];
        }
        
        // Now extract INSERT statements for each table
        foreach (array_keys($this->tables) as $tableName) {
            $this->extractInserts($content, $tableName);
        }
    }
    
    /**
     * Extract INSERT data for a specific table
     */
    private function extractInserts(string $content, string $tableName): void
    {
        // Find all INSERT statements for this table
        $pattern = '/INSERT INTO `' . preg_quote($tableName, '/') . '`[^(]*\(([^)]+)\)\s+VALUES\s*(.+?);/s';
        
        if (preg_match($pattern, $content, $match)) {
            $columns = array_map('trim', explode(',', $match[1]));
            $columns = array_map(fn($col) => trim($col, '`'), $columns);
            
            $this->tables[$tableName]['columns'] = $columns;
            $this->tables[$tableName]['data'] = $this->parseValues($match[2]);
        }
    }
    
    /**
     * Parse VALUES clause from INSERT statement
     */
    private function parseValues(string $valuesString): array
    {
        $rows = [];
        
        // Split by "),\n\t(" to get individual rows
        $valueRows = preg_split('/\),[\s\n\r]*\(/s', $valuesString);
        
        foreach ($valueRows as $row) {
            // Clean up the row
            $row = trim($row, " \t\n\r\0\x0B(),");
            
            // Parse individual values
            $values = $this->parseRowValues($row);
            $rows[] = $values;
        }
        
        return $rows;
    }
    
    /**
     * Parse individual row values (handles strings, numbers, NULL, binary data)
     */
    private function parseRowValues(string $row): array
    {
        $values = [];
        $pos = 0;
        $length = strlen($row);
        
        while ($pos < $length) {
            // Skip whitespace
            while ($pos < $length && in_array($row[$pos], [' ', "\t", "\n", "\r"])) {
                $pos++;
            }
            
            if ($pos >= $length) break;
            
            // Check for NULL
            if (substr($row, $pos, 4) === 'NULL') {
                $values[] = null;
                $pos += 4;
            }
            // Check for _binary prefix
            elseif (substr($row, $pos, 7) === '_binary') {
                $pos += 7;
                // Skip binary data for now (it's session data we don't need)
                $values[] = '[BINARY]';
                // Find the end of the hex string
                while ($pos < $length && $row[$pos] !== ',') {
                    $pos++;
                }
            }
            // Check for string (quoted)
            elseif ($row[$pos] === "'") {
                $pos++; // Skip opening quote
                $value = '';
                while ($pos < $length) {
                    if ($row[$pos] === '\\' && $pos + 1 < $length) {
                        // Handle escaped characters
                        $pos++;
                        $value .= $row[$pos];
                        $pos++;
                    } elseif ($row[$pos] === "'") {
                        // Check if it's escaped quote
                        if ($pos + 1 < $length && $row[$pos + 1] === "'") {
                            $value .= "'";
                            $pos += 2;
                        } else {
                            // End of string
                            $pos++;
                            break;
                        }
                    } else {
                        $value .= $row[$pos];
                        $pos++;
                    }
                }
                $values[] = $value;
            }
            // Number or other value
            else {
                $value = '';
                while ($pos < $length && $row[$pos] !== ',') {
                    $value .= $row[$pos];
                    $pos++;
                }
                $value = trim($value);
                // Convert to appropriate type
                if (is_numeric($value)) {
                    $values[] = strpos($value, '.') !== false ? (float)$value : (int)$value;
                } else {
                    $values[] = $value;
                }
            }
            
            // Skip comma
            while ($pos < $length && in_array($row[$pos], [',', ' ', "\t", "\n", "\r"])) {
                $pos++;
            }
        }
        
        return $values;
    }
    
    /**
     * Get data for a specific table
     */
    public function getTableData(string $tableName): array
    {
        return $this->tables[$tableName] ?? [];
    }
    
    /**
     * Get all table names
     */
    public function getTableNames(): array
    {
        return array_keys($this->tables);
    }
    
    /**
     * Convert table data to associative arrays
     */
    public function getTableRows(string $tableName): array
    {
        $tableData = $this->getTableData($tableName);
        
        if (empty($tableData['columns']) || empty($tableData['data'])) {
            return [];
        }
        
        $rows = [];
        foreach ($tableData['data'] as $rowValues) {
            $row = [];
            foreach ($tableData['columns'] as $index => $column) {
                $row[$column] = $rowValues[$index] ?? null;
            }
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Export table data to JSON for inspection
     */
    public function exportTableToJson(string $tableName, string $outputPath): void
    {
        $rows = $this->getTableRows($tableName);
        file_put_contents($outputPath, json_encode($rows, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get sample of table data (first N rows)
     */
    public function getTableSample(string $tableName, int $limit = 10): array
    {
        $rows = $this->getTableRows($tableName);
        return array_slice($rows, 0, $limit);
    }
    
    /**
     * Get statistics about the dump
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        foreach ($this->tables as $tableName => $tableData) {
            $stats[$tableName] = [
                'columns' => count($tableData['columns'] ?? []),
                'rows' => count($tableData['data'] ?? []),
                'column_names' => $tableData['columns'] ?? [],
            ];
        }
        
        return $stats;
    }
}
