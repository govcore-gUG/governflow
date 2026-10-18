/**
 * Thin fetch wrapper for the Laravel backend (BFF). The browser only holds
 * the HttpOnly session cookie and the XSRF-TOKEN cookie set by Laravel; no
 * tokens are kept in JavaScript or in storage.
 */

export interface Me {
    name: string;
    email: string;
}

export type LoginResult = 'ok' | 'invalid' | 'throttled';

export class ApiError extends Error {
    constructor(public readonly status: number) {
        super(`Request failed with status ${String(status)}`);
        this.name = 'ApiError';
    }
}

function readCookie(name: string): string | null {
    const prefix = `${name}=`;

    for (const part of document.cookie.split(';')) {
        const cookie = part.trim();

        if (cookie.startsWith(prefix)) {
            return decodeURIComponent(cookie.slice(prefix.length));
        }
    }

    return null;
}

async function request(method: 'GET' | 'POST', url: string, body?: unknown): Promise<Response> {
    const headers = new Headers({ Accept: 'application/json' });
    const xsrf = readCookie('XSRF-TOKEN');

    if (xsrf !== null) {
        headers.set('X-XSRF-TOKEN', xsrf);
    }

    if (body !== undefined) {
        headers.set('Content-Type', 'application/json');
    }

    return fetch(url, {
        method,
        headers,
        credentials: 'same-origin',
        body: body === undefined ? undefined : JSON.stringify(body),
    });
}

/** Lets Laravel set a fresh XSRF-TOKEN cookie before a state-changing request. */
export async function csrf(): Promise<void> {
    const response = await request('GET', '/sanctum/csrf-cookie');

    if (!response.ok) {
        throw new ApiError(response.status);
    }
}

export async function login(email: string, password: string): Promise<LoginResult> {
    await csrf();

    const response = await request('POST', '/auth/login', { email, password });

    if (response.ok) {
        return 'ok';
    }

    if (response.status === 422) {
        return 'invalid';
    }

    if (response.status === 429) {
        return 'throttled';
    }

    throw new ApiError(response.status);
}

export async function logout(): Promise<void> {
    const response = await request('POST', '/auth/logout');

    // 401: the session had already expired, so the user is logged out anyway.
    if (!response.ok && response.status !== 401) {
        throw new ApiError(response.status);
    }
}

/** Returns the signed-in user, or null without a session. */
export async function getMe(): Promise<Me | null> {
    const response = await request('GET', '/api/me');

    if (response.status === 401) {
        return null;
    }

    if (!response.ok) {
        throw new ApiError(response.status);
    }

    const data = (await response.json()) as Me;

    return { name: data.name, email: data.email };
}
