<?php

use App\Models\{Meter, Gateway, Location, User};
use function Pest\Laravel\{actingAs, assertDatabaseHas, assertDatabaseMissing, get, post, put, delete};

beforeEach(function () {
    $this->user = User::factory()->create();
});

// Index tests
test('guest cannot access meters index', function () {
    get(route('meters.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view meters index', function () {
    actingAs($this->user)
        ->get(route('meters.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('meters/Index'));
});

test('meters index shows paginated meters', function () {
    Meter::factory(20)->create();

    actingAs($this->user)
        ->get(route('meters.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meters/Index')
            ->has('meters.data', 15)
        );
});

test('meters can be searched by name', function () {
    Meter::factory()->create(['name' => 'METER-12345']);
    Meter::factory()->create(['name' => 'METER-99999']);

    actingAs($this->user)
        ->get(route('meters.index', ['search' => 'METER-12345']))
        ->assertInertia(fn ($page) => $page
            ->where('meters.data.0.name', 'METER-12345')
            ->has('meters.data', 1)
        );
});

test('meters can be filtered by gateway', function () {
    $gateway = Gateway::factory()->create();
    Meter::factory()->create(['gateway_id' => $gateway->id]);
    Meter::factory()->create();

    actingAs($this->user)
        ->get(route('meters.index', ['gateway_id' => $gateway->id]))
        ->assertInertia(fn ($page) => $page->has('meters.data', 1));
});

// Create tests
test('authenticated user can view create meter form', function () {
    actingAs($this->user)
        ->get(route('meters.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meters/Create')
            ->has('gateways')
            ->has('locations')
        );
});

test('authenticated user can create a meter', function () {
    $gateway = Gateway::factory()->create();
    $location = Location::factory()->create();

    actingAs($this->user)
        ->post(route('meters.store'), [
            'gateway_id' => $gateway->id,
            'location_id' => $location->id,
            'name' => 'METER-TEST-001',
            'type' => 'Electric',
            'brand' => 'Schneider',
            'customer_name' => 'Test Customer',
            'status' => 'Active',
            'is_addressable' => false,
            'has_load_profile' => true,
        ])
        ->assertRedirect(route('meters.index'));

    assertDatabaseHas('meters', [
        'name' => 'METER-TEST-001',
        'gateway_id' => $gateway->id,
        'created_by' => $this->user->id,
    ]);
});

// Validation tests
test('meter creation requires gateway_id', function () {
    actingAs($this->user)
        ->post(route('meters.store'), [
            'name' => 'METER-TEST',
            'type' => 'Electric',
            'brand' => 'Test',
            'customer_name' => 'Test',
            'status' => 'Active',
        ])
        ->assertSessionHasErrors('gateway_id');
});

test('meter creation requires name', function () {
    $gateway = Gateway::factory()->create();

    actingAs($this->user)
        ->post(route('meters.store'), [
            'gateway_id' => $gateway->id,
            'type' => 'Electric',
            'brand' => 'Test',
            'customer_name' => 'Test',
            'status' => 'Active',
        ])
        ->assertSessionHasErrors('name');
});

test('meter creation requires unique name', function () {
    $gateway = Gateway::factory()->create();
    Meter::factory()->create(['name' => 'METER-DUPLICATE']);

    actingAs($this->user)
        ->post(route('meters.store'), [
            'gateway_id' => $gateway->id,
            'name' => 'METER-DUPLICATE',
            'type' => 'Electric',
            'brand' => 'Test',
            'customer_name' => 'Test',
            'status' => 'Active',
            'is_addressable' => false,
            'has_load_profile' => false,
        ])
        ->assertSessionHasErrors('name');
});

test('meter creation requires valid status', function () {
    $gateway = Gateway::factory()->create();

    actingAs($this->user)
        ->post(route('meters.store'), [
            'gateway_id' => $gateway->id,
            'name' => 'METER-TEST',
            'type' => 'Electric',
            'brand' => 'Test',
            'customer_name' => 'Test',
            'status' => 'Invalid',
            'is_addressable' => false,
            'has_load_profile' => false,
        ])
        ->assertSessionHasErrors('status');
});

// Show tests
test('authenticated user can view meter details', function () {
    $meter = Meter::factory()->create();

    actingAs($this->user)
        ->get(route('meters.show', $meter))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meters/Show')
            ->where('meter.id', $meter->id)
        );
});

test('meter show page displays latest meter reading data', function () {
    $meter = Meter::factory()->create(['name' => '030011100592']);
    
    // Create meter data
    $meterData = \App\Models\MeterData::factory()->create([
        'meter_name' => '030011100592',
        'watt' => 13.88,
        'wh_total' => 61450.9102,
        'wh_delivered' => 0,
        'vrms_a' => 219.4,
        'vrms_b' => 223.6,
        'vrms_c' => 224.9,
        'irms_a' => 28.7,
        'irms_b' => 14.1,
        'irms_c' => 22.2,
    ]);

    actingAs($this->user)
        ->get(route('meters.show', $meter))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meters/Show')
            ->where('meter.id', $meter->id)
            ->has('meter.meter_data', 1)
            ->where('meter.meter_data.0.watt', 13.88)
            ->where('meter.meter_data.0.wh_total', 61450.9102)
            ->where('meter.meter_data.0.wh_delivered', 0)
        );
});

// Edit tests
test('authenticated user can view edit meter form', function () {
    $meter = Meter::factory()->create();

    actingAs($this->user)
        ->get(route('meters.edit', $meter))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meters/Edit')
            ->where('meter.id', $meter->id)
            ->has('gateways')
            ->has('locations')
        );
});

test('authenticated user can update a meter', function () {
    $meter = Meter::factory()->create(['name' => 'OLD-NAME']);

    actingAs($this->user)
        ->put(route('meters.update', $meter), [
            'gateway_id' => $meter->gateway_id,
            'name' => 'NEW-NAME',
            'type' => $meter->type,
            'brand' => $meter->brand,
            'customer_name' => $meter->customer_name,
            'status' => $meter->status,
            'is_addressable' => false,
            'has_load_profile' => false,
        ])
        ->assertRedirect(route('meters.show', $meter));

    assertDatabaseHas('meters', [
        'id' => $meter->id,
        'name' => 'NEW-NAME',
        'updated_by' => $this->user->id,
    ]);
});

// Delete tests
test('authenticated user can delete a meter', function () {
    $meter = Meter::factory()->create();

    actingAs($this->user)
        ->delete(route('meters.destroy', $meter))
        ->assertRedirect(route('meters.index'));

    assertDatabaseMissing('meters', [
        'id' => $meter->id,
    ]);
});
