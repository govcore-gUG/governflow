<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

use function Pest\Laravel\get;
use function Pest\Laravel\withCookie;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against PostgreSQL (see phpunit.xml) and reset the
| database for every test.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Browser session helpers
|--------------------------------------------------------------------------
|
| Laravel skips CSRF verification while running tests. enforceCsrf() turns
| it back on for the current test; browserSession() and fromBrowser() then
| replay what the SPA does: fetch /sanctum/csrf-cookie and send the session
| cookie plus the X-XSRF-TOKEN header with every request.
|
*/

function enforceCsrf(): void
{
    app()->instance('env', 'local');
}

/**
 * @return array{session: string, xsrf: string}
 */
function browserSession(): array
{
    return sessionFrom(get('/sanctum/csrf-cookie')->assertNoContent());
}

/**
 * @param  TestResponse<Response>  $response
 * @return array{session: string, xsrf: string}
 */
function sessionFrom(TestResponse $response): array
{
    $session = $response->getCookie(config()->string('session.cookie'));
    $xsrf = $response->getCookie('XSRF-TOKEN', false);

    expect($session)->toBeInstanceOf(Cookie::class)
        ->and($xsrf)->toBeInstanceOf(Cookie::class);

    return [
        'session' => (string) $session?->getValue(),
        'xsrf' => (string) $xsrf?->getValue(),
    ];
}

/**
 * @param  array{session: string, xsrf: string}  $browser
 */
function fromBrowser(array $browser, bool $withXsrfHeader = true): Illuminate\Foundation\Testing\TestCase
{
    $test = withCookie(config()->string('session.cookie'), $browser['session'])
        ->withHeader('Referer', config()->string('app.url').'/');

    if ($withXsrfHeader) {
        $test->withHeader('X-XSRF-TOKEN', $browser['xsrf']);
    }

    return $test;
}
