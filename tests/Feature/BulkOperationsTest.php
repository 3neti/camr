<?php

use App\Models\{Site, Gateway, Meter, User};
use function Pest\Laravel\{actingAs, post};

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

// Sites Bulk Delete Tests
test('can bulk delete multiple sites', function () {
    // Create sites with no relationships to avoid cascade issues
    $sites = Site::factory()->count(3)->create();
    $siteIds = $sites->pluck('id')->toArray();
    
    $response = post('/sites/bulk-delete', ['ids' => $siteIds]);
    
    // Check that at least the redirect and success message work
    $response->assertRedirect(route('sites.index'))
        ->assertSessionHas('success');
    
    // Verify sites are deleted
    expect(Site::whereIn('id', $siteIds)->count())->toBe(0);
});

test('sites bulk delete requires ids array', function () {
    post('/sites/bulk-delete', [])
        ->assertSessionHasErrors('ids');
});

test('sites bulk delete validates site existence', function () {
    post('/sites/bulk-delete', ['ids' => [99999, 99998]])
        ->assertSessionHasErrors();
});

test('sites bulk delete returns count in success message', function () {
    $sites = Site::factory()->count(5)->create();
    
    post('/sites/bulk-delete', ['ids' => $sites->pluck('id')->toArray()])
        ->assertSessionHas('success', '5 sites deleted successfully.');
});

// Gateways Bulk Delete Tests
test('can bulk delete multiple gateways', function () {
    $site = Site::factory()->create();
    $gateways = Gateway::factory()->count(3)->create(['site_id' => $site->id]);
    $gatewayIds = $gateways->pluck('id')->toArray();
    
    post('/gateways/bulk-delete', ['ids' => $gatewayIds])
        ->assertRedirect(route('gateways.index'))
        ->assertSessionHas('success');
    
    foreach ($gatewayIds as $id) {
        $this->assertDatabaseMissing('gateways', ['id' => $id]);
    }
});

test('gateways bulk delete requires ids array', function () {
    post('/gateways/bulk-delete', [])
        ->assertSessionHasErrors('ids');
});

test('gateways bulk delete validates gateway existence', function () {
    post('/gateways/bulk-delete', ['ids' => [99999, 99998]])
        ->assertSessionHasErrors();
});

// Meters Bulk Delete Tests
test('can bulk delete multiple meters', function () {
    $site = Site::factory()->create();
    $gateway = Gateway::factory()->create(['site_id' => $site->id]);
    $meters = Meter::factory()->count(3)->create([
        'site_id' => $site->id,
        'gateway_id' => $gateway->id,
    ]);
    $meterIds = $meters->pluck('id')->toArray();
    
    post('/meters/bulk-delete', ['ids' => $meterIds])
        ->assertRedirect(route('meters.index'))
        ->assertSessionHas('success');
    
    foreach ($meterIds as $id) {
        $this->assertDatabaseMissing('meters', ['id' => $id]);
    }
});

test('meters bulk delete requires ids array', function () {
    post('/meters/bulk-delete', [])
        ->assertSessionHasErrors('ids');
});

test('meters bulk delete validates meter existence', function () {
    post('/meters/bulk-delete', ['ids' => [99999, 99998]])
        ->assertSessionHasErrors();
});

// Users Bulk Delete Tests
test('can bulk delete multiple users', function () {
    $users = User::factory()->count(3)->create();
    $userIds = $users->pluck('id')->toArray();
    
    post('/users/bulk-delete', ['ids' => $userIds])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');
    
    foreach ($userIds as $id) {
        $this->assertDatabaseMissing('users', ['id' => $id]);
    }
});

test('users bulk delete requires ids array', function () {
    post('/users/bulk-delete', [])
        ->assertSessionHasErrors('ids');
});

test('users bulk delete validates user existence', function () {
    post('/users/bulk-delete', ['ids' => [99999, 99998]])
        ->assertSessionHasErrors();
});

test('cannot bulk delete own user account', function () {
    $otherUser = User::factory()->create();
    
    post('/users/bulk-delete', ['ids' => [$this->user->id, $otherUser->id]])
        ->assertRedirect()
        ->assertSessionHas('success', '1 users deleted successfully.');
    
    // Own account should still exist
    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    // Other user should be deleted
    $this->assertDatabaseMissing('users', ['id' => $otherUser->id]);
});

test('bulk delete with only own id returns error', function () {
    post('/users/bulk-delete', ['ids' => [$this->user->id]])
        ->assertRedirect()
        ->assertSessionHas('error');
    
    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
});

// Mixed scenarios
test('bulk delete handles empty array gracefully', function () {
    post('/sites/bulk-delete', ['ids' => []])
        ->assertSessionHasErrors('ids');
});

test('bulk delete is atomic - all or nothing', function () {
    $sites = Site::factory()->count(2)->create();
    $validId = $sites->first()->id;
    $invalidId = 99999;
    
    post('/sites/bulk-delete', ['ids' => [$validId, $invalidId]])
        ->assertSessionHasErrors();
    
    // Valid site should still exist (transaction rolled back)
    $this->assertDatabaseHas('sites', ['id' => $validId]);
});
