<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ui.show_buildings', false);
        $this->migrator->add('ui.show_locations', false);
        $this->migrator->add('ui.show_config_files', false);
    }
};
