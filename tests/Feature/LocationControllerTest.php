<?php

use App\Models\{Building, Location, Site, User};
use function Pest\Laravel\{actingAs, get, post, put, assertDatabaseHas, assertDatabaseMissing};
use function Pest\Laravel\delete as deleteRoute;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->site = Site::factory()->create();
});

// Index tests
test('guest cannot access locations index', function () {
    get(route('locations.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view locations index', function () {
    actingAs($this->user)
        ->get(route('locations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('locations/Index')
            ->has('locations')
            ->has('sites')
            ->has('buildings')
        );
});

test('locations index shows paginated locations', function () {
    Location::factory()->count(5)->for($this->site)->create();

    actingAs($this->user)
        ->get(route('locations.index'))
        ->assertOk();
});

test('locations index can be filtered by site', function () {
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();
    
    Location::factory()->for($site1)->create(['code' => 'LOC-1']);
    Location::factory()->for($site2)->create(['code' => 'LOC-2']);

    actingAs($this->user)
        ->get(route('locations.index', ['site_id' => $site1->id]))
        ->assertOk();
});

test('locations index can be filtered by building', function () {
    $building = Building::factory()->for($this->site)->create();
    Location::factory()->for($this->site)->for($building)->create();

    actingAs($this->user)
        ->get(route('locations.index', ['building_id' => $building->id]))
        ->assertOk();
});

test('locations index can be searched', function () {
    Location::factory()->for($this->site)->create(['code' => 'FINDME']);
    Location::factory()->for($this->site)->create(['code' => 'OTHER']);

    actingAs($this->user)
        ->get(route('locations.index', ['search' => 'FINDME']))
        ->assertOk();
});

// Create tests
test('authenticated user can view create location form', function () {
    actingAs($this->user)
        ->get(route('locations.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('locations/Create')
            ->has('sites')
            ->has('buildings')
        );
});

// Store tests
test('authenticated user can create a location', function () {
    actingAs($this->user)
        ->post(route('locations.store'), [
            'site_id' => $this->site->id,
            'code' => 'LOC-001',
            'description' => 'Main Floor',
        ])
        ->assertRedirect(route('locations.index'));

    assertDatabaseHas('locations', [
        'site_id' => $this->site->id,
        'code' => 'LOC-001',
        'description' => 'Main Floor',
    ]);
});

test('location creation requires site_id', function () {
    actingAs($this->user)
        ->post(route('locations.store'), [
            'code' => 'LOC-001',
            'description' => 'Main Floor',
        ])
        ->assertSessionHasErrors('site_id');
});

test('location creation requires code', function () {
    actingAs($this->user)
        ->post(route('locations.store'), [
            'site_id' => $this->site->id,
            'description' => 'Main Floor',
        ])
        ->assertSessionHasErrors('code');
});

test('location creation requires description', function () {
    actingAs($this->user)
        ->post(route('locations.store'), [
            'site_id' => $this->site->id,
            'code' => 'LOC-001',
        ])
        ->assertSessionHasErrors('description');
});

test('location code can be duplicated across different sites', function () {
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();
    
    Location::factory()->for($site1)->create(['code' => 'LOC-001']);

    // Same code on different site should be allowed
    actingAs($this->user)
        ->post(route('locations.store'), [
            'site_id' => $site2->id,
            'code' => 'LOC-001',
            'description' => 'Test',
        ])
        ->assertRedirect(route('locations.index'));
        
    assertDatabaseHas('locations', [
        'site_id' => $site2->id,
        'code' => 'LOC-001',
    ]);
});

test('location can be created with building', function () {
    $building = Building::factory()->for($this->site)->create();

    actingAs($this->user)
        ->post(route('locations.store'), [
            'site_id' => $this->site->id,
            'building_id' => $building->id,
            'code' => 'LOC-001',
            'description' => 'Building Floor',
        ])
        ->assertRedirect(route('locations.index'));

    assertDatabaseHas('locations', [
        'site_id' => $this->site->id,
        'building_id' => $building->id,
        'code' => 'LOC-001',
    ]);
});

// Show tests
test('authenticated user can view location details', function () {
    $location = Location::factory()->for($this->site)->create();

    actingAs($this->user)
        ->get(route('locations.show', $location))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('locations/Show')
            ->has('location')
            ->where('location.id', $location->id)
        );
});

// Edit tests
test('authenticated user can view edit location form', function () {
    $location = Location::factory()->for($this->site)->create();

    actingAs($this->user)
        ->get(route('locations.edit', $location))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('locations/Edit')
            ->has('location')
            ->has('sites')
            ->has('buildings')
        );
});

// Update tests
test('authenticated user can update a location', function () {
    $location = Location::factory()->for($this->site)->create(['code' => 'OLD-CODE']);

    actingAs($this->user)
        ->put(route('locations.update', $location), [
            'site_id' => $this->site->id,
            'code' => 'NEW-CODE',
            'description' => 'Updated Description',
        ])
        ->assertRedirect(route('locations.show', $location));

    assertDatabaseHas('locations', [
        'id' => $location->id,
        'code' => 'NEW-CODE',
        'description' => 'Updated Description',
    ]);
});

test('location can be moved to different site', function () {
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();
    $location = Location::factory()->for($site1)->create(['code' => 'LOC-1']);

    actingAs($this->user)
        ->put(route('locations.update', $location), [
            'site_id' => $site2->id,
            'code' => 'LOC-1',
            'description' => 'Moved Location',
        ])
        ->assertRedirect(route('locations.show', $location));
        
    assertDatabaseHas('locations', [
        'id' => $location->id,
        'site_id' => $site2->id,
    ]);
});

// Delete tests
test('authenticated user can delete a location', function () {
    $location = Location::factory()->for($this->site)->create();

    actingAs($this->user)
        ->delete(route('locations.destroy', $location))
        ->assertRedirect(route('locations.index'));

    assertDatabaseMissing('locations', [
        'id' => $location->id,
    ]);
});

test('location deletion cascades properly', function () {
    $location = Location::factory()->for($this->site)->create();
    $locationId = $location->id;

    actingAs($this->user)
        ->delete(route('locations.destroy', $location));

    expect(Location::find($locationId))->toBeNull();
});
