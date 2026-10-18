import { render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import App from './App';
import { json, mockFetch, route } from './test/fetch';

describe('App', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the application shell with the app name as heading', async () => {
        mockFetch(route('GET', '/api/me', () => json(401, {})));

        render(<App />);

        expect(screen.getByRole('banner')).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 1, name: 'governflow' })).toBeInTheDocument();
        expect(screen.getByRole('main')).toBeInTheDocument();
        expect(await screen.findByRole('button', { name: 'Anmelden' })).toBeInTheDocument();
    });
});
