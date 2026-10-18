import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { empty, json, mockFetch, route } from '../test/fetch';
import LoginForm from './LoginForm';

function fillAndSubmit() {
    fireEvent.change(screen.getByLabelText('E-Mail-Adresse'), { target: { value: 'max.muster@example.org' } });
    fireEvent.change(screen.getByLabelText('Passwort'), { target: { value: 'geheimes-passwort' } });
    fireEvent.click(screen.getByRole('button', { name: 'Anmelden' }));
}

describe('LoginForm', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    });

    it('fetches the CSRF cookie and sends the credentials with the XSRF header', async () => {
        document.cookie = 'XSRF-TOKEN=token%3Dabc';
        const requests = mockFetch(
            route('GET', '/sanctum/csrf-cookie', () => empty(204)),
            route('POST', '/auth/login', () => empty(204)),
        );
        const onLoggedIn = vi.fn();

        render(<LoginForm onLoggedIn={onLoggedIn} />);
        expect(screen.getByLabelText('E-Mail-Adresse')).toHaveAttribute('autocomplete', 'username');
        expect(screen.getByLabelText('Passwort')).toHaveAttribute('autocomplete', 'current-password');

        fillAndSubmit();

        await waitFor(() => {
            expect(onLoggedIn).toHaveBeenCalledOnce();
        });
        expect(requests.map((request) => `${request.method} ${request.url}`)).toEqual([
            'GET /sanctum/csrf-cookie',
            'POST /auth/login',
        ]);
        expect(requests[1]?.body).toEqual({ email: 'max.muster@example.org', password: 'geheimes-passwort' });
        expect(requests[1]?.headers.get('X-XSRF-TOKEN')).toBe('token=abc');
    });

    it('clears the password field after submitting', async () => {
        mockFetch(
            route('GET', '/sanctum/csrf-cookie', () => empty(204)),
            route('POST', '/auth/login', () => json(422, { message: 'x' })),
        );

        render(<LoginForm onLoggedIn={vi.fn()} />);
        fillAndSubmit();

        expect(screen.getByLabelText('Passwort')).toHaveValue('');
        await screen.findByRole('alert');
    });

    it('shows the message for wrong credentials on 422', async () => {
        mockFetch(
            route('GET', '/sanctum/csrf-cookie', () => empty(204)),
            route('POST', '/auth/login', () => json(422, { message: 'x' })),
        );

        render(<LoginForm onLoggedIn={vi.fn()} />);
        fillAndSubmit();

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Die E-Mail-Adresse oder das Passwort ist falsch.',
        );
    });

    it('shows the message for too many attempts on 429', async () => {
        mockFetch(
            route('GET', '/sanctum/csrf-cookie', () => empty(204)),
            route('POST', '/auth/login', () => json(429, { message: 'x' })),
        );

        render(<LoginForm onLoggedIn={vi.fn()} />);
        fillAndSubmit();

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Zu viele Anmeldeversuche. Bitte warten Sie eine Minute und versuchen Sie es dann erneut.',
        );
    });

    it('never writes the password to web storage', async () => {
        mockFetch(
            route('GET', '/sanctum/csrf-cookie', () => empty(204)),
            route('POST', '/auth/login', () => empty(204)),
        );
        const onLoggedIn = vi.fn();

        render(<LoginForm onLoggedIn={onLoggedIn} />);
        fillAndSubmit();
        await waitFor(() => {
            expect(onLoggedIn).toHaveBeenCalledOnce();
        });

        expect(localStorage.length).toBe(0);
        expect(sessionStorage.length).toBe(0);
    });
});
