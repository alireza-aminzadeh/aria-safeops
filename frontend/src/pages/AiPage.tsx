import { FormEvent, useEffect, useState } from 'react';
import { api } from '../lib/api';

type AiStatus = { enabled: boolean; available: boolean; message: string };

export function AiPage() {
  const [query, setQuery] = useState('الزام گاز‌تست برای کار گرم چیست؟');
  const [status, setStatus] = useState<AiStatus | null>(null);
  const [text, setText] = useState('');
  const [citations, setCitations] = useState<string[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void api<AiStatus>('/ai/status').then(setStatus).catch(() => undefined);
  }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      const result = await api<{ available: boolean; text?: string; citations?: string[]; message?: string }>(
        '/ai/knowledge-query',
        { method: 'POST', body: JSON.stringify({ query }) },
      );
      setText(result.text ?? result.message ?? '');
      setCitations(result.citations ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'سرویس در دسترس نیست');
    }
  }

  return (
    <section className="max-w-2xl">
      <h2 className="text-2xl">دستیار دانش HSE</h2>
      <p className="text-sm text-muted mt-2">{status?.message ?? 'در حال بررسی وضعیت…'}</p>
      <form onSubmit={onSubmit} className="mt-6 rounded-2xl border border-line bg-panel p-6">
        {status?.enabled ? <span className="text-xs bg-mint/20 text-mint rounded-full px-3 py-1">فعال</span> : <span className="text-xs bg-ember text-ink rounded-full px-3 py-1">به‌زودی</span>}
        <textarea className="w-full mt-4 rounded-lg bg-ink border border-line px-3 py-2 min-h-28" value={query} onChange={(e) => setQuery(e.target.value)} />
        <button className="mt-4 rounded-lg bg-ember text-ink px-4 py-2 text-sm">پرسش</button>
        {error ? <p className="text-rust text-sm mt-4">{error}</p> : null}
        {text ? <p className="text-sm mt-4 leading-7">{text}</p> : null}
        {citations.length > 0 ? <ul className="mt-3 text-xs text-muted space-y-1">{citations.map((item) => <li key={item}>{item}</li>)}</ul> : null}
      </form>
    </section>
  );
}
