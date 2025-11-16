<?php

use App\Models\{Company, Division, Site, Building, Gateway, Meter, User};

test('site can be created with required relationships', function () {
    $company = Company::factory()->create();
    $division = Division::factory()->create();
    
    $site = Site::factory()->create([
        'company_id' => $company->id,
        'division_id' => $division->id,
        'code' => 'RG-01',
    ]);

    expect($site)
        ->toBeInstanceOf(Site::class)
        ->code->toBe('RG-01')
        ->company_id->toBe($company->id)
        ->division_id->toBe($division->id);
});

test('site belongs to company', function () {
    $site = Site::factory()->create();

    expect($site->company)
        ->toBeInstanceOf(Company::class)
        ->id->toBe($site->company_id);
});

test('site belongs to division', function () {
    $site = Site::factory()->create();

    expect($site->division)
        ->toBeInstanceOf(Division::class)
        ->id->toBe($site->division_id);
});

test('site has many buildings', function () {
    $site = Site::factory()->create();
    $buildings = Building::factory()->count(3)->create(['site_id' => $site->id]);

    expect($site->buildings)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(Building::class);
});

test('site has many gateways', function () {
    $site = Site::factory()->create();
    Gateway::factory()->count(2)->create(['site_id' => $site->id]);

    expect($site->gateways)->toHaveCount(2);
});

test('site has many meters', function () {
    $site = Site::factory()->create();
    Meter::factory()->count(5)->create(['site_id' => $site->id]);

    expect($site->meters)->toHaveCount(5);
});

test('site can be soft deleted', function () {
    $site = Site::factory()->create();
    $id = $site->id;

    $site->delete();

    expect(Site::find($id))->toBeNull()
        ->and(Site::withTrashed()->find($id))->not->toBeNull();
});

test('site status is online when recently updated', function () {
    $site = Site::factory()->create([
        'last_log_update' => now()->subHours(12),
    ]);

    expect($site->status)->toBe('Online');
});

test('site status is offline when not updated', function () {
    $site = Site::factory()->create([
        'last_log_update' => now()->subDays(2),
    ]);

    expect($site->status)->toBe('Offline');
});

test('site status is no data when never updated', function () {
    $site = Site::factory()->create([
        'last_log_update' => null,
    ]);

    expect($site->status)->toBe('No Data');
});

test('site online scope returns only online sites', function () {
    Site::factory()->create(['last_log_update' => now()->subHours(12)]);
    Site::factory()->create(['last_log_update' => now()->subDays(2)]);
    Site::factory()->create(['last_log_update' => null]);

    expect(Site::online()->count())->toBe(1);
});

test('site offline scope returns offline sites', function () {
    Site::factory()->create(['last_log_update' => now()->subHours(12)]);
    Site::factory()->create(['last_log_update' => now()->subDays(2)]);
    Site::factory()->create(['last_log_update' => null]);

    expect(Site::offline()->count())->toBe(2);
});

test('site has many-to-many relationship with users', function () {
    $site = Site::factory()->create();
    $users = User::factory()->count(2)->create();

    $site->users()->attach($users->pluck('id'));

    expect($site->users)->toHaveCount(2);
});

test('site code must be unique', function () {
    Site::factory()->create(['code' => 'DUPLICATE']);

    expect(fn() => Site::factory()->create(['code' => 'DUPLICATE']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('deleting company cascades to site', function () {
    $company = Company::factory()->create();
    $site = Site::factory()->create(['company_id' => $company->id]);

    $company->delete();

    expect(Site::withTrashed()->find($site->id))->toBeNull();
});
