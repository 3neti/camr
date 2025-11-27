<?php

namespace App\Services\Sap;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserListImporter extends SapFileImporter
{
    protected string $importType = 'users';

    /**
     * Process a single user row from CSV
     * 
     * CSV Format (8 columns):
     * USER_ID, USER_NAME, USER_ID_VALID_TO, COMPANY, BUSINESS_ENTITY,
     * BUSINESS_ENTITY_VALID_TO, FUNCTION, FUNCTION_VALID_TO
     */
    protected function processRow(string $line, int $index): void
    {
        $cols = $this->parseRow($line);
        
        // Skip header row
        if ($index === 0 && strtoupper($cols[0] ?? '') === 'USER_ID') {
            $this->skippedRows++;
            return;
        }

        // Validate minimum columns
        if (count($cols) < 8) {
            throw new \Exception("Invalid column count: " . count($cols) . ", expected 8");
        }

        // Extract and clean data
        $userId = trim($cols[0]);
        $userName = trim($cols[1]);
        $userIdValidTo = trim($cols[2]);
        $company = trim($cols[3]);
        $businessEntity = trim($cols[4]);
        $businessEntityValidTo = trim($cols[5]);
        $function = trim($cols[6]);
        $functionValidTo = trim($cols[7]);

        // Clean newline characters
        $functionValidTo = str_replace(["\r\n", "\r", "\n"], '', $functionValidTo);

        // Skip if not a valid building admin function
        if (!$this->isValidFunction($function)) {
            Log::debug("Skipping user {$userId} - function '{$function}' not mapped");
            $this->skippedRows++;
            return;
        }

        // Skip header if somehow we got it
        if ($userId === 'USER_ID') {
            $this->skippedRows++;
            return;
        }

        // Map SAP function to CAMR role
        $role = $this->mapUserRole($function);

        // Create email from user ID if not already an email
        $email = str_contains($userId, '@') ? $userId : $userId . '@example.com';

        // Parse expiration date
        $expirationDate = $this->parseDate($userIdValidTo);

        // Prepare user data
        $userData = [
            'name' => $userName,
            'email' => $email,
            'role' => $role,
            // Note: In a real system, you might want to add SAP-specific fields to users table
            // For now, we'll use the existing user structure
        ];

        // Check if user exists by email
        $existingUser = User::where('email', $email)->first();

        if (!$existingUser) {
            // Create new user with default password
            $defaultPassword = config('sap.mapping.default_user_password');
            $userData['password'] = Hash::make($defaultPassword);
            $userData['email_verified_at'] = now(); // Auto-verify SAP imported users

            User::create($userData);
            $this->insertedRows++;
            
            Log::info("Created new user: {$email}");
        } else {
            // Update existing user (but don't change password)
            $existingUser->update([
                'name' => $userName,
                'role' => $role,
            ]);
            $this->updatedRows++;
            
            Log::debug("Updated user: {$email}");
        }
    }

    /**
     * Check if SAP function is a valid building admin function
     */
    protected function isValidFunction(string $function): bool
    {
        $validFunctions = array_keys(config('sap.mapping.user_roles'));
        return in_array($function, $validFunctions);
    }

    /**
     * Map SAP function to CAMR role
     */
    protected function mapUserRole(string $sapFunction): string
    {
        $mapping = config('sap.mapping.user_roles');
        return $mapping[$sapFunction] ?? 'user';
    }

    /**
     * Parse SAP date format (YYYYMMDD) to Carbon instance
     */
    protected function parseDate(?string $date): ?Carbon
    {
        if (empty($date) || $date === '00000000' || $date === '99991231') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Ymd', $date);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$date}");
            return null;
        }
    }
}
