<?php

use App\Models\{Company, Division, Site, User};
use function Pest\Laravel\{actingAs, get, post, put, assertDatabaseHas, assertDatabaseMissing};
use function Pest\Laravel\delete as deleteRoute;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin']);
});

// Index tests
test('guest cannot access sites index', function () {
    get(route('sites.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view sites index', function () {
    actingAs($this->user)
        ->get(route('sites.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sites/Index')
            ->has('sites')
        );
});

test('sites index shows paginated sites', function () {
    Site::factory()->count(5)->create();

    actingAs($this->user)
        ->get(route('sites.index'))
        ->assertOk();
});

// Create tests
test('authenticated user can view create site form', function () {
    Company::factory()->count(2)->create();
    Division::factory()->count(2)->create();

    actingAs($this->user)
        ->get(route('sites.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sites/Create')
            ->has('companies')
            ->has('divisions')
        );
});

// Store tests
test('authenticated user can create a site', function () {
    $company = Company::factory()->create();
    $division = Division::factory()->create();

    actingAs($this->user)
        ->post(route('sites.store'), [
            'company_id' => $company->id,
            'division_id' => $division->id,
            'code' => 'RG-01',
        ])
        ->assertRedirect(route('sites.index'));

    assertDatabaseHas('sites', [
        'company_id' => $company->id,
        'division_id' => $division->id,
        'code' => 'RG-01',
    ]);
});

test('site creation requires company_id', function () {
    $division = Division::factory()->create();

    actingAs($this->user)
        ->post(route('sites.store'), [
            'division_id' => $division->id,
            'code' => 'RG-01',
        ])
        ->assertSessionHasErrors('company_id');
});

test('site creation requires division_id', function () {
    $company = Company::factory()->create();

    actingAs($this->user)
        ->post(route('sites.store'), [
            'company_id' => $company->id,
            'code' => 'RG-01',
        ])
        ->assertSessionHasErrors('division_id');
});

test('site creation requires unique code', function () {
    Site::factory()->create(['code' => 'DUPLICATE']);

    actingAs($this->user)
        ->post(route('sites.store'), [
            'company_id' => Company::factory()->create()->id,
            'division_id' => Division::factory()->create()->id,
            'code' => 'DUPLICATE',
        ])
        ->assertSessionHasErrors('code');
});

// Show tests
test('authenticated user can view site details', function () {
    $site = Site::factory()->create();

    actingAs($this->user)
        ->get(route('sites.show', $site))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sites/Show')
            ->has('site')
            ->where('site.id', $site->id)
        );
});

// Edit tests
test('authenticated user can view edit site form', function () {
    $site = Site::factory()->create();

    actingAs($this->user)
        ->get(route('sites.edit', $site))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sites/Edit')
            ->has('site')
            ->has('companies')
            ->has('divisions')
        );
});

// Update tests
test('authenticated user can update a site', function () {
    $site = Site::factory()->create(['code' => 'OLD-CODE']);
    $newCompany = Company::factory()->create();

    actingAs($this->user)
        ->put(route('sites.update', $site), [
            'company_id' => $newCompany->id,
            'division_id' => $site->division_id,
            'code' => 'NEW-CODE',
        ])
        ->assertRedirect(route('sites.show', $site));

    assertDatabaseHas('sites', [
        'id' => $site->id,
        'code' => 'NEW-CODE',
        'company_id' => $newCompany->id,
    ]);
});

// Delete tests
test('authenticated user can delete a site', function () {
    $site = Site::factory()->create();

    actingAs($this->user)
        ->delete(route('sites.destroy', $site))
        ->assertRedirect(route('sites.index'));

    assertDatabaseMissing('sites', [
        'id' => $site->id,
        'deleted_at' => null,
    ]);
});

test('site is soft deleted', function () {
    $site = Site::factory()->create();

    actingAs($this->user)
        ->delete(route('sites.destroy', $site));

    expect(Site::withTrashed()->find($site->id))->not->toBeNull();
});
