<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\postJson;

it('stores sessions in the database with the user id only', function () {
    Config::set('session.driver', 'database');

    $user = User::factory()->create([
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
    ]);

    postJson('/auth/login', [
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
    ])->assertNoContent();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(1);
});

it('has no columns for IP address or user agent in the sessions table', function () {
    expect(Schema::getColumnListing('sessions'))
        ->toEqualCanonicalizing(['id', 'user_id', 'payload', 'last_activity']);
});

it('stores only the necessary user fields', function () {
    expect(Schema::getColumnListing('users'))
        ->toEqualCanonicalizing(['id', 'name', 'email', 'password', 'created_at', 'updated_at']);
});

it('has no password reset token table', function () {
    expect(Schema::hasTable('password_reset_tokens'))->toBeFalse();
});

it('stores email addresses in lower case', function () {
    $user = User::factory()->create(['email' => 'Max.Muster@Example.ORG']);

    expect($user->fresh()?->email)->toBe('max.muster@example.org');
});
