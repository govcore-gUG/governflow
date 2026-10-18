import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

// Separate from vite.config.ts: laravel-vite-plugin treats a Vitest run as a
// dev server and refuses to start in CI, and tests do not need it.
export default defineConfig({
    plugins: [react()],
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.{ts,tsx}'],
        setupFiles: ['resources/js/test/setup.ts'],
    },
});
