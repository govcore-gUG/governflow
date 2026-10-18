<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Only the data the SPA needs to show who is signed in.
Route::get('/me', fn (Request $request): array => [
    'name' => $request->user()?->name,
    'email' => $request->user()?->email,
])->middleware('auth:sanctum');
