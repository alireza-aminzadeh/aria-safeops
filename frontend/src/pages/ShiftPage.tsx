import { FormEvent, useEffect, useState } from 'react';
import { api } from '../lib/api';
import { faDate, shiftStatus } from '../lib/labels';
import type { LogbookEntry, ShiftHandover, ToolboxTalk } from '../lib/types';

export function ShiftPage() {
  const [handovers, setHandovers] = useState<ShiftHandover[]>([]);
  const [logs, setLogs] = useState<LogbookEntry[]>([]);
  const [talks, setTalks] = useState<ToolboxTalk[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [summary, setSummary] = useState('');
  const [incomingName, setIncoming] = useState('');
  const [logBody, setLogBody] = useState('');
  const [topic, setTopic] = useState('');

  async function load() {
    const [h, l, t] = await Promise.all([
      api<{ items: ShiftHandover[] }>('/shift-handovers'),
      api<{ items: LogbookEntry[] }>('/logbook'),
      api<{ items: ToolboxTalk[] }>('/toolbox-talks'),
    ]);
    setHandovers(h.items);
    setLogs(l.items);
    setTalks(t.items);
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function createHandover(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api('/shift-handovers', { method: 'POST', body: JSON.stringify({ summary, incomingName, shiftName: 'day' }) });
      setSummary('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'خطا');
    }
  }

  async function addLog(event: FormEvent) {
    event.preventDefault();
    await api('/logbook', { method: 'POST', body: JSON.stringify({ category: 'operations', body: logBody }) });
    setLogBody('');
    await load();
  }

  async function addTalk(event: FormEvent) {
    event.preventDefault();
    await api('/toolbox-talks', { method: 'POST', body: JSON.stringify({ topic, location: 'اتاق کنترل', attendeeCount: 6 }) });
    setTopic('');
    await load();
  }

  return (
    <section className="space-y-6">
      <h2 className="text-2xl">تحویل شیفت، Logbook و Toolbox Talk</h2>
      {error ? <p className="text-rust text-sm">{error}</p> : null}
      <div className="grid lg:grid-cols-3 gap-6">
        <form onSubmit={createHandover} className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">تحویل شیفت</h3>
          <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={incomingName} onChange={(e) => setIncoming(e.target.value)} placeholder="تحویل‌گیرنده" />
          <textarea className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2 min-h-24" value={summary} onChange={(e) => setSummary(e.target.value)} required />
          <button className="rounded-lg bg-ember text-ink px-4 py-2">ثبت پیش‌نویس</button>
        </form>
        <form onSubmit={addLog} className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">Logbook</h3>
          <textarea className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2 min-h-24" value={logBody} onChange={(e) => setLogBody(e.target.value)} required />
          <button className="rounded-lg border border-line px-4 py-2">ثبت لاگ</button>
        </form>
        <form onSubmit={addTalk} className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">Toolbox Talk</h3>
          <input className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={topic} onChange={(e) => setTopic(e.target.value)} required />
          <button className="rounded-lg border border-line px-4 py-2">ثبت جلسه</button>
        </form>
      </div>
      <div className="grid lg:grid-cols-3 gap-6 text-sm">
        <div className="rounded-2xl border border-line bg-panel p-5 space-y-3">
          {handovers.map((item) => (
            <article key={item.id} className="border-b border-line pb-2">
              <p>{item.outgoingName} → {item.incomingName || '—'}</p>
              <p className="text-muted">{item.summary}</p>
              <p className="text-xs mt-1">{shiftStatus[item.status] ?? item.status}</p>
              {item.status === 'draft' ? <button className="text-ember text-xs mt-1" onClick={() => void api(`/shift-handovers/${item.id}/submit`, { method: 'POST' }).then(load)}>ارسال</button> : null}
              {item.status === 'submitted' ? <button className="text-ember text-xs mt-1" onClick={() => void api(`/shift-handovers/${item.id}/accept`, { method: 'POST' }).then(load)}>پذیرش</button> : null}
            </article>
          ))}
        </div>
        <div className="rounded-2xl border border-line bg-panel p-5 space-y-3">
          {logs.map((item) => (
            <article key={item.id} className="border-b border-line pb-2">
              <p>{item.body}</p>
              <p className="text-xs text-muted">{item.authorName} · {faDate(item.createdAt)}</p>
            </article>
          ))}
        </div>
        <div className="rounded-2xl border border-line bg-panel p-5 space-y-3">
          {talks.map((item) => (
            <article key={item.id} className="border-b border-line pb-2">
              <p>{item.topic}</p>
              <p className="text-xs text-muted">{item.location} · {item.attendeeCount} نفر · {faDate(item.heldAt)}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
