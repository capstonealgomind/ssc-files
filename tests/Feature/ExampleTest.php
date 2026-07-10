<?php

use App\Models\LocationRangeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application returns a successful response', function () {
    LocationRangeSetting::current()->update([
        'is_enabled' => false,
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);
});
