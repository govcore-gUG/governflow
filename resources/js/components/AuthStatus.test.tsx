import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { empty, json, mockFetch, route } from '../test/fetch';
import AuthStatus from './AuthStatus';

const maxMuster = { name: 'Max Muster', email: 'max.muster@example.org' };

describe('AuthStatus', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('shows the login form without a session', async () => {
        mockFetch(route('GET', '/api/me', () => json(401, { message: 'Unauthenticated.' })));

        render(<AuthStatus />);

        expect(await screen.findByRole('button', { name: 'Anmelden' })).toBeInTheDocument();
        expect(screen.getByLabelText('E-Mail-Adresse')).toBeInTheDocument();
        expect(screen.getByLabelText('Passwort')).toBeInTheDocument();
    });

    it('shows the signed-in user and the logout button with a session', async () => {
        mockFetch(route('GET', '/api/me', () => json(200, maxMuster)));

        render(<AuthStatus />);

        expect(await screen.findByText('Angemeldet als Max Muster')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Abmelden' })).toBeInTheDocument();
    });

    it('shows the signed-in user after a successful login', async () => {
        let signedIn = false;
        mockFetch(
            route('GET', '/api/me', () => (signedIn ? json(200, maxMuster) : json(401, {}))),
            route('GET', '/sanctum/csrf-cookie', () => empty(204)),
            route('POST', '/auth/login', () => {
                signedIn = true;
                return empty(204);
            }),
        );

        render(<AuthStatus />);

        fireEvent.change(await screen.findByLabelText('E-Mail-Adresse'), {
            target: { value: 'max.muster@example.org' },
        });
        fireEvent.change(screen.getByLabelText('Passwort'), { target: { value: 'geheimes-passwort' } });
        fireEvent.click(screen.getByRole('button', { name: 'Anmelden' }));

        expect(await screen.findByText('Angemeldet als Max Muster')).toBeInTheDocument();
    });

    it('calls the logout endpoint and shows the login form again', async () => {
        let signedIn = true;
        const requests = mockFetch(
            route('GET', '/api/me', () => (signedIn ? json(200, maxMuster) : json(401, {}))),
            route('POST', '/auth/logout', () => {
                signedIn = false;
                return empty(204);
            }),
        );

        render(<AuthStatus />);
        fireEvent.click(await screen.findByRole('button', { name: 'Abmelden' }));

        expect(await screen.findByRole('button', { name: 'Anmelden' })).toBeInTheDocument();
        expect(requests.some((request) => request.method === 'POST' && request.url === '/auth/logout')).toBe(true);
    });

    it('shows a hint when the session cannot be checked', async () => {
        mockFetch(route('GET', '/api/me', () => json(500, {})));

        render(<AuthStatus />);

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Die Anmeldung konnte nicht geprüft werden. Bitte laden Sie die Seite neu.',
        );
    });
});
