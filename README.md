# governflow

## Lokale Einrichtung (Backend)

Voraussetzungen: PHP 8.5, Composer 2, PostgreSQL 18. Tests laufen immer gegen PostgreSQL, nie gegen SQLite.

1. `composer install`
2. `cp .env.example .env` und die `DB_*`-Werte an die lokale Datenbank anpassen (das Passwort nur in `.env`, nie committen).
3. `php artisan key:generate`
4. `composer check` – führt Pint (Test-Modus), Larastan (Level 6) und Pest aus.

`.env.example` enthält nur Platzhalter; das Repository ist öffentlich.
