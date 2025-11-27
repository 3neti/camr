<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SAP Import/Export Scheduled Tasks
Schedule::command('sap:import-meters')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sap:import-sites')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sap:import-users')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sap:export-readings')->dailyAt('00:15')->withoutOverlapping();
