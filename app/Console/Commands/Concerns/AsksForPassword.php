<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Asks for a password twice without echoing it. The password is never a
 * command argument, so it does not end up in the shell history, and it is
 * never printed.
 */
trait AsksForPassword
{
    /**
     * @return string|null the password, or null after printing the reason
     */
    protected function askForNewPassword(string $question): ?string
    {
        $password = (string) $this->secret($question);
        $confirmation = (string) $this->secret('Passwort wiederholen');

        if (! hash_equals($password, $confirmation)) {
            $this->error('Die Passwörter stimmen nicht überein.');

            return null;
        }

        $validator = Validator::make(['password' => $password], [
            'password' => [
                'required',
                'string',
                Password::min(12)->max(72),
                // bcrypt only uses the first 72 bytes; multi-byte characters
                // could otherwise exceed that limit within 72 characters.
                function (string $attribute, string $value, \Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('Das Passwort darf höchstens 72 Bytes lang sein.');
                    }
                },
            ],
        ], [], ['password' => 'Passwort']);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return null;
        }

        return $password;
    }
}
