<?php

use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\Site;

beforeEach(function () {
    // Create test site
    $this->site = Site::factory()->create([
        'code' => 'TEST',
    ]);

    // Create test gateway
    $this->gateway = Gateway::factory()->create([
        'site_id' => $this->site->id,
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'site_code' => 'TESTSITE',
        'update_csv' => false,
        'update_site_code' => false,
        'force_load_profile' => false,
    ]);

    // Create configuration file
    $this->configFile = ConfigurationFile::factory()->create([
        'meter_model' => 'test_config.cfg',
    ]);
});

describe('CSV meter list updates', function () {
    test('check csv update returns 0 when flag is false', function () {
        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/check/csv");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        expect($response->content())->toBe('0');
    });

    test('check csv update returns 1 when flag is true', function () {
        $this->gateway->update(['update_csv' => true]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/check/csv");

        $response->assertStatus(200);
        expect($response->content())->toBe('1');
    });

    test('check csv update returns 0 for unknown gateway', function () {
        $response = $this->get('/api/gateway/00:00:00:00:00:00/check/csv');

        $response->assertStatus(200);
        expect($response->content())->toBe('0');
    });

    test('get csv content returns empty for gateway with no meters', function () {
        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/csv");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        expect($response->content())->toBe('');
    });

    test('get csv content returns meter list in correct format', function () {
        // Create active meters
        Meter::factory()->create([
            'gateway_id' => $this->gateway->id,
            'site_id' => $this->site->id,
            'configuration_file_id' => $this->configFile->id,
            'name' => '12345678',
            'default_name' => '12345678',
            'status' => 'Active',
        ]);

        Meter::factory()->create([
            'gateway_id' => $this->gateway->id,
            'site_id' => $this->site->id,
            'configuration_file_id' => $this->configFile->id,
            'name' => '87654321',
            'default_name' => '11111111',
            'status' => 'Active',
        ]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/csv");

        $response->assertStatus(200);
        $lines = explode("\n", $response->content());

        expect($lines)->toHaveCount(2);
        expect($lines[0])->toBe('12345678,test_config.cfg,12345678');
        expect($lines[1])->toBe('87654321,test_config.cfg,11111111');
    });

    test('get csv content excludes inactive meters', function () {
        Meter::factory()->create([
            'gateway_id' => $this->gateway->id,
            'site_id' => $this->site->id,
            'configuration_file_id' => $this->configFile->id,
            'name' => 'active_meter',
            'default_name' => 'active_meter',
            'status' => 'Active',
        ]);

        Meter::factory()->create([
            'gateway_id' => $this->gateway->id,
            'site_id' => $this->site->id,
            'name' => 'inactive_meter',
            'default_name' => 'inactive_meter',
            'status' => 'Inactive',
        ]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/csv");

        $response->assertStatus(200);
        $lines = explode("\n", $response->content());

        expect($lines)->toHaveCount(1);
        expect($response->content())->toContain('active_meter');
        expect($response->content())->not->toContain('inactive_meter');
    });

    test('get csv content limits to 32 meters', function () {
        // Create 40 active meters
        for ($i = 1; $i <= 40; $i++) {
            Meter::factory()->create([
                'gateway_id' => $this->gateway->id,
                'site_id' => $this->site->id,
                'configuration_file_id' => $this->configFile->id,
                'name' => "meter_{$i}",
                'default_name' => "meter_{$i}",
                'status' => 'Active',
            ]);
        }

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/csv");

        $response->assertStatus(200);
        $lines = explode("\n", $response->content());

        expect($lines)->toHaveCount(32);
    });

    test('get csv content handles meter with default_name = 1', function () {
        Meter::factory()->create([
            'gateway_id' => $this->gateway->id,
            'site_id' => $this->site->id,
            'configuration_file_id' => $this->configFile->id,
            'name' => 'some_meter',
            'default_name' => '1',
            'status' => 'Active',
        ]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/csv");

        $response->assertStatus(200);
        expect($response->content())->toBe('1,test_config.cfg,1');
    });

    test('get csv content handles meter without config file', function () {
        Meter::factory()->create([
            'gateway_id' => $this->gateway->id,
            'site_id' => $this->site->id,
            'configuration_file_id' => null,
            'name' => 'test_meter',
            'default_name' => 'test_meter',
            'status' => 'Active',
        ]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/csv");

        $response->assertStatus(200);
        expect($response->content())->toBe('test_meter,,test_meter');
    });

    test('reset csv update sets flag to false', function () {
        $this->gateway->update(['update_csv' => true]);

        $response = $this->post("/api/gateway/{$this->gateway->mac_address}/csv/reset");

        $response->assertStatus(200);
        expect($response->content())->toBe('OK');

        $this->gateway->refresh();
        expect($this->gateway->update_csv)->toBeFalse();
    });

    test('reset csv update returns ok for unknown gateway', function () {
        $response = $this->post('/api/gateway/00:00:00:00:00:00/csv/reset');

        $response->assertStatus(200);
        expect($response->content())->toBe('OK');
    });
});

describe('Site code updates', function () {
    test('check site code update returns 0 when flag is false', function () {
        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/check/site-code");

        $response->assertStatus(200);
        expect($response->content())->toBe('0');
    });

    test('check site code update returns 1 when flag is true', function () {
        $this->gateway->update(['update_site_code' => true]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/check/site-code");

        $response->assertStatus(200);
        expect($response->content())->toBe('1');
    });

    test('get site code returns location format', function () {
        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/site-code");

        $response->assertStatus(200);
        expect($response->content())->toBe('location = "TESTSITE"');
    });

    test('get site code returns empty for gateway without site code', function () {
        $this->gateway->update(['site_code' => null]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/site-code");

        $response->assertStatus(200);
        expect($response->content())->toBe('location = ""');
    });

    test('get site code returns empty for unknown gateway', function () {
        $response = $this->get('/api/gateway/00:00:00:00:00:00/site-code');

        $response->assertStatus(200);
        expect($response->content())->toBe('location = ""');
    });

    test('reset site code update sets flag to false', function () {
        $this->gateway->update(['update_site_code' => true]);

        $response = $this->post("/api/gateway/{$this->gateway->mac_address}/site-code/reset");

        $response->assertStatus(200);
        expect($response->content())->toBe('OK');

        $this->gateway->refresh();
        expect($this->gateway->update_site_code)->toBeFalse();
    });
});

describe('Force load profile', function () {
    test('check force load profile returns 0 when flag is false', function () {
        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/check/load-profile");

        $response->assertStatus(200);
        expect($response->content())->toBe('0');
    });

    test('check force load profile returns 1 when flag is true', function () {
        $this->gateway->update(['force_load_profile' => true]);

        $response = $this->get("/api/gateway/{$this->gateway->mac_address}/check/load-profile");

        $response->assertStatus(200);
        expect($response->content())->toBe('1');
    });

    test('reset force load profile sets flag to false', function () {
        $this->gateway->update(['force_load_profile' => true]);

        $response = $this->post("/api/gateway/{$this->gateway->mac_address}/load-profile/reset");

        $response->assertStatus(200);
        expect($response->content())->toBe('OK');

        $this->gateway->refresh();
        expect($this->gateway->force_load_profile)->toBeFalse();
    });
});

describe('Server time', function () {
    test('get server time returns valid timestamp', function () {
        $response = $this->get('/api/server-time');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        // Check format: YYYY-MM-DD HH:MM:SS
        expect($response->content())->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
    });

    test('get server time returns current time', function () {
        $before = now()->format('Y-m-d H:i');
        $response = $this->get('/api/server-time');
        $after = now()->format('Y-m-d H:i');

        $responseTime = substr($response->content(), 0, 16); // YYYY-MM-DD HH:MM

        expect($responseTime)->toBeGreaterThanOrEqual($before);
        expect($responseTime)->toBeLessThanOrEqual($after);
    });
});
