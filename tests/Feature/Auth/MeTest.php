<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('answers 401 without a session', function () {
    getJson('/api/me')->assertUnauthorized();
});

it('returns exactly name and email of the signed-in user', function () {
    $user = User::factory()->create([
        'name' => 'Max Muster',
        'email' => 'max.muster@example.org',
    ]);

    actingAs($user)
        ->getJson('/api/me')
        ->assertOk()
        ->assertExactJson(['name' => 'Max Muster', 'email' => 'max.muster@example.org']);
});
