import { FormEvent, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api, setToken } from '../lib/api';
import { useAuth } from '../lib/auth';

export function LoginPage() {
  const navigate = useNavigate();
  const { reload } = useAuth();
  const [username, setUsername] = useState('alireza');
  const [password, setPassword] = useState('alireza');
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    try {
      const result = await api<{ token: string }>('/login', {
        method: 'POST',
        body: JSON.stringify({ username, password }),
      });
      setToken(result.token);
      await reload();
      navigate('/');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ورود ناموفق');
    }
  }

  return (
    <div className="min-h-screen grid place-items-center p-6">
      <form onSubmit={onSubmit} className="w-full max-w-md rounded-2xl border border-line bg-panel p-8">
        <p className="text-ember text-xs tracking-[0.25em]">ARIA AI</p>
        <h1 className="text-2xl mt-2">ورود به ایمن‌کار</h1>
        <label className="block mt-6 text-sm">نام کاربری</label>
        <input
          className="mt-1 w-full rounded-lg bg-ink border border-line px-3 py-2"
          value={username}
          onChange={(e) => setUsername(e.target.value)}
          autoComplete="username"
          required
        />
        <label className="block mt-4 text-sm">رمز عبور</label>
        <input
          type="password"
          className="mt-1 w-full rounded-lg bg-ink border border-line px-3 py-2"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          autoComplete="current-password"
          required
        />
        {error ? <p className="text-rust text-sm mt-3">{error}</p> : null}
        <button className="mt-6 w-full rounded-lg bg-ember text-ink py-2.5 font-medium">ورود</button>
      </form>
    </div>
  );
}
