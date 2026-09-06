import { FormEvent, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, patch } from '../lib/api';
import { rootCauseCategory } from '../lib/labels';
import type { Incident } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

const FIVE_WHYS = ['چرا ۱', 'چرا ۲', 'چرا ۳', 'چرا ۴', 'چرا ۵ (علت ریشه‌ای)'];

export function IncidentDetailPage() {
  const { id } = useParams();
  const [item, setItem] = useState<Incident | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [rca, setRca] = useState('');
  const [whys, setWhys] = useState<string[]>(['', '', '', '', '']);
  const [category, setCategory] = useState('');
  const [lostDays, setLostDays] = useState('0');
  const [capa, setCapa] = useState('');

  async function load() {
    if (!id) return;
    const incident = await api<Incident>(`/incidents/${id}`);
    setItem(incident);
    setRca(incident.rcaNotes ?? '');
    const existing = incident.rootCauseWhys ?? [];
    setWhys([0, 1, 2, 3, 4].map((i) => existing[i] ?? ''));
    setCategory(incident.rootCauseCategory ?? '');
    setLostDays(String(incident.lostDays ?? 0));
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, [id]);

  async function saveRca(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await patch(`/incidents/${id}`, {
        rcaNotes: rca,
        rootCauseWhys: whys.filter((w) => w.trim() !== ''),
        rootCauseCategory: category || null,
        lostDays: Number(lostDays) || 0,
      });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ذخیرهٔ RCA ناموفق');
    }
  }

  async function toggleRecordable() {
    if (!item) return;
    setError(null);
    try {
      await patch(`/incidents/${id}`, { recordable: !item.recordable });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'تغییر وضعیت ثبت‌شدنی ناموفق');
    }
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
      <div className="flex items-center gap-3 flex-wrap">
        <h2 className="text-2xl">حادثه</h2>
        <StatusBadge value={item.status} kind="incident" />
        {item.recordable ? <span className="text-xs rounded-full bg-rust/20 text-rust px-3 py-1">ثبت‌شدنی (Recordable)</span> : null}
        {item.lostDays ? <span className="text-xs rounded-full bg-ember/20 text-ember px-3 py-1">{item.lostDays} روز ازدست‌رفته</span> : null}
      </div>
      <p>{item.description}</p>
      {error ? <p className="text-rust">{error}</p> : null}
      <div className="flex flex-wrap gap-2">
        {nextMap[item.status] ? (
          <button onClick={() => void next(nextMap[item.status].to)} className="rounded-lg bg-ember text-ink px-4 py-2 text-sm">
            {nextMap[item.status].label}
          </button>
        ) : null}
        <button onClick={() => void toggleRecordable()} className="rounded-lg border border-line px-4 py-2 text-sm">
          {item.recordable ? 'برداشتن برچسب ثبت‌شدنی' : 'علامت‌گذاری به‌عنوان ثبت‌شدنی (TRIR)'}
        </button>
      </div>

      <form onSubmit={saveRca} className="rounded-2xl border border-line bg-panel p-5 space-y-4">
        <h3>تحلیل ریشه‌ای (۵ چرا)</h3>
        <div className="space-y-2">
          {FIVE_WHYS.map((label, i) => (
            <div key={label} className="flex items-center gap-3">
              <span className="text-xs text-muted w-28 shrink-0">{label}</span>
              <input
                className="w-full rounded-lg bg-ink border border-line px-3 py-2 text-sm"
                value={whys[i]}
                onChange={(e) => setWhys((prev) => prev.map((w, idx) => (idx === i ? e.target.value : w)))}
              />
            </div>
          ))}
        </div>
        <div className="grid sm:grid-cols-2 gap-3">
          <div>
            <label className="text-xs text-muted">دستهٔ علت ریشه‌ای</label>
            <select className="w-full mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={category} onChange={(e) => setCategory(e.target.value)}>
              <option value="">— انتخاب کنید —</option>
              {Object.entries(rootCauseCategory).map(([value, label]) => (
                <option key={value} value={value}>{label}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-xs text-muted">روزهای ازدست‌رفته (Lost Time)</label>
            <input
              type="number"
              min={0}
              className="w-full mt-1 rounded-lg bg-ink border border-line px-3 py-2"
              value={lostDays}
              onChange={(e) => setLostDays(e.target.value)}
            />
          </div>
        </div>
        <div>
          <label className="text-xs text-muted">خلاصهٔ نهایی RCA</label>
          <textarea className="w-full mt-1 rounded-lg bg-ink border border-line px-3 py-2 min-h-24" value={rca} onChange={(e) => setRca(e.target.value)} />
        </div>
        <button className="rounded-lg border border-line px-4 py-2 text-sm">ذخیرهٔ تحلیل ریشه‌ای</button>
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
