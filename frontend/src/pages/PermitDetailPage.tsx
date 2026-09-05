import { FormEvent, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, collection } from '../lib/api';
import { faDate, permitTransitions } from '../lib/labels';
import type { AuditItem, Permit } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';
import { QrCard } from '../components/QrCard';

export function PermitDetailPage() {
  const { id } = useParams();
  const [item, setItem] = useState<Permit | null>(null);
  const [transitions, setTransitions] = useState<string[]>([]);
  const [audit, setAudit] = useState<AuditItem[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [gasType, setGasType] = useState('O2');
  const [value, setValue] = useState('20.9');
  const [loto, setLoto] = useState('Breaker-A, Valve-12');

  async function load() {
    if (!id) return;
    const [permit, avail, logs] = await Promise.all([
      api<Permit>(`/permits/${id}`),
      api<{ transitions: string[] }>(`/permits/${id}/available-transitions`),
      api<{ member?: AuditItem[] }>(`/audit?entity=permit&entityId=${id}`),
    ]);
    setItem(permit);
    setTransitions(avail.transitions);
    setAudit(collection<AuditItem>(logs));
  }

  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, [id]);

  async function apply(transition: string) {
    setError(null);
    try {
      await api(`/permits/${id}/transitions`, { method: 'POST', body: JSON.stringify({ transition }) });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'گذار ناموفق');
    }
  }

  async function addGas() {
    setError(null);
    try {
      await api(`/permits/${id}/gas-test-readings`, { method: 'POST', body: JSON.stringify({ gasType, value }) });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ثبت گاز‌تست ناموفق');
    }
  }

  async function confirmLoto(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api(`/permits/${id}/isolation`, {
        method: 'POST',
        body: JSON.stringify({ isolationPoints: loto.split(',').map((s) => s.trim()).filter(Boolean) }),
      });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'تأیید LOTO ناموفق');
    }
  }

  if (!item) return <p>در حال بارگذاری…</p>;
  const qrValue = `${window.location.origin}/permits/${item.id}`;

  return (
    <section className="grid xl:grid-cols-[1fr_16rem] gap-6">
      <div>
        <Link to="/permits" className="text-sm text-muted">بازگشت به فهرست</Link>
        <p className="font-mono text-ember mt-3">{item.equipmentTag}</p>
        <div className="flex items-center gap-3 mt-1">
          <h2 className="text-2xl">{item.permitType?.nameFa ?? 'مجوز کار'}</h2>
          <StatusBadge value={item.status} />
        </div>
        <p className="text-sm text-muted mt-2">{item.workDescription || 'بدون شرح کار'}</p>
        <p className="text-xs text-muted mt-1">ناحیه: {item.locationPlotRef || '—'} · درخواست‌کننده: {item.requestedBy?.fullName ?? '—'}</p>
        {error ? <p className="text-rust mt-3">{error}</p> : null}

        <div className="flex flex-wrap gap-2 mt-6">
          {transitions.map((t) => (
            <button key={t} onClick={() => void apply(t)} className="rounded-lg border border-line bg-panel-2 px-4 py-2 text-sm">
              {permitTransitions[t] ?? t}
            </button>
          ))}
          {transitions.length === 0 ? <p className="text-muted text-sm">گذار مجازی برای نقش شما وجود ندارد.</p> : null}
        </div>

        <div className="grid md:grid-cols-2 gap-4 mt-8">
          <div className="rounded-2xl border border-line bg-panel p-5">
            <h3 className="mb-3">گاز‌تست</h3>
            <div className="flex gap-2 mb-4">
              <select className="rounded-lg bg-ink border border-line px-3 py-2" value={gasType} onChange={(e) => setGasType(e.target.value)}>
                <option>O2</option><option>LEL</option><option>H2S</option><option>CO</option>
              </select>
              <input className="rounded-lg bg-ink border border-line px-3 py-2 w-24" value={value} onChange={(e) => setValue(e.target.value)} />
              <button onClick={() => void addGas()} className="rounded-lg bg-ember text-ink px-4">ثبت</button>
            </div>
            <ul className="text-sm space-y-1">
              {(item.gasTestReadings ?? []).map((reading) => (
                <li key={reading.id} className="flex justify-between border-b border-line/50 py-1">
                  <span>{reading.gasType}</span>
                  <span className="font-mono">{reading.readingValue}</span>
                </li>
              ))}
            </ul>
            {item.permitType?.requiresGasTest ? <p className="text-xs text-muted mt-3">فعال‌سازی کار گرم/فضای بسته نیازمند O2، LEL و H2S ایمن است.</p> : null}
          </div>
          <form onSubmit={confirmLoto} className="rounded-2xl border border-line bg-panel p-5">
            <h3 className="mb-3">ایزولاسیون انرژی (LOTO)</h3>
            {item.isolationConfirmed ? (
              <p className="text-mint text-sm">تأیید شده در {faDate(item.isolationConfirmedAt)} · {(item.isolationPoints ?? []).join('، ')}</p>
            ) : (
              <>
                <textarea className="w-full rounded-lg bg-ink border border-line px-3 py-2 min-h-20 mb-3" value={loto} onChange={(e) => setLoto(e.target.value)} />
                <button className="rounded-lg border border-line px-4 py-2 text-sm">تأیید LOTO</button>
              </>
            )}
            {item.permitType?.requiresIsolation ? <p className="text-xs text-muted mt-3">بدون تأیید ایزولاسیون نمی‌توان مجوز را فعال کرد.</p> : <p className="text-xs text-muted mt-3">این نوع مجوز ایزولاسیون اجباری ندارد.</p>}
          </form>
        </div>

        <div className="rounded-2xl border border-line bg-panel p-5 mt-4">
          <h3 className="mb-3">سابقه ممیزی</h3>
          <ul className="text-sm space-y-2">
            {audit.map((row) => (
              <li key={row.id} className="flex justify-between gap-3 border-b border-line/50 pb-1">
                <span>{row.action}</span>
                <span className="text-muted">{faDate(row.createdAt)}</span>
              </li>
            ))}
            {audit.length === 0 ? <li className="text-muted">هنوز گذاری ثبت نشده است.</li> : null}
          </ul>
        </div>
      </div>
      <QrCard value={qrValue} caption="QR میدان — اسکن برای باز کردن همین مجوز" />
    </section>
  );
}
