import { useEffect, useState } from 'react';
import { getMe, logout, type Me } from '../lib/api';
import LoginForm from './LoginForm';

type State = { status: 'loading' } | { status: 'guest' } | { status: 'signedIn'; me: Me } | { status: 'error' };

export const MESSAGE_LOAD_ERROR = 'Die Anmeldung konnte nicht geprüft werden. Bitte laden Sie die Seite neu.';

async function loadState(): Promise<State> {
    try {
        const me = await getMe();

        return me === null ? { status: 'guest' } : { status: 'signedIn', me };
    } catch {
        return { status: 'error' };
    }
}

export default function AuthStatus() {
    const [state, setState] = useState<State>({ status: 'loading' });

    useEffect(() => {
        let active = true;

        void loadState().then((next) => {
            if (active) {
                setState(next);
            }
        });

        return () => {
            active = false;
        };
    }, []);

    async function refresh() {
        setState(await loadState());
    }

    async function signOut() {
        try {
            await logout();
        } catch {
            // refresh() below shows whether the session is really gone.
        }

        await refresh();
    }

    switch (state.status) {
        case 'loading':
            return null;
        case 'error':
            return <p role="alert">{MESSAGE_LOAD_ERROR}</p>;
        case 'guest':
            return (
                <LoginForm
                    onLoggedIn={() => {
                        void refresh();
                    }}
                />
            );
        case 'signedIn':
            return (
                <div className="flex items-center gap-3">
                    <p>Angemeldet als {state.me.name}</p>
                    <button
                        type="button"
                        className="rounded border border-slate-300 px-3 py-1"
                        onClick={() => {
                            void signOut();
                        }}
                    >
                        Abmelden
                    </button>
                </div>
            );
    }
}
