<?php

use App\Models\ConfigurationFile;
use App\Models\Meter;
use App\Models\Gateway;
use App\Models\Site;
use App\Models\User;
use function Pest\Laravel\{actingAs, get, post, put, delete};

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access config files index', function () {
    get(route('config-files.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view config files index', function () {
    actingAs($this->user);
    
    get(route('config-files.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('config-files/Index'));
});

test('config files index shows paginated files', function () {
    actingAs($this->user);
    
    ConfigurationFile::factory()->count(20)->create();
    
    get(route('config-files.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('config-files/Index')
            ->has('configFiles.data', 15)
        );
});

test('config files can be searched by meter model', function () {
    actingAs($this->user);
    
    ConfigurationFile::factory()->create([
        'meter_model' => 'GE I-210+',
        'config_file_content' => 'test config'
    ]);
    ConfigurationFile::factory()->create([
        'meter_model' => 'Itron Centron',
        'config_file_content' => 'other config'
    ]);
    
    get(route('config-files.index', ['search' => 'GE I-210']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('config-files/Index')
            ->has('configFiles.data', 1)
            ->where('configFiles.data.0.meter_model', 'GE I-210+')
        );
});

test('config files index shows meters count', function () {
    actingAs($this->user);
    
    $config = ConfigurationFile::factory()->create();
    $site = Site::factory()->create();
    $gateway = Gateway::factory()->create(['site_id' => $site->id]);
    
    Meter::factory()->count(3)->create([
        'site_id' => $site->id,
        'gateway_id' => $gateway->id,
        'configuration_file_id' => $config->id,
    ]);
    
    get(route('config-files.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('config-files/Index')
            ->where('configFiles.data.0.meters_count', 3)
        );
});

test('authenticated user can view create config file page', function () {
    actingAs($this->user);
    
    get(route('config-files.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('config-files/Create'));
});

test('authenticated user can create a config file', function () {
    actingAs($this->user);
    
    $configData = [
        'meter_model' => 'GE I-210+',
        'config_file_content' => 'Sample configuration content here',
    ];
    
    post(route('config-files.store'), $configData)
        ->assertRedirect(route('config-files.index'))
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('configuration_files', [
        'meter_model' => 'GE I-210+',
        'config_file_content' => 'Sample configuration content here',
        'created_by' => $this->user->id,
    ]);
});

test('config file creation requires meter_model', function () {
    actingAs($this->user);
    
    post(route('config-files.store'), [
        'config_file_content' => 'Sample content',
    ])->assertSessionHasErrors('meter_model');
});

test('config file creation requires config_file_content', function () {
    actingAs($this->user);
    
    post(route('config-files.store'), [
        'meter_model' => 'GE I-210+',
    ])->assertSessionHasErrors('config_file_content');
});

test('authenticated user can view a config file', function () {
    actingAs($this->user);
    
    $config = ConfigurationFile::factory()->create();
    
    get(route('config-files.show', $config))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('config-files/Show')
            ->has('configFile')
            ->where('configFile.id', $config->id)
        );
});

test('config file show page includes meters using it', function () {
    actingAs($this->user);
    
    $config = ConfigurationFile::factory()->create();
    $site = Site::factory()->create();
    $gateway = Gateway::factory()->create(['site_id' => $site->id]);
    
    $meter = Meter::factory()->create([
        'site_id' => $site->id,
        'gateway_id' => $gateway->id,
        'configuration_file_id' => $config->id,
    ]);
    
    get(route('config-files.show', $config))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('config-files/Show')
            ->has('configFile.meters', 1)
            ->where('configFile.meters.0.id', $meter->id)
        );
});

test('authenticated user can view edit config file page', function () {
    actingAs($this->user);
    
    $config = ConfigurationFile::factory()->create();
    
    get(route('config-files.edit', $config))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('config-files/Edit')
            ->has('configFile')
        );
});

test('authenticated user can update a config file', function () {
    actingAs($this->user);
    
    $config = ConfigurationFile::factory()->create([
        'meter_model' => 'Old Model',
        'config_file_content' => 'Old content',
    ]);
    
    put(route('config-files.update', $config), [
        'meter_model' => 'New Model',
        'config_file_content' => 'New content',
    ])
        ->assertRedirect(route('config-files.show', $config))
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('configuration_files', [
        'id' => $config->id,
        'meter_model' => 'New Model',
        'config_file_content' => 'New content',
        'updated_by' => $this->user->id,
    ]);
});

test('authenticated user can delete a config file', function () {
    actingAs($this->user);
    
    $config = ConfigurationFile::factory()->create();
    
    delete(route('config-files.destroy', $config))
        ->assertRedirect(route('config-files.index'))
        ->assertSessionHas('success');
    
    $this->assertDatabaseMissing('configuration_files', ['id' => $config->id]);
});

test('cannot delete config file in use by meters', function () {
    actingAs($this->user);
    
    $config = ConfigurationFile::factory()->create();
    $site = Site::factory()->create();
    $gateway = Gateway::factory()->create(['site_id' => $site->id]);
    
    Meter::factory()->create([
        'site_id' => $site->id,
        'gateway_id' => $gateway->id,
        'configuration_file_id' => $config->id,
    ]);
    
    delete(route('config-files.destroy', $config))
        ->assertRedirect()
        ->assertSessionHas('error');
    
    $this->assertDatabaseHas('configuration_files', ['id' => $config->id]);
});

test('config files index supports sorting', function () {
    actingAs($this->user);
    
    ConfigurationFile::factory()->create(['meter_model' => 'AAAA Model']);
    ConfigurationFile::factory()->create(['meter_model' => 'ZZZZ Model']);
    
    get(route('config-files.index', ['sort' => 'meter_model', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('config-files/Index')
            ->where('configFiles.data.0.meter_model', 'AAAA Model')
        );
});
