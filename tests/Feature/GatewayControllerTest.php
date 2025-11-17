<?php

use App\Models\{Gateway, Site, Location, User};
use function Pest\Laravel\{actingAs, assertDatabaseHas, assertDatabaseMissing, get, post, put, delete};

beforeEach(function () {
    $this->user = User::factory()->create();
});

// Index tests
test('guest cannot access gateways index', function () {
    get(route('gateways.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view gateways index', function () {
    actingAs($this->user)
        ->get(route('gateways.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('gateways/Index'));
});

test('gateways index shows paginated gateways', function () {
    Gateway::factory(20)->create();

    actingAs($this->user)
        ->get(route('gateways.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gateways/Index')
            ->has('gateways.data', 15)
        );
});

test('gateways can be searched by serial number', function () {
    Gateway::factory()->create(['serial_number' => 'GW-12345']);
    Gateway::factory()->create(['serial_number' => 'GW-99999']);

    actingAs($this->user)
        ->get(route('gateways.index', ['search' => 'GW-12345']))
        ->assertInertia(fn ($page) => $page
            ->where('gateways.data.0.serial_number', 'GW-12345')
            ->has('gateways.data', 1)
        );
});

test('gateways can be filtered by site', function () {
    $site = Site::factory()->create();
    Gateway::factory()->create(['site_id' => $site->id]);
    Gateway::factory()->create();

    actingAs($this->user)
        ->get(route('gateways.index', ['site_id' => $site->id]))
        ->assertInertia(fn ($page) => $page->has('gateways.data', 1));
});

// Create tests
test('authenticated user can view create gateway form', function () {
    actingAs($this->user)
        ->get(route('gateways.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gateways/Create')
            ->has('sites')
            ->has('locations')
        );
});

test('authenticated user can create a gateway', function () {
    $site = Site::factory()->create();

    actingAs($this->user)
        ->post(route('gateways.store'), [
            'site_id' => $site->id,
            'serial_number' => 'GW-TEST-001',
            'mac_address' => '00:11:22:33:44:55',
            'ip_address' => '192.168.1.100',
            'update_csv' => false,
            'update_site_code' => false,
            'ssh_enabled' => true,
            'force_load_profile' => false,
        ])
        ->assertRedirect(route('gateways.index'));

    assertDatabaseHas('gateways', [
        'serial_number' => 'GW-TEST-001',
        'site_id' => $site->id,
        'created_by' => $this->user->id,
    ]);
});

// Validation tests
test('gateway creation requires site_id', function () {
    actingAs($this->user)
        ->post(route('gateways.store'), [
            'serial_number' => 'GW-TEST',
        ])
        ->assertSessionHasErrors('site_id');
});

test('gateway creation requires serial_number', function () {
    $site = Site::factory()->create();

    actingAs($this->user)
        ->post(route('gateways.store'), [
            'site_id' => $site->id,
        ])
        ->assertSessionHasErrors('serial_number');
});

test('gateway creation requires unique serial_number', function () {
    $site = Site::factory()->create();
    Gateway::factory()->create(['serial_number' => 'GW-DUPLICATE']);

    actingAs($this->user)
        ->post(route('gateways.store'), [
            'site_id' => $site->id,
            'serial_number' => 'GW-DUPLICATE',
            'update_csv' => false,
            'update_site_code' => false,
            'ssh_enabled' => false,
            'force_load_profile' => false,
        ])
        ->assertSessionHasErrors('serial_number');
});

// Show tests
test('authenticated user can view gateway details', function () {
    $gateway = Gateway::factory()->create();

    actingAs($this->user)
        ->get(route('gateways.show', $gateway))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gateways/Show')
            ->where('gateway.id', $gateway->id)
        );
});

// Edit tests
test('authenticated user can view edit gateway form', function () {
    $gateway = Gateway::factory()->create();

    actingAs($this->user)
        ->get(route('gateways.edit', $gateway))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gateways/Edit')
            ->where('gateway.id', $gateway->id)
            ->has('sites')
            ->has('locations')
        );
});

test('authenticated user can update a gateway', function () {
    $gateway = Gateway::factory()->create(['serial_number' => 'OLD-SERIAL']);

    actingAs($this->user)
        ->put(route('gateways.update', $gateway), [
            'site_id' => $gateway->site_id,
            'serial_number' => 'NEW-SERIAL',
            'update_csv' => false,
            'update_site_code' => false,
            'ssh_enabled' => false,
            'force_load_profile' => false,
        ])
        ->assertRedirect(route('gateways.show', $gateway));

    assertDatabaseHas('gateways', [
        'id' => $gateway->id,
        'serial_number' => 'NEW-SERIAL',
        'updated_by' => $this->user->id,
    ]);
});

// Delete tests
test('authenticated user can delete a gateway', function () {
    $gateway = Gateway::factory()->create();

    actingAs($this->user)
        ->delete(route('gateways.destroy', $gateway))
        ->assertRedirect(route('gateways.index'));

    assertDatabaseMissing('gateways', [
        'id' => $gateway->id,
    ]);
});
