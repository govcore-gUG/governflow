import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

const container = document.getElementById('app');

if (container === null) {
    throw new Error('Mount point #app not found.');
}

createRoot(container).render(
    <StrictMode>
        <App />
    </StrictMode>,
);
