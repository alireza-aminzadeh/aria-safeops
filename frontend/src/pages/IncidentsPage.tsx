import { FormEvent, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, collection } from '../lib/api';
import { incidentType } from '../lib/labels';
import type { Incident } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

export function IncidentsPage() {
  const [items, setItems] = useState<Incident[]>([]);
  const [description, setDescription] = useState('');
  const [location, setLocation] = useState('');
  const [type, setType] = useState('near_miss');
  const [severity, setSeverity] = useState('low');
  const [error, setError] = useState<string | null>(null);

  async function load() {
    setItems(collection<Incident>(await api('/incidents?itemsPerPage=50')));
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    await api('/incidents', { method: 'POST', body: JSON.stringify({ description, type, severity, location }) });
    setDescription('');
    await load();
  }

  return (
    <section className="grid lg:grid-cols-[22rem_1fr] gap-6">
      <form onSubmit={onSubmit} className="rounded-2xl border border-line bg-panel p-5 h-fit">
        <h3 className="mb-4">ثبت حادثه / Near miss</h3>
        <select className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={type} onChange={(e) => setType(e.target.value)}>
          <option value="near_miss">شبه‌حادثه</option>
          <option value="incident">حادثه</option>
          <option value="injury">آسیب</option>
        </select>
        <select className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={severity} onChange={(e) => setSeverity(e.target.value)}>
          <option value="low">کم</option>
          <option value="medium">متوسط</option>
          <option value="high">بالا</option>
          <option value="critical">بحرانی</option>
        </select>
        <input className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" placeholder="محل" value={location} onChange={(e) => setLocation(e.target.value)} />
        <textarea className="w-full mb-4 rounded-lg bg-ink border border-line px-3 py-2 min-h-28" value={description} onChange={(e) => setDescription(e.target.value)} required />
        {error ? <p className="text-rust text-sm mb-3">{error}</p> : null}
        <button className="w-full rounded-lg bg-ember text-ink py-2">ثبت</button>
      </form>
      <div className="rounded-2xl border border-line bg-panel p-5">
        {items.map((item) => (
          <Link key={item.id} to={`/incidents/${item.id}`} className="block border-b border-line py-3 hover:bg-panel-2/40 px-2 rounded">
            <p>{item.description}</p>
            <p className="text-sm text-muted mt-1">{incidentType[item.type]} · {item.location || 'بدون محل'}</p>
            <div className="mt-2"><StatusBadge value={item.status} kind="incident" /></div>
          </Link>
        ))}
      </div>
    </section>
  );
}
