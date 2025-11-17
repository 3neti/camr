<?php

use App\Models\Building;
use App\Models\Site;
use App\Models\User;
use function Pest\Laravel\{actingAs, get, post, put, delete};

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access buildings index', function () {
    get(route('buildings.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view buildings index', function () {
    actingAs($this->user);
    
    get(route('buildings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('buildings/Index'));
});

test('buildings index shows paginated buildings', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    Building::factory()->count(20)->create(['site_id' => $site->id]);
    
    get(route('buildings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Index')
            ->has('buildings.data', 15)
        );
});

test('buildings can be searched by code', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    Building::factory()->create([
        'site_id' => $site->id,
        'code' => 'SEARCH123',
        'description' => 'Test Building'
    ]);
    Building::factory()->create([
        'site_id' => $site->id,
        'code' => 'OTHER456',
        'description' => 'Other Building'
    ]);
    
    get(route('buildings.index', ['search' => 'SEARCH123']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Index')
            ->has('buildings.data', 1)
            ->where('buildings.data.0.code', 'SEARCH123')
        );
});

test('buildings can be filtered by site', function () {
    actingAs($this->user);
    
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();
    
    Building::factory()->count(3)->create(['site_id' => $site1->id]);
    Building::factory()->count(2)->create(['site_id' => $site2->id]);
    
    get(route('buildings.index', ['site_id' => $site1->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Index')
            ->has('buildings.data', 3)
        );
});

test('authenticated user can view create building page', function () {
    actingAs($this->user);
    
    get(route('buildings.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Create')
            ->has('sites')
        );
});

test('authenticated user can create a building', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    
    $buildingData = [
        'site_id' => $site->id,
        'code' => 'BLDG001',
        'description' => 'Test Building Description',
    ];
    
    post(route('buildings.store'), $buildingData)
        ->assertRedirect(route('buildings.index'))
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('buildings', [
        'site_id' => $site->id,
        'code' => 'BLDG001',
        'description' => 'Test Building Description',
        'created_by' => $this->user->id,
    ]);
});

test('building creation requires site_id', function () {
    actingAs($this->user);
    
    post(route('buildings.store'), [
        'code' => 'BLDG001',
        'description' => 'Test Building',
    ])->assertSessionHasErrors('site_id');
});

test('building creation requires code', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    
    post(route('buildings.store'), [
        'site_id' => $site->id,
        'description' => 'Test Building',
    ])->assertSessionHasErrors('code');
});

test('authenticated user can view a building', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    $building = Building::factory()->create(['site_id' => $site->id]);
    
    get(route('buildings.show', $building))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Show')
            ->has('building')
            ->where('building.id', $building->id)
        );
});

test('authenticated user can view edit building page', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    $building = Building::factory()->create(['site_id' => $site->id]);
    
    get(route('buildings.edit', $building))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Edit')
            ->has('building')
            ->has('sites')
        );
});

test('authenticated user can update a building', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    $building = Building::factory()->create([
        'site_id' => $site->id,
        'code' => 'OLD_CODE',
    ]);
    
    put(route('buildings.update', $building), [
        'site_id' => $site->id,
        'code' => 'NEW_CODE',
        'description' => 'Updated Description',
    ])
        ->assertRedirect(route('buildings.show', $building))
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('buildings', [
        'id' => $building->id,
        'code' => 'NEW_CODE',
        'description' => 'Updated Description',
        'updated_by' => $this->user->id,
    ]);
});

test('authenticated user can delete a building', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    $building = Building::factory()->create(['site_id' => $site->id]);
    
    delete(route('buildings.destroy', $building))
        ->assertRedirect(route('buildings.index'))
        ->assertSessionHas('success');
    
    $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
});

test('cannot delete building with locations', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    $building = Building::factory()->create(['site_id' => $site->id]);
    
    // Create a location for this building
    $building->locations()->create([
        'site_id' => $site->id,
        'code' => 'LOC001',
        'description' => 'Test Location',
        'created_by' => $this->user->id,
    ]);
    
    delete(route('buildings.destroy', $building))
        ->assertRedirect()
        ->assertSessionHas('error');
    
    $this->assertDatabaseHas('buildings', ['id' => $building->id]);
});

test('buildings index supports sorting', function () {
    actingAs($this->user);
    
    $site = Site::factory()->create();
    Building::factory()->create(['site_id' => $site->id, 'code' => 'AAAA']);
    Building::factory()->create(['site_id' => $site->id, 'code' => 'ZZZZ']);
    
    get(route('buildings.index', ['sort' => 'code', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Index')
            ->where('buildings.data.0.code', 'AAAA')
        );
});
