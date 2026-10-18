<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;

it('creates an account with a hashed password', function () {
    artisan('user:create')
        ->expectsQuestion('Name', 'Max Muster')
        ->expectsQuestion('E-Mail-Adresse', 'Max.Muster@example.org')
        ->expectsQuestion('Passwort', 'ein-langes-passwort')
        ->expectsQuestion('Passwort wiederholen', 'ein-langes-passwort')
        ->doesntExpectOutputToContain('ein-langes-passwort')
        ->assertSuccessful();

    $user = User::query()->where('email', 'max.muster@example.org')->sole();

    expect($user->name)->toBe('Max Muster')
        ->and($user->password)->not->toBe('ein-langes-passwort')
        ->and(Hash::check('ein-langes-passwort', $user->password))->toBeTrue();
});

it('rejects a password that is too short', function () {
    artisan('user:create')
        ->expectsQuestion('Name', 'Max Muster')
        ->expectsQuestion('E-Mail-Adresse', 'max.muster@example.org')
        ->expectsQuestion('Passwort', 'zu-kurz')
        ->expectsQuestion('Passwort wiederholen', 'zu-kurz')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('rejects a password longer than 72 bytes', function () {
    $password = str_repeat('ä', 40);

    artisan('user:create')
        ->expectsQuestion('Name', 'Max Muster')
        ->expectsQuestion('E-Mail-Adresse', 'max.muster@example.org')
        ->expectsQuestion('Passwort', $password)
        ->expectsQuestion('Passwort wiederholen', $password)
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('rejects a password confirmation that does not match', function () {
    artisan('user:create')
        ->expectsQuestion('Name', 'Max Muster')
        ->expectsQuestion('E-Mail-Adresse', 'max.muster@example.org')
        ->expectsQuestion('Passwort', 'ein-langes-passwort')
        ->expectsQuestion('Passwort wiederholen', 'ein-anderes-passwort')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('rejects a duplicate email address', function () {
    User::factory()->create(['email' => 'max.muster@example.org']);

    artisan('user:create')
        ->expectsQuestion('Name', 'Max Muster')
        ->expectsQuestion('E-Mail-Adresse', 'MAX.MUSTER@example.org')
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});

it('sets a new password for an existing account', function () {
    $user = User::factory()->create([
        'email' => 'max.muster@example.org',
        'password' => 'altes-passwort-123',
    ]);
    $oldHash = $user->password;

    artisan('user:password', ['email' => 'Max.Muster@example.org'])
        ->expectsQuestion('Neues Passwort', 'neues-passwort-123')
        ->expectsQuestion('Passwort wiederholen', 'neues-passwort-123')
        ->doesntExpectOutputToContain('neues-passwort-123')
        ->assertSuccessful();

    $user->refresh();

    expect($user->password)->not->toBe($oldHash)
        ->and(Hash::check('neues-passwort-123', $user->password))->toBeTrue();
});

it('fails for an unknown email address when setting a password', function () {
    artisan('user:password', ['email' => 'erika.beispiel@example.org'])
        ->assertFailed();
});
