<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class UiSettings extends Settings
{
    public bool $show_buildings = false;
    public bool $show_locations = false;
    public bool $show_config_files = false;

    public static function group(): string
    {
        return 'ui';
    }
}
