<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FileValidator
{
    private array $errors = [];
    private array $warnings = [];
    private array $statistics = [];

    /**
     * Validate SQL dump file
     */
    public function validateSqlDump(string $filePath): bool
    {
        $this->errors = [];
        $this->warnings = [];
        $this->statistics = [];

        if (!file_exists($filePath)) {
            $this->errors[] = "File not found: {$filePath}";
            return false;
        }

        // Check file size
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            $this->errors[] = "File is empty";
            return false;
        }

        if ($fileSize > 50 * 1024 * 1024) { // 50MB
            $this->errors[] = "File exceeds maximum size of 50MB";
            return false;
        }

        $this->statistics['file_size'] = $fileSize;

        // Read file and validate structure
        $content = file_get_contents($filePath);

        if (!$content) {
            $this->errors[] = "Unable to read file content";
            return false;
        }

        // Check for SQL markers
        if (!$this->containsSqlMarkers($content)) {
            $this->errors[] = "File does not appear to be a SQL dump (missing SQL statements)";
            return false;
        }

        // Validate required tables exist
        $requiredTables = [
            'meter_site' => 'Site configuration',
            'meter_details' => 'Meter definitions',
            'user_tb' => 'User information',
        ];

        $requiredTableMissing = [];
        foreach ($requiredTables as $tableName => $description) {
            if (!$this->tableExists($content, $tableName)) {
                $requiredTableMissing[] = "$tableName ($description)";
            }
        }

        if (!empty($requiredTableMissing)) {
            $this->errors[] = "Missing required tables: " . implode(', ', $requiredTableMissing);
            return false;
        }

        // Validate table structures
        $this->validateTableStructures($content);

        // Count records
        $this->statistics['insert_statements'] = substr_count($content, 'INSERT INTO');
        
        // Parse and validate
        try {
            $parser = new SqlDumpParser($filePath);
            $parser->parse();
            
            $tableNames = $parser->getTableNames();
            $this->statistics['tables_found'] = count($tableNames);
            
            // Count rows for key tables
            foreach (['meter_site', 'meter_details', 'user_tb', 'meter_data'] as $tableName) {
                $rows = $parser->getTableRows($tableName);
                $this->statistics[$tableName . '_rows'] = count($rows);
                
                if ($tableName !== 'meter_data' && count($rows) === 0) {
                    $this->warnings[] = "No records found in $tableName table";
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = "SQL parsing failed: " . $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Validate CSV file
     */
    public function validateCsv(string $filePath, ?string $expectedFormat = null): bool
    {
        $this->errors = [];
        $this->warnings = [];
        $this->statistics = [];

        if (!file_exists($filePath)) {
            $this->errors[] = "File not found: {$filePath}";
            return false;
        }

        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            $this->errors[] = "File is empty";
            return false;
        }

        if ($fileSize > 50 * 1024 * 1024) { // 50MB
            $this->errors[] = "File exceeds maximum size of 50MB";
            return false;
        }

        $this->statistics['file_size'] = $fileSize;

        // Open and read CSV
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->errors[] = "Unable to open file";
            return false;
        }

        try {
            $lineNumber = 0;
            $headers = null;
            $rowCount = 0;
            $invalidRows = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($lineNumber === 1) {
                    // Validate headers
                    $headers = $row;
                    
                    if (empty($headers) || count($headers) === 0) {
                        $this->errors[] = "CSV has no headers or columns";
                        fclose($handle);
                        return false;
                    }

                    // Check for empty header names
                    $emptyHeaders = array_filter($headers, fn($h) => trim($h) === '');
                    if (!empty($emptyHeaders)) {
                        $this->errors[] = "CSV contains empty column headers";
                        fclose($handle);
                        return false;
                    }

                    $this->statistics['columns'] = count($headers);
                    $this->statistics['column_names'] = $headers;
                } else {
                    // Validate data rows
                    if (count($row) !== count($headers)) {
                        $invalidRows++;
                        if ($invalidRows <= 5) { // Only show first 5 errors
                            $this->warnings[] = "Row $lineNumber has " . count($row) . " columns, expected " . count($headers);
                        }
                    }
                    
                    // Check for completely empty rows
                    if (array_filter($row, fn($v) => trim($v) !== '') === []) {
                        if ($rowCount > 0) { // Don't count trailing empty rows
                            $invalidRows++;
                        }
                    } else {
                        $rowCount++;
                    }
                }
            }

            fclose($handle);

            if ($rowCount === 0) {
                $this->errors[] = "CSV contains no data rows (only headers)";
                return false;
            }

            $this->statistics['rows'] = $rowCount;
            $this->statistics['invalid_rows'] = $invalidRows;

            if ($invalidRows > $rowCount * 0.1) { // More than 10% invalid
                $this->warnings[] = "CSV has a high number of malformed rows ({$invalidRows} out of {$rowCount})";
            }

            return true;
        } catch (\Exception $e) {
            fclose($handle);
            $this->errors[] = "Error reading CSV: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Check if file contains SQL markers
     */
    private function containsSqlMarkers(string $content): bool
    {
        $sqlMarkers = [
            'INSERT INTO',
            'CREATE TABLE',
            'DROP TABLE',
        ];

        foreach ($sqlMarkers as $marker) {
            if (stripos($content, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if table exists in SQL dump
     */
    private function tableExists(string $content, string $tableName): bool
    {
        // Look for INSERT INTO `table_name` or INSERT INTO table_name
        $patterns = [
            "/INSERT INTO `{$tableName}`/i",
            "/INSERT INTO {$tableName}/i",
            "/CREATE TABLE `{$tableName}`/i",
            "/CREATE TABLE {$tableName}/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate table structures
     */
    private function validateTableStructures(string $content): void
    {
        // Check for common issues in SQL syntax
        
        // Check for proper INSERT statements
        if (!preg_match('/INSERT INTO/i', $content)) {
            $this->warnings[] = "No INSERT statements found (file may contain only schema)";
        }

        // Check for unmatched quotes
        $singleQuotes = substr_count($content, "'") - substr_count($content, "\\'");
        if ($singleQuotes % 2 !== 0) {
            $this->warnings[] = "Possible unmatched quotes in file (may cause parsing issues)";
        }

        // Check for common SQL syntax issues
        if (preg_match("/;[^;]*$/i", $content)) {
            // File ends properly with semicolon, good
        } else {
            $this->warnings[] = "File may be incomplete (does not end with semicolon)";
        }
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get statistics about the file
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Get validation result as array
     */
    public function getValidationResult(): array
    {
        $isValid = empty($this->errors);

        return [
            'is_valid' => $isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'statistics' => $this->statistics,
            'message' => $isValid
                ? 'File validation passed'
                : 'File validation failed: ' . implode('; ', $this->errors),
        ];
    }
}
