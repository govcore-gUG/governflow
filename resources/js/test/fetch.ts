import { vi } from 'vitest';

export interface RecordedRequest {
    url: string;
    method: string;
    headers: Headers;
    body: unknown;
}

type Route = (request: RecordedRequest) => Response | undefined;

export function json(status: number, body: unknown): Response {
    return new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}

export function empty(status: number): Response {
    return new Response(null, { status });
}

/**
 * Replaces global fetch. Each request is recorded and answered by the first
 * route returning a response; unknown requests fail the test.
 */
export function mockFetch(...routes: Route[]): RecordedRequest[] {
    const requests: RecordedRequest[] = [];

    vi.stubGlobal(
        'fetch',
        vi.fn((input: string, init: RequestInit = {}) => {
            const request: RecordedRequest = {
                url: input,
                method: init.method ?? 'GET',
                headers: new Headers(init.headers),
                body: typeof init.body === 'string' ? JSON.parse(init.body) : undefined,
            };
            requests.push(request);

            for (const route of routes) {
                const response = route(request);

                if (response !== undefined) {
                    return Promise.resolve(response);
                }
            }

            return Promise.reject(new Error(`Unexpected request ${request.method} ${request.url}`));
        }),
    );

    return requests;
}

export function route(method: string, url: string, respond: () => Response): Route {
    return (request) => (request.method === method && request.url === url ? respond() : undefined);
}
