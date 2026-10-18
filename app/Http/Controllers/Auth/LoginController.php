<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Local login with email address and password for the SPA (BFF): the
 * browser only ever holds the HttpOnly session cookie and the XSRF cookie.
 */
class LoginController extends Controller
{
    public const FAILED_MESSAGE = 'Die E-Mail-Adresse oder das Passwort ist falsch.';

    /**
     * Neither responses nor redirects depend on request parameters, so there
     * is no open redirect.
     *
     * @throws ValidationException
     */
    public function store(LoginRequest $request): Response
    {
        $request->ensureIsNotRateLimited();

        // SessionGuard::attempt() runs inside Illuminate\Support\Timebox
        // (auth.timebox_duration), so unknown email addresses and wrong
        // passwords take the same time. No "remember me".
        if (! Auth::guard('web')->attempt($request->credentials())) {
            $request->recordFailedAttempt();

            // Deliberately without email address, password or IP address.
            Log::info('Failed login attempt.');

            throw ValidationException::withMessages(['email' => self::FAILED_MESSAGE]);
        }

        $request->clearFailedAttempts();
        $request->session()->regenerate();

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
