<?php

use App\Models\Meter;
use App\Models\User;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->meter = Meter::factory()->create();
});

test('meter show page renders without errors when meter_data is empty', function () {
    actingAs($this->user)
        ->get(route('meters.show', $this->meter))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meters/Show')
            ->where('meter.id', $this->meter->id)
        );
});

test('meter show page renders without errors when meter_data is present', function () {
    $this->meter->load('meterData');
    
    actingAs($this->user)
        ->get(route('meters.show', $this->meter))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meters/Show')
            ->where('meter.id', $this->meter->id)
        );
});

test('meter show page renders without errors when energy summary contains null values', function () {
    actingAs($this->user)
        ->get(route('meters.show', $this->meter))
        ->assertOk();
});
