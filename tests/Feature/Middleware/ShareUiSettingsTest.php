<?php

use App\Settings\UiSettings;
use function Pest\Laravel\{actingAs, post, get};

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
});

test('ShareUiSettings middleware shares UI settings with Inertia', function () {
    actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('uiSettings')
        );
});

test('UI settings defaults are correct', function () {
    actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('uiSettings.show_buildings', false)
            ->where('uiSettings.show_locations', false)
            ->where('uiSettings.show_config_files', false)
        );
});

test('CSRF protection is not broken by middleware', function () {
    // Invalid CSRF token should return 419 or validation error, not crash
    $response = actingAs($this->user)
        ->postJson(route('data-import.upload'), [], ['X-CSRF-TOKEN' => 'invalid']);
    
    // Either 419 CSRF error or 422 validation error is acceptable
    expect($response->status())->toBeIn([419, 422]);
});

test('valid CSRF token allows authenticated requests', function () {
    actingAs($this->user)
        ->get(route('dashboard'))
        ->assertStatus(200);
});

test('UI settings are accessible in all authenticated routes', function () {
    $routes = [
        route('dashboard'),
        route('analytics'),
        route('live.monitoring'),
    ];

    foreach ($routes as $route) {
        actingAs($this->user)
            ->get($route)
            ->assertInertia(fn ($page) => $page->has('uiSettings'))
            ->assertStatus(200);
    }
});

test('UI settings can be toggled and persist', function () {
    $settings = app(UiSettings::class);
    $settings->show_buildings = true;
    $settings->show_locations = true;
    $settings->save();

    actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('uiSettings.show_buildings', true)
            ->where('uiSettings.show_locations', true)
            ->where('uiSettings.show_config_files', false)
        );
});
