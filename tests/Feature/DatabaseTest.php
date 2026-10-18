<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('runs the test suite against PostgreSQL', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

it('migrates the schema and persists models', function () {
    $user = User::factory()->create(['name' => 'Max Muster']);

    expect(User::query()->whereKey($user->getKey())->value('name'))->toBe('Max Muster');
});
