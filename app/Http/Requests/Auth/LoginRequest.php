<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    /**
     * Failed attempts allowed per email address and IP address within
     * DECAY_SECONDS.
     */
    public const MAX_FAILED_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public const TOO_MANY_ATTEMPTS_MESSAGE = 'Zu viele Anmeldeversuche. Bitte warten Sie eine Minute und versuchen Sie es dann erneut.';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        return [
            'email' => $this->normalizedEmail(),
            'password' => $this->string('password')->toString(),
        ];
    }

    /**
     * Throw a 429 response with Retry-After once the failed attempts for this
     * email address and IP address are used up.
     *
     * @throws ThrottleRequestsException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_FAILED_ATTEMPTS)) {
            return;
        }

        throw new ThrottleRequestsException(
            self::TOO_MANY_ATTEMPTS_MESSAGE,
            headers: ['Retry-After' => RateLimiter::availableIn($this->throttleKey())],
        );
    }

    public function recordFailedAttempt(): void
    {
        RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
    }

    public function clearFailedAttempts(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Hashed so that the cache never holds the email or IP address in
     * plain text.
     */
    private function throttleKey(): string
    {
        return 'login-failed:'.hash('sha256', $this->normalizedEmail().'|'.$this->ip());
    }

    private function normalizedEmail(): string
    {
        return Str::lower(trim($this->string('email')->toString()));
    }
}
