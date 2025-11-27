<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SAP Import Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for importing data from SAP ERP system via CSV files
    | dropped into monitored directories.
    |
    */

    'import' => [
        'base_path' => env('SAP_IMPORT_PATH', '/AMR'),
        
        // Check SEP (test) folder first before DOWNLOAD (production)
        'check_sep_first' => env('SAP_CHECK_SEP_FIRST', true),
        
        // Lock file directory to prevent concurrent processing
        'lock_path' => env('SAP_LOCK_PATH', storage_path('app/sap/locks')),
        
        // Meter master data imports
        'meters' => [
            'enabled' => env('SAP_IMPORT_METERS_ENABLED', true),
            'path' => 'DOWNLOAD/METER_LIST',
            'sep_path' => 'SEP_DOWNLOAD/METER_LIST',
            'archive_path' => 'DOWNLOAD/METER_LIST_OLD',
            'lock_file' => 'importmetermaster',
        ],
        
        // Site/cut-off imports
        'sites' => [
            'enabled' => env('SAP_IMPORT_SITES_ENABLED', true),
            'path' => 'DOWNLOAD/SITE_LIST',
            'sep_path' => 'SEP_DOWNLOAD/SITE_LIST',
            'archive_path' => 'DOWNLOAD/SITE_LIST_OLD',
            'lock_file' => 'importsitelist',
        ],
        
        // User access list imports
        'users' => [
            'enabled' => env('SAP_IMPORT_USERS_ENABLED', true),
            'path' => 'DOWNLOAD/USER_LIST',
            'sep_path' => 'SEP_DOWNLOAD/USER_LIST',
            'archive_path' => 'DOWNLOAD/USER_LIST_OLD',
            'lock_file' => 'importuserlist',
        ],
        
        // File pattern to match (e.g., *.CSV, *.csv)
        'file_pattern' => '*.{CSV,csv,txt,TXT}',
        
        // Delete unassigned meters with inactive status after import
        'cleanup_unassigned_inactive' => env('SAP_CLEANUP_UNASSIGNED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SAP Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for exporting meter readings to SAP for billing.
    |
    */

    'export' => [
        'enabled' => env('SAP_EXPORT_ENABLED', true),
        'path' => env('SAP_EXPORT_PATH', '/AMR/UPLOAD'),
        'archive_path' => '/AMR/UPLOAD/ARCHIVE',
        
        // Export validation rules
        'validation' => [
            // Maximum days offline before excluding from export
            'max_offline_days' => 4,
            
            // Only export active meters
            'require_active_status' => true,
            
            // Only export meters with valid RO dates
            'require_valid_ro' => true,
            
            // Only export meters with customer names
            'require_customer_name' => true,
            
            // Only export meters with contract numbers
            'require_contract_number' => true,
            
            // Minimum reading value to export
            'min_reading_value' => 1.0,
            
            // Only export meters with measuring point != 0
            'require_measuring_point' => true,
        ],
        
        // File naming format: {BUSINESS_ENTITY}_{COMPANY}_{DAY}_{MONTH}_{YEAR}.csv
        'filename_format' => '{business_entity}_{company}_{day}_{month}_{year}.csv',
        
        // CSV format settings
        'csv' => [
            'delimiter' => ',',
            'enclosure' => '',
            'escape' => '\\',
            'headers' => false,
        ],
        
        // Time to consider for cut-off (HH:MM:SS)
        'cutoff_time' => '00:14:59',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Mapping Configuration
    |--------------------------------------------------------------------------
    |
    | Map SAP values to CAMR values
    |
    */

    'mapping' => [
        // User roles mapping (SAP function -> CAMR role)
        'user_roles' => [
            'Z>PH-BLDG-ADMIN-SUPERVISOR' => 'building_admin',
            'Z>PH-BLDG-ADMIN-MANAGER' => 'building_admin',
            'Z>PH-BLDG-ADMIN-OFFICER' => 'building_admin',
            'Z>PH-ML-BLDG-ADMIN-SUPERVISOR' => 'building_admin',
            'Z>PH-ML-BLDG-ADMIN-MANAGER' => 'building_admin',
            'Z>PH-ML-BLDG-ADMIN-OFFICER' => 'building_admin',
        ],
        
        // Default password for imported users
        'default_user_password' => '123',
        
        // Meter roles
        'meter_roles' => [
            'client' => 'Client Meter',
            'spare' => 'Spare Meter',
            'main' => 'Main',
            'sub' => 'Sub',
            'check' => 'Check',
        ],
        
        // Meter status values
        'meter_status' => [
            'ACTIVE' => 'Active',
            'INACTIVE' => 'Inactive',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        // Log to database
        'database' => true,
        
        // Log to Laravel log file
        'file' => true,
        
        // Log level
        'level' => env('SAP_LOG_LEVEL', 'info'),
        
        // Retain logs for (days)
        'retention_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        // Send email on import/export errors
        'email_on_error' => env('SAP_NOTIFY_ON_ERROR', true),
        
        // Email addresses to notify
        'recipients' => explode(',', env('SAP_NOTIFICATION_EMAILS', '')),
        
        // Send summary reports
        'daily_summary' => env('SAP_DAILY_SUMMARY', false),
    ],
];
