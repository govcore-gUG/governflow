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

## Anmeldung (Abweichung von STACK.md)

STACK.md sieht für die Identität Authentik (OIDC) mit `laravel/socialite` und
`socialiteproviders/authentik` vor. Davon weicht governflow auf Entscheidung von
Lars (2026-10-18, GF-1) bewusst ab: Im Grundgerüst gibt es **kein Authentik**,
sondern einen lokalen Login mit E-Mail-Adresse und Passwort über Laravel-Session
und Sanctum; der Passwort-Hash (bcrypt) liegt in der Datenbank. Der
BFF-Grundsatz bleibt: Der Browser hält nur das HttpOnly-Session-Cookie und das
XSRF-Cookie, keine Tokens. Der Wechsel auf OIDC folgt in einem eigenen Ticket.

Es gibt keine Selbstregistrierung und kein Zurücksetzen des Passworts per
E-Mail. Sitzungen werden ohne IP-Adresse und User-Agent gespeichert.

Hinter einem Reverse Proxy (z. B. Traefik/Coolify) müssen die vertrauenswürdigen
Proxys konfiguriert werden (`trustProxies`), sonst sieht Laravel nur die
Proxy-IP und alle Anmeldungen teilen sich ein IP-Limit. Das gehört zum
Deploy-Ticket.

## Konten verwalten

Konten legt nur die Administration auf dem Server an. Passwörter werden
verdeckt abgefragt und nie als Argument übergeben, damit sie nicht in der
Shell-Historie landen. Erlaubt sind 12 bis 72 Zeichen (höchstens 72 Bytes).

- `php artisan user:create` – fragt Name, E-Mail-Adresse und Passwort ab und
  legt das Konto an.
- `php artisan user:password <email>` – setzt für ein bestehendes Konto ein
  neues Passwort (Ersatz für „Passwort vergessen“).

Bedienung für Nutzende: [docs/handbuch/anmelden.md](docs/handbuch/anmelden.md).
