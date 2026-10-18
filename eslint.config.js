import reactHooks from 'eslint-plugin-react-hooks';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'bootstrap/ssr/**', 'storage/**'],
    },
    {
        files: ['**/*.{ts,tsx}'],
        extends: [tseslint.configs.strictTypeChecked, reactHooks.configs.flat.recommended],
        languageOptions: {
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },
    },
);
