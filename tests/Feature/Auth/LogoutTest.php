<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * @return array{session: string, xsrf: string}
 */
function loggedInBrowser(): array
{
    User::factory()->create([
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
    ]);

    enforceCsrf();

    $response = fromBrowser(browserSession())->postJson('/auth/login', [
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
    ])->assertNoContent();

    return sessionFrom($response);
}

it('rejects a logout without CSRF token', function () {
    $browser = loggedInBrowser();

    fromBrowser($browser, withXsrfHeader: false)->postJson('/auth/logout')->assertStatus(419);

    expect(Auth::check())->toBeTrue();
});

it('logs out with CSRF token and invalidates the session', function () {
    $browser = loggedInBrowser();

    $response = fromBrowser($browser)->postJson('/auth/logout')->assertNoContent();

    expect(Auth::check())->toBeFalse();

    fromBrowser(sessionFrom($response))->getJson('/api/me')->assertUnauthorized();
    fromBrowser($browser)->getJson('/api/me')->assertUnauthorized();
});
