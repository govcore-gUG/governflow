<?php

use function Pest\Laravel\get;

it('answers the health check', function () {
    get('/up')->assertOk();
});

it('serves the application shell on the start page', function () {
    get('/')
        ->assertOk()
        ->assertSee('<div id="app"></div>', false);
});
