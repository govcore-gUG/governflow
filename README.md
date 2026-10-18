# governflow

## Lokale Einrichtung (Backend)

Voraussetzungen: PHP 8.5, Composer 2, PostgreSQL 18. Tests laufen immer gegen PostgreSQL, nie gegen SQLite.

1. `composer install`
2. `cp .env.example .env` und die `DB_*`-Werte an die lokale Datenbank anpassen (das Passwort nur in `.env`, nie committen).
3. `php artisan key:generate`
4. `composer check` – führt Pint (Test-Modus), Larastan (Level 6) und Pest aus.

## Lokale Einrichtung (Frontend)

Voraussetzungen: Node.js 24 und npm. Das Frontend ist eine React-SPA (React 19.2, TypeScript, Vite 8, Tailwind 4) unter `resources/js/`.

1. `npm ci`
2. `npm run dev` – Vite-Entwicklungsserver; die Seite selbst liefert Laravel aus (z. B. `php artisan serve`).
3. `npm run check` – führt ESLint, TypeScript (`tsc --noEmit`), Vitest und den Build aus.

`.env.example` enthält nur Platzhalter; das Repository ist öffentlich.
