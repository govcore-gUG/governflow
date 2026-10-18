import { useId, useState, type SyntheticEvent } from 'react';
import { login } from '../lib/api';

export const MESSAGE_INVALID = 'Die E-Mail-Adresse oder das Passwort ist falsch.';
export const MESSAGE_THROTTLED =
    'Zu viele Anmeldeversuche. Bitte warten Sie eine Minute und versuchen Sie es dann erneut.';
export const MESSAGE_ERROR = 'Die Anmeldung hat nicht geklappt. Bitte versuchen Sie es erneut.';

interface LoginFormProps {
    onLoggedIn: () => void;
}

export default function LoginForm({ onLoggedIn }: LoginFormProps) {
    const id = useId();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    async function submit(event: SyntheticEvent<HTMLFormElement>) {
        event.preventDefault();

        const enteredPassword = password;
        // The password is only kept until it is sent, never stored.
        setPassword('');
        setError(null);
        setSubmitting(true);

        try {
            const result = await login(email, enteredPassword);

            if (result === 'ok') {
                onLoggedIn();
                return;
            }

            setError(result === 'throttled' ? MESSAGE_THROTTLED : MESSAGE_INVALID);
        } catch {
            setError(MESSAGE_ERROR);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <form
            className="flex max-w-sm flex-col gap-3"
            onSubmit={(event) => {
                void submit(event);
            }}
        >
            <label className="flex flex-col gap-1" htmlFor={`${id}-email`}>
                E-Mail-Adresse
                <input
                    id={`${id}-email`}
                    className="rounded border border-slate-300 px-2 py-1"
                    type="email"
                    name="email"
                    autoComplete="username"
                    required
                    value={email}
                    onChange={(event) => {
                        setEmail(event.target.value);
                    }}
                />
            </label>
            <label className="flex flex-col gap-1" htmlFor={`${id}-password`}>
                Passwort
                <input
                    id={`${id}-password`}
                    className="rounded border border-slate-300 px-2 py-1"
                    type="password"
                    name="password"
                    autoComplete="current-password"
                    required
                    value={password}
                    onChange={(event) => {
                        setPassword(event.target.value);
                    }}
                />
            </label>
            {error !== null && (
                <p role="alert" className="text-red-700">
                    {error}
                </p>
            )}
            <button
                type="submit"
                className="self-start rounded bg-slate-900 px-3 py-1 text-white disabled:opacity-50"
                disabled={submitting}
            >
                Anmelden
            </button>
        </form>
    );
}
