<?php

use Illuminate\Support\Facades\Vite;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

it('answers the health check', function () {
    get('/up')->assertOk();
});

it('serves the application shell on the start page', function () {
    withoutVite();

    get('/')
        ->assertOk()
        ->assertSee('<div id="app"></div>', false);
});

it('serves the application shell on any other page', function () {
    withoutVite();

    get('/einstellungen/profil')
        ->assertOk()
        ->assertSee('<div id="app"></div>', false);
});

it('does not serve the application shell for unknown API routes', function () {
    get('/api/unbekannt')->assertNotFound();
});

it('includes the frontend entry points in the application shell', function () {
    $hotFile = (string) tempnam(sys_get_temp_dir(), 'vite-hot');

    // finally: the temporary file is removed even if an assertion fails.
    try {
        file_put_contents($hotFile, 'http://vite.test');
        Vite::useHotFile($hotFile);

        get('/')
            ->assertOk()
            ->assertSee('http://vite.test/resources/js/main.tsx', false)
            ->assertSee('http://vite.test/resources/css/app.css', false);
    } finally {
        if (is_file($hotFile)) {
            unlink($hotFile);
        }
    }
});
