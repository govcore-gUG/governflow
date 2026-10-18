import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import App from './App';

describe('App', () => {
    it('renders the application shell with the app name as heading', () => {
        render(<App />);

        expect(screen.getByRole('banner')).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 1, name: 'governflow' })).toBeInTheDocument();
        expect(screen.getByRole('main')).toBeInTheDocument();
    });
});
