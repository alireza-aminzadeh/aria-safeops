import { FormEvent, useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, collection } from '../lib/api';
import { useAuth } from '../lib/auth';
import { faDate, permitTransitions } from '../lib/labels';
import { mutateOrQueue, useOfflineQueue } from '../lib/offlineQueue';
import type { AuditItem, Permit, SignatureItem } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';
import { QrCard } from '../components/QrCard';

const SIGNATURE_REQUIRED = ['approve', 'activate'];

export function PermitDetailPage() {
  const { id } = useParams();
  const { me } = useAuth();
  const [item, setItem] = useState<Permit | null>(null);
  const [transitions, setTransitions] = useState<string[]>([]);
  const [audit, setAudit] = useState<AuditItem[]>([]);
  const [signatures, setSignatures] = useState<SignatureItem[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [gasType, setGasType] = useState('O2');
  const [value, setValue] = useState('20.9');
  const [loto, setLoto] = useState('Breaker-A, Valve-12');
  const [pendingTransition, setPendingTransition] = useState<string | null>(null);
  const [signerName, setSignerName] = useState('');
  const [gasNotice, setGasNotice] = useState<string | null>(null);
  const [pendingGasReadings, setPendingGasReadings] = useState<{ gasType: string; value: string }[]>([]);
  const { pendingCount } = useOfflineQueue();
  const previousPendingCount = useRef(pendingCount);

  async function load() {
    if (!id) return;
    const [permit, avail, logs, signed] = await Promise.all([
      api<Permit>(`/permits/${id}`),
      api<{ transitions: string[] }>(`/permits/${id}/available-transitions`),
      api<{ member?: AuditItem[] }>(`/audit?entity=permit&entityId=${id}`),
      api<{ member?: SignatureItem[] }>(`/signatures?entityType=permit&entityId=${id}`),
    ]);
    setItem(permit);
    setTransitions(avail.transitions);
    setAudit(collection<AuditItem>(logs));
    setSignatures(collection<SignatureItem>(signed));
  }

  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, [id]);

  // با ارسال موفق صف آفلاین، دوباره بگیر و لیست قرائت‌های محلی/موقت را خالی کن.
  useEffect(() => {
    if (pendingCount < previousPendingCount.current) {
      setPendingGasReadings([]);
      void load().catch(() => undefined);
    }
    previousPendingCount.current = pendingCount;
  }, [pendingCount]);

  async function apply(transition: string, withSignerName?: string) {
    setError(null);
    try {
      await api(`/permits/${id}/transitions`, {
        method: 'POST',
        body: JSON.stringify({ transition, ...(withSignerName ? { signerName: withSignerName } : {}) }),
      });
      setPendingTransition(null);
      setSignerName('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'گذار ناموفق');
    }
  }

  function startTransition(transition: string) {
    if (SIGNATURE_REQUIRED.includes(transition)) {
      setPendingTransition(transition);
      setSignerName(me?.fullName ?? '');
      return;
    }
    void apply(transition);
  }

  async function confirmSignature(event: FormEvent) {
    event.preventDefault();
    if (!pendingTransition || signerName.trim() === '') return;
    await apply(pendingTransition, signerName.trim());
  }

  async function addGas() {
    setError(null);
    setGasNotice(null);
    try {
      const result = await mutateOrQueue({
        path: `/permits/${id}/gas-test-readings`,
        method: 'POST',
        body: { gasType, value },
        description: `گاز‌تست ${gasType} — ${item?.equipmentTag ?? id}`,
      });
      if (result.queued) {
        setPendingGasReadings((prev) => [...prev, { gasType, value }]);
        setGasNotice('آفلاین — این قرائت ذخیره شد و به‌محض اتصال ارسال می‌شود.');
      } else {
        await load();
      }
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
        <Link to={`/equipment/${encodeURIComponent(item.equipmentTag)}`} className="font-mono text-ember mt-3 block hover:underline">
          {item.equipmentTag} ↗ وضعیت تجهیز
        </Link>
        <div className="flex items-center gap-3 mt-1">
          <h2 className="text-2xl">{item.permitType?.nameFa ?? 'مجوز کار'}</h2>
          <StatusBadge value={item.status} />
        </div>
        <p className="text-sm text-muted mt-2">{item.workDescription || 'بدون شرح کار'}</p>
        <p className="text-xs text-muted mt-1">ناحیه: {item.locationPlotRef || '—'} · درخواست‌کننده: {item.requestedBy?.fullName ?? '—'}</p>
        {error ? <p className="text-rust mt-3">{error}</p> : null}

        <div className="flex flex-wrap gap-2 mt-6">
          {transitions.map((t) => (
            <button key={t} onClick={() => startTransition(t)} className="rounded-lg border border-line bg-panel-2 px-4 py-2 text-sm">
              {permitTransitions[t] ?? t}{SIGNATURE_REQUIRED.includes(t) ? ' ✒' : ''}
            </button>
          ))}
          {transitions.length === 0 ? <p className="text-muted text-sm">گذار مجازی برای نقش شما وجود ندارد.</p> : null}
        </div>

        {pendingTransition ? (
          <form onSubmit={confirmSignature} className="rounded-2xl border border-ember bg-panel p-5 mt-4">
            <h3 className="mb-2">امضای الکترونیک — {permitTransitions[pendingTransition] ?? pendingTransition}</h3>
            <p className="text-xs text-muted mb-3">با تایپ نام کامل خود، این تصمیم را به‌صورت الکترونیک تأیید و امضا می‌کنید. این امضا در زنجیرهٔ ممیزی غیرقابل‌تغییر ثبت می‌شود.</p>
            <div className="flex gap-2">
              <input
                autoFocus
                className="w-full rounded-lg bg-ink border border-line px-3 py-2 text-sm"
                value={signerName}
                onChange={(e) => setSignerName(e.target.value)}
                placeholder="نام و نام خانوادگی"
                required
              />
              <button className="rounded-lg bg-ember text-ink px-4 py-2 text-sm shrink-0">تأیید و امضا</button>
              <button type="button" onClick={() => setPendingTransition(null)} className="rounded-lg border border-line px-4 py-2 text-sm shrink-0">انصراف</button>
            </div>
          </form>
        ) : null}

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
            {gasNotice ? <p className="text-ember text-xs mb-2">{gasNotice}</p> : null}
            <ul className="text-sm space-y-1">
              {(item.gasTestReadings ?? []).map((reading) => (
                <li key={reading.id} className="flex justify-between border-b border-line/50 py-1">
                  <span>{reading.gasType}</span>
                  <span className="font-mono">{reading.readingValue}</span>
                </li>
              ))}
              {pendingGasReadings.map((reading, idx) => (
                <li key={`pending-${idx}`} className="flex justify-between border-b border-line/50 py-1 text-muted">
                  <span>{reading.gasType} <span className="text-[10px]">(در صف ارسال)</span></span>
                  <span className="font-mono">{reading.value}</span>
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

        <div className="grid md:grid-cols-2 gap-4 mt-4">
          <div className="rounded-2xl border border-line bg-panel p-5">
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
          <div className="rounded-2xl border border-line bg-panel p-5">
            <h3 className="mb-3">امضاهای الکترونیک</h3>
            <ul className="text-sm space-y-2">
              {signatures.map((row) => (
                <li key={row.id} className="border-b border-line/50 pb-1">
                  <div className="flex justify-between gap-3">
                    <span>{permitTransitions[row.action] ?? row.action} — {row.signerName}</span>
                    <span className="text-muted">{faDate(row.signedAt)}</span>
                  </div>
                  <p className="font-mono text-[10px] text-muted truncate" title={row.contentHash}>{row.contentHash}</p>
                </li>
              ))}
              {signatures.length === 0 ? <li className="text-muted">هنوز امضایی ثبت نشده است.</li> : null}
            </ul>
          </div>
        </div>
      </div>
      <QrCard value={qrValue} caption="QR میدان — اسکن برای باز کردن همین مجوز" />
    </section>
  );
}
