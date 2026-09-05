import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, collection } from '../lib/api';
import { changeType, faDate, mocTransitions } from '../lib/labels';
import type { AuditItem, Moc } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

export function MocDetailPage() {
  const { id } = useParams();
  const [item, setItem] = useState<Moc | null>(null);
  const [transitions, setTransitions] = useState<string[]>([]);
  const [audit, setAudit] = useState<AuditItem[]>([]);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    if (!id) return;
    const [moc, avail, logs] = await Promise.all([
      api<Moc>(`/moc_requests/${id}`),
      api<{ transitions: string[] }>(`/moc-requests/${id}/available-transitions`),
      api<{ member?: AuditItem[] }>(`/audit?entity=moc_request&entityId=${id}`),
    ]);
    setItem(moc);
    setTransitions(avail.transitions);
    setAudit(collection<AuditItem>(logs));
  }

  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, [id]);

  async function apply(transition: string) {
    setError(null);
    try {
      await api(`/moc-requests/${id}/transitions`, { method: 'POST', body: JSON.stringify({ transition }) });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'گذار ناموفق');
    }
  }

  if (!item) return <p>در حال بارگذاری…</p>;

  return (
    <section className="max-w-3xl">
      <Link to="/moc" className="text-sm text-muted">بازگشت</Link>
      <div className="flex items-center gap-3 mt-3">
        <h2 className="text-2xl">مدیریت تغییر</h2>
        <StatusBadge value={item.status} kind="moc" />
      </div>
      <p className="mt-3">{item.description}</p>
      <p className="text-sm text-muted mt-2">{changeType[item.changeType]} · {item.equipmentTag ?? '—'} · PSSR: {faDate(item.pssrCompletedAt)}</p>
      {error ? <p className="text-rust mt-3">{error}</p> : null}
      <div className="flex flex-wrap gap-2 mt-6">
        {transitions.map((t) => (
          <button key={t} onClick={() => void apply(t)} className="rounded-lg border border-line bg-panel-2 px-4 py-2 text-sm">
            {mocTransitions[t] ?? t}
          </button>
        ))}
      </div>
      <p className="text-xs text-muted mt-4">بستن MOC فقط از وضعیت PSSR ممکن است؛ دور زدن بازنگری پیش‌راه‌اندازی مسدود است.</p>
      <div className="rounded-2xl border border-line bg-panel p-5 mt-6">
        <h3 className="mb-3">سابقه ممیزی</h3>
        {audit.map((row) => (
          <p key={row.id} className="text-sm flex justify-between border-b border-line/50 py-1">
            <span>{row.action}</span><span className="text-muted">{faDate(row.createdAt)}</span>
          </p>
        ))}
      </div>
    </section>
  );
}
