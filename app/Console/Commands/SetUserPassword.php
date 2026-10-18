<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksForPassword;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetUserPassword extends Command
{
    use AsksForPassword;

    protected $signature = 'user:password {email : E-Mail-Adresse des Kontos}';

    protected $description = 'Set a new password for a local account (the password is asked interactively)';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('Es gibt kein Konto mit dieser E-Mail-Adresse.');

            return self::FAILURE;
        }

        $password = $this->askForNewPassword('Neues Passwort');

        if ($password === null) {
            return self::FAILURE;
        }

        $user->update(['password' => $password]);

        $this->info('Das Passwort wurde geändert.');

        return self::SUCCESS;
    }
}
