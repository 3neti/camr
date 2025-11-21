<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function parse(): void
    {
        if (! file_exists($this->sqlDumpPath)) {
            throw new \Exception("SQL dump file not found: {$this->sqlDumpPath}");
        }
        
        $content = file_get_contents($this->sqlDumpPath);
        
        // Extract table structures and data
        $this->extractTables($content);
    }
    
    /**
     * Extract all tables from SQL dump
     */
    private function extractTables(string $content): void
    {
        // Now extract INSERT statements for each table
        $this->extractAllInserts($content);
    }
    
    /**
     * Extract all INSERT statements from the dump
     */
    private function extractAllInserts(string $content): void
    {
        // Pattern to find all INSERT INTO statements
        $pattern = '/INSERT INTO `([a-zA-Z0-9_]+)`[^(]*\(([^)]+)\)\s+VALUES\s*(.+?);/s';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tableName = $match[1];
                $columns = array_map('trim', explode(',', $match[2]));
                $columns = array_map(fn($col) => trim($col, '`'), $columns);
                
                if (! isset($this->tables[$tableName])) {
                    $this->tables[$tableName] = [
                        'columns' => $columns,
                        'data' => [],
                    ];
                }
                
                $values = $this->parseValues($match[3]);
                $this->tables[$tableName]['data'] = array_merge($this->tables[$tableName]['data'], $values);
            }
        }
        
        Log::info('Parsed tables from SQL dump', ['tables' => array_keys($this->tables)]);
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
            
            if ($pos >= $length) {
                break;
            }
            
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
     * Get all rows for a specific table as associative arrays
     */
    public function getTableRows(string $tableName): array
    {
        $tableData = $this->tables[$tableName] ?? null;
        
        if (! $tableData || empty($tableData['columns']) || empty($tableData['data'])) {
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
     * Get all table names
     */
    public function getTableNames(): array
    {
        return array_keys($this->tables);
    }
}
