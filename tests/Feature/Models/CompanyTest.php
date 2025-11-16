<?php

use App\Models\Company;
use App\Models\Site;
use App\Models\User;

use function Pest\Laravel\{assertDatabaseHas};

test('company can be created', function () {
    $company = Company::factory()->create([
        'code' => 'RLC',
        'name' => 'Robinsons Land Corporation',
    ]);

    expect($company)
        ->toBeInstanceOf(Company::class)
        ->code->toBe('RLC')
        ->name->toBe('Robinsons Land Corporation');

    assertDatabaseHas('companies', [
        'code' => 'RLC',
        'name' => 'Robinsons Land Corporation',
    ]);
});

test('company has sites relationship', function () {
    $company = Company::factory()->create();

    expect($company->sites())
        ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('company code must be unique', function () {
    Company::factory()->create(['code' => 'TEST']);

    expect(fn() => Company::factory()->create(['code' => 'TEST']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('company has timestamps', function () {
    $company = Company::factory()->create();

    expect($company->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($company->updated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('company can be updated', function () {
    $company = Company::factory()->create(['name' => 'Old Name']);

    $company->update(['name' => 'New Name']);

    expect($company->fresh()->name)->toBe('New Name');
    assertDatabaseHas('companies', ['name' => 'New Name']);
});

test('company can be deleted', function () {
    $company = Company::factory()->create();
    $id = $company->id;

    $company->delete();

    expect(Company::find($id))->toBeNull();
});

test('company has created_by relationship', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['created_by' => $user->id]);

    expect($company->createdBy)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('company has updated_by relationship', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['updated_by' => $user->id]);

    expect($company->updatedBy)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});
