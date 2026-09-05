import { FormEvent, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, patch } from '../lib/api';
import type { Incident } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

export function IncidentDetailPage() {
  const { id } = useParams();
  const [item, setItem] = useState<Incident | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [rca, setRca] = useState('');
  const [capa, setCapa] = useState('');

  async function load() {
    if (!id) return;
    const incident = await api<Incident>(`/incidents/${id}`);
    setItem(incident);
    setRca(incident.rcaNotes ?? '');
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, [id]);

  async function saveRca(event: FormEvent) {
    event.preventDefault();
    await patch(`/incidents/${id}`, { rcaNotes: rca });
    await load();
  }

  async function addCapa(event: FormEvent) {
    event.preventDefault();
    await api(`/incidents/${id}/capa-actions`, { method: 'POST', body: JSON.stringify({ description: capa }) });
    setCapa('');
    await load();
  }

  async function next(status: string) {
    setError(null);
    try {
      await api(`/incidents/${id}/status`, { method: 'POST', body: JSON.stringify({ status }) });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'گذار ناموفق');
    }
  }

  if (!item) return <p>در حال بارگذاری…</p>;
  const nextMap: Record<string, { to: string; label: string }> = {
    reported: { to: 'under_investigation', label: 'شروع بررسی (RCA)' },
    under_investigation: { to: 'capa_assigned', label: 'ارجاع به CAPA' },
    capa_assigned: { to: 'closed', label: 'بستن حادثه' },
  };

  return (
    <section className="max-w-3xl space-y-6">
      <Link to="/incidents" className="text-sm text-muted">بازگشت</Link>
      <div className="flex items-center gap-3">
        <h2 className="text-2xl">حادثه</h2>
        <StatusBadge value={item.status} kind="incident" />
      </div>
      <p>{item.description}</p>
      {error ? <p className="text-rust">{error}</p> : null}
      {nextMap[item.status] ? (
        <button onClick={() => void next(nextMap[item.status].to)} className="rounded-lg bg-ember text-ink px-4 py-2 text-sm">
          {nextMap[item.status].label}
        </button>
      ) : null}
      <form onSubmit={saveRca} className="rounded-2xl border border-line bg-panel p-5">
        <h3 className="mb-3">یادداشت RCA</h3>
        <textarea className="w-full rounded-lg bg-ink border border-line px-3 py-2 min-h-28" value={rca} onChange={(e) => setRca(e.target.value)} />
        <button className="mt-3 rounded-lg border border-line px-4 py-2 text-sm">ذخیره RCA</button>
      </form>
      <form onSubmit={addCapa} className="rounded-2xl border border-line bg-panel p-5">
        <h3 className="mb-3">اقدام اصلاحی (CAPA)</h3>
        <input className="w-full rounded-lg bg-ink border border-line px-3 py-2" value={capa} onChange={(e) => setCapa(e.target.value)} required />
        <button className="mt-3 rounded-lg bg-ember text-ink px-4 py-2 text-sm">افزودن</button>
        <ul className="mt-4 text-sm space-y-2">
          {(item.capaActions ?? []).map((action) => (
            <li key={action.id} className="flex justify-between border-b border-line/50 pb-1">
              <span>{action.description}</span>
              <span className="text-muted">{action.status}</span>
            </li>
          ))}
        </ul>
      </form>
    </section>
  );
}
