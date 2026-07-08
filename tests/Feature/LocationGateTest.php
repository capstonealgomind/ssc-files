<?php

namespace Tests\Feature;

use App\Models\LocationRangeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_accessible_when_location_range_is_disabled(): void
    {
        LocationRangeSetting::current()->update([
            'is_enabled' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Welcome'));
    }

    public function test_home_redirects_to_location_gate_when_range_is_enabled(): void
    {
        LocationRangeSetting::current()->update([
            'is_enabled' => true,
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'range_meters' => 500,
        ]);

        $this->get('/')
            ->assertRedirect(route('location.show'));
    }

    public function test_location_verify_allows_access_within_range(): void
    {
        LocationRangeSetting::current()->update([
            'is_enabled' => true,
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'range_meters' => 500,
        ]);

        $this->post('/location/verify', [
            'latitude' => 14.5995,
            'longitude' => 120.9842,
        ])->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Welcome'));
    }

    public function test_location_verify_denies_access_outside_range(): void
    {
        LocationRangeSetting::current()->update([
            'is_enabled' => true,
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'range_meters' => 500,
        ]);

        $this->post('/location/verify', [
            'latitude' => 14.6500,
            'longitude' => 121.0500,
        ])
            ->assertRedirect(route('location.show'))
            ->assertSessionHasErrors('location');
    }
}
