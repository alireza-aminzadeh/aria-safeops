import { FormEvent, useState } from 'react';
import { api } from '../lib/api';

export function AiPage() {
  const [query, setQuery] = useState('الزام گاز‌تست برای کار گرم چیست؟');
  const [message, setMessage] = useState('سرویس دستیار هوشمند هنوز فعال نشده است.');

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    try {
      await api('/ai/knowledge-query', { method: 'POST', body: JSON.stringify({ query }) });
    } catch (err) {
      setMessage(err instanceof Error ? err.message : 'سرویس در دسترس نیست');
    }
  }

  return (
    <section className="max-w-2xl">
      <h2 className="text-2xl">دستیار دانش HSE</h2>
      <form onSubmit={onSubmit} className="mt-6 rounded-2xl border border-dashed border-ember/50 bg-panel p-6">
        <span className="text-xs bg-ember text-ink rounded-full px-3 py-1">به‌زودی</span>
        <textarea className="w-full mt-4 rounded-lg bg-ink border border-line px-3 py-2 min-h-28" value={query} onChange={(e) => setQuery(e.target.value)} />
        <button className="mt-4 rounded-lg border border-line px-4 py-2 text-sm">ارسال آزمایشی</button>
        <p className="text-rust text-sm mt-4">{message}</p>
      </form>
    </section>
  );
}
