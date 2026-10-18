<?php

use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;

const LOGIN_FAILED_MESSAGE = 'Die E-Mail-Adresse oder das Passwort ist falsch.';

beforeEach(function () {
    User::factory()->create([
        'name' => 'Max Muster',
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
    ]);
});

function maxMuster(): User
{
    return User::query()->where('email', 'max.muster@example.org')->sole();
}

/**
 * @return TestResponse<Response>
 */
function attemptLogin(string $email, string $password = 'falsches-passwort-123'): TestResponse
{
    return postJson('/auth/login', ['email' => $email, 'password' => $password]);
}

it('logs in with valid credentials and issues a new session id', function () {
    enforceCsrf();
    $browser = browserSession();

    $response = fromBrowser($browser)->postJson('/auth/login', [
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
    ]);

    $response->assertNoContent();
    expect(Auth::id())->toBe(maxMuster()->id);

    $after = sessionFrom($response);
    expect($after['session'])->not->toBe($browser['session']);

    fromBrowser($after)->getJson('/api/me')
        ->assertOk()
        ->assertExactJson(['name' => 'Max Muster', 'email' => 'max.muster@example.org']);
});

it('does not expose tokens in the login response', function () {
    $response = attemptLogin('max.muster@example.org', 'richtiges-passwort-123');

    $response->assertNoContent();
    expect($response->getContent())->toBe('');

    foreach ($response->headers->getCookies() as $cookie) {
        expect($cookie->getName())->toBeIn([config('session.cookie'), 'XSRF-TOKEN']);
    }
});

it('accepts the email address in a different case', function () {
    attemptLogin('Max.Muster@EXAMPLE.org', 'richtiges-passwort-123')->assertNoContent();

    expect(Auth::id())->toBe(maxMuster()->id);
});

it('answers a wrong password and an unknown email address identically', function () {
    $wrongPassword = attemptLogin('max.muster@example.org');
    $unknownEmail = attemptLogin('erika.beispiel@example.org');

    $wrongPassword->assertUnprocessable()
        ->assertJsonPath('errors.email.0', LOGIN_FAILED_MESSAGE);
    $unknownEmail->assertUnprocessable();

    expect($unknownEmail->getContent())->toBe($wrongPassword->getContent())
        ->and(Auth::check())->toBeFalse();
});

it('rejects a login without CSRF token', function () {
    enforceCsrf();
    $browser = browserSession();

    fromBrowser($browser, withXsrfHeader: false)->postJson('/auth/login', [
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
    ])->assertStatus(419);

    expect(Auth::check())->toBeFalse();
});

it('blocks the sixth failed attempt within a minute and allows it again afterwards', function () {
    foreach (range(1, 5) as $attempt) {
        attemptLogin('max.muster@example.org')->assertUnprocessable();
    }

    attemptLogin('max.muster@example.org')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');

    travel(61)->seconds();

    attemptLogin('max.muster@example.org')->assertUnprocessable();
});

it('resets the failed attempts after a successful login', function () {
    foreach (range(1, 4) as $attempt) {
        attemptLogin('max.muster@example.org')->assertUnprocessable();
    }

    attemptLogin('max.muster@example.org', 'richtiges-passwort-123')->assertNoContent();
    postJson('/auth/logout')->assertNoContent();

    foreach (range(1, 5) as $attempt) {
        attemptLogin('max.muster@example.org')->assertUnprocessable();
    }

    attemptLogin('max.muster@example.org')->assertTooManyRequests();
});

it('limits login attempts per IP address across email addresses', function () {
    foreach (range(1, 20) as $attempt) {
        attemptLogin("person{$attempt}@example.org")->assertUnprocessable();
    }

    attemptLogin('person21@example.org')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');
});

it('keeps neither the email nor the IP address in plain text in the cache', function () {
    // Use the production cache store (database) for the rate limiter; the
    // test environment uses the array store, and the limiter is already
    // resolved during boot.
    Config::set('cache.default', 'database');
    $limiter = new RateLimiter(Cache::store('database'));
    $limiter->for('login', app(RateLimiter::class)->limiter('login'));
    app()->instance(RateLimiter::class, $limiter);
    RateLimiterFacade::clearResolvedInstance(RateLimiter::class);

    foreach (range(1, 3) as $attempt) {
        attemptLogin('max.muster@example.org')->assertUnprocessable();
    }

    $cache = DB::table('cache')->get(['key', 'value']);
    expect($cache)->not->toBeEmpty();

    $stored = $cache->map(fn (object $row): string => $row->key.' '.$row->value)->implode("\n");
    expect(str_contains($stored, 'max.muster@example.org'))->toBeFalse()
        ->and(str_contains($stored, '127.0.0.1'))->toBeFalse();
});

it('ignores redirect targets passed with the login', function () {
    postJson('/auth/login?redirect=https://evil.example.org', [
        'email' => 'max.muster@example.org',
        'password' => 'richtiges-passwort-123',
        'redirect' => 'https://evil.example.org',
        'intended' => 'https://evil.example.org',
    ])
        ->assertNoContent()
        ->assertHeaderMissing('Location');

    expect(session('url.intended'))->toBeNull();
});
