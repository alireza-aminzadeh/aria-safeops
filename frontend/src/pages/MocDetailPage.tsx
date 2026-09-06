import { FormEvent, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, collection } from '../lib/api';
import { useAuth } from '../lib/auth';
import { changeType, faDate, mocTransitions } from '../lib/labels';
import type { AuditItem, Moc, SignatureItem } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

const SIGNATURE_REQUIRED = ['approve'];

export function MocDetailPage() {
  const { id } = useParams();
  const { me } = useAuth();
  const [item, setItem] = useState<Moc | null>(null);
  const [transitions, setTransitions] = useState<string[]>([]);
  const [audit, setAudit] = useState<AuditItem[]>([]);
  const [signatures, setSignatures] = useState<SignatureItem[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [pendingTransition, setPendingTransition] = useState<string | null>(null);
  const [signerName, setSignerName] = useState('');

  async function load() {
    if (!id) return;
    const [moc, avail, logs, signed] = await Promise.all([
      api<Moc>(`/moc_requests/${id}`),
      api<{ transitions: string[] }>(`/moc-requests/${id}/available-transitions`),
      api<{ member?: AuditItem[] }>(`/audit?entity=moc_request&entityId=${id}`),
      api<{ member?: SignatureItem[] }>(`/signatures?entityType=moc_request&entityId=${id}`),
    ]);
    setItem(moc);
    setTransitions(avail.transitions);
    setAudit(collection<AuditItem>(logs));
    setSignatures(collection<SignatureItem>(signed));
  }

  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, [id]);

  async function apply(transition: string, withSignerName?: string) {
    setError(null);
    try {
      await api(`/moc-requests/${id}/transitions`, {
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
          <button key={t} onClick={() => startTransition(t)} className="rounded-lg border border-line bg-panel-2 px-4 py-2 text-sm">
            {mocTransitions[t] ?? t}{SIGNATURE_REQUIRED.includes(t) ? ' ✒' : ''}
          </button>
        ))}
      </div>

      {pendingTransition ? (
        <form onSubmit={confirmSignature} className="rounded-2xl border border-ember bg-panel p-5 mt-4">
          <h3 className="mb-2">امضای الکترونیک — {mocTransitions[pendingTransition] ?? pendingTransition}</h3>
          <p className="text-xs text-muted mb-3">با تایپ نام کامل خود، تأیید MOC را به‌صورت الکترونیک امضا می‌کنید.</p>
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

      <p className="text-xs text-muted mt-4">بستن MOC فقط از وضعیت PSSR ممکن است؛ دور زدن بازنگری پیش‌راه‌اندازی مسدود است.</p>
      <div className="grid md:grid-cols-2 gap-4 mt-6">
        <div className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">سابقه ممیزی</h3>
          {audit.map((row) => (
            <p key={row.id} className="text-sm flex justify-between border-b border-line/50 py-1">
              <span>{row.action}</span><span className="text-muted">{faDate(row.createdAt)}</span>
            </p>
          ))}
          {audit.length === 0 ? <p className="text-muted text-sm">هنوز گذاری ثبت نشده است.</p> : null}
        </div>
        <div className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">امضاهای الکترونیک</h3>
          {signatures.map((row) => (
            <div key={row.id} className="text-sm border-b border-line/50 py-1">
              <p className="flex justify-between"><span>{row.signerName}</span><span className="text-muted">{faDate(row.signedAt)}</span></p>
            </div>
          ))}
          {signatures.length === 0 ? <p className="text-muted text-sm">هنوز امضایی ثبت نشده است.</p> : null}
        </div>
      </div>
    </section>
  );
}
