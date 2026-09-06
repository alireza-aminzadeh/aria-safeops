import { FormEvent, useEffect, useState } from 'react';
import { api } from '../lib/api';
import { faDate, psmAuditStatus, psmRating } from '../lib/labels';
import type { PsmAudit, PsmAuditFinding } from '../lib/types';

export function PsmAuditPage() {
  const [audits, setAudits] = useState<PsmAudit[]>([]);
  const [selected, setSelected] = useState<PsmAudit | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [title, setTitle] = useState('ممیزی PSM دورهٔ جاری');
  const [auditorName, setAuditorName] = useState('');
  const [auditDate, setAuditDate] = useState(() => new Date().toISOString().slice(0, 10));

  async function load() {
    const list = await api<{ items: PsmAudit[] }>('/psm-audits');
    setAudits(list.items);
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function openAudit(id: string) {
    setError(null);
    try {
      setSelected(await api<PsmAudit>(`/psm-audits/${id}`));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'خطا در بازکردن ممیزی');
    }
  }

  async function createAudit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      const created = await api<PsmAudit>('/psm-audits', {
        method: 'POST',
        body: JSON.stringify({ title, auditorName, auditDate }),
      });
      await load();
      setSelected(created);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ایجاد ممیزی ناموفق');
    }
  }

  async function updateFinding(finding: PsmAuditFinding, patch: Partial<PsmAuditFinding>) {
    if (!selected) return;
    try {
      const updated = await api<PsmAudit>(`/psm-audits/${selected.id}/findings/${finding.id}`, {
        method: 'PATCH',
        body: JSON.stringify({ ...finding, ...patch }),
      });
      setSelected(updated);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'به‌روزرسانی یافته ناموفق');
    }
  }

  async function completeAudit() {
    if (!selected) return;
    setError(null);
    try {
      const updated = await api<PsmAudit>(`/psm-audits/${selected.id}/complete`, { method: 'POST' });
      setSelected(updated);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'تکمیل ممیزی ناموفق — همهٔ عناصر را ارزیابی کنید.');
    }
  }

  return (
    <section className="space-y-6">
      <div>
        <p className="text-xs tracking-[0.2em] text-ember">PHASE 3 · PSM COMPLIANCE</p>
        <h2 className="text-2xl mt-1">ممیزی PSM — چک‌لیست ۱۴ عنصر OSHA 1910.119</h2>
      </div>
      {error ? <p className="text-rust text-sm">{error}</p> : null}
      <div className="grid lg:grid-cols-[20rem_1fr] gap-6">
        <div className="space-y-4">
          <form onSubmit={createAudit} className="rounded-2xl border border-line bg-panel p-5">
            <h3 className="mb-3">ممیزی جدید</h3>
            <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={title} onChange={(e) => setTitle(e.target.value)} placeholder="عنوان" required />
            <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={auditorName} onChange={(e) => setAuditorName(e.target.value)} placeholder="نام ممیز" />
            <input type="date" className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={auditDate} onChange={(e) => setAuditDate(e.target.value)} />
            <button className="w-full rounded-lg bg-ember text-ink py-2">شروع ممیزی (۱۴ عنصر خودکار)</button>
          </form>
          <div className="rounded-2xl border border-line bg-panel p-5 space-y-2">
            <h3 className="mb-2">ممیزی‌های ثبت‌شده</h3>
            {audits.map((a) => (
              <button
                key={a.id}
                onClick={() => void openAudit(a.id)}
                className={`w-full text-right rounded-lg border px-3 py-2 text-sm ${selected?.id === a.id ? 'border-ember bg-ember/10' : 'border-line'}`}
              >
                <p>{a.title}</p>
                <p className="text-xs text-muted mt-1">
                  {faDate(a.auditDate)} · {psmAuditStatus[a.status] ?? a.status}
                  {a.overallScorePercent != null ? ` · ${a.overallScorePercent}%` : ''}
                  {a.openFindings ? ` · ${a.openFindings} یافتهٔ باز` : ''}
                </p>
              </button>
            ))}
            {audits.length === 0 ? <p className="text-muted text-sm">هنوز ممیزی‌ای ثبت نشده است.</p> : null}
          </div>
        </div>

        <div>
          {!selected ? (
            <p className="text-muted">یک ممیزی را از فهرست باز کنید یا ممیزی جدید بسازید.</p>
          ) : (
            <div className="rounded-2xl border border-line bg-panel p-5">
              <div className="flex justify-between items-center flex-wrap gap-2">
                <div>
                  <h3>{selected.title}</h3>
                  <p className="text-xs text-muted mt-1">{selected.auditorName} · {faDate(selected.auditDate)} · {psmAuditStatus[selected.status] ?? selected.status}</p>
                </div>
                {selected.status !== 'completed' ? (
                  <button onClick={() => void completeAudit()} className="rounded-lg bg-ember text-ink px-4 py-2 text-sm">تکمیل ممیزی</button>
                ) : (
                  <span className="text-mint text-lg font-medium">{selected.overallScorePercent}% انطباق</span>
                )}
              </div>
              <div className="mt-5 space-y-3">
                {(selected.findings ?? []).map((f) => (
                  <div key={f.id} className="rounded-xl border border-line/70 p-4">
                    <div className="flex justify-between items-start gap-3 flex-wrap">
                      <p className="font-medium">{f.elementNameFa}</p>
                      <select
                        disabled={selected.status === 'completed'}
                        className="rounded-lg bg-ink border border-line px-2 py-1 text-sm"
                        value={f.rating}
                        onChange={(e) => void updateFinding(f, { rating: e.target.value })}
                      >
                        {Object.entries(psmRating).map(([value, label]) => (
                          <option key={value} value={value}>{label}</option>
                        ))}
                      </select>
                    </div>
                    <textarea
                      disabled={selected.status === 'completed'}
                      className="w-full mt-2 rounded-lg bg-ink border border-line px-3 py-2 text-sm min-h-16"
                      placeholder="یادداشت ممیز"
                      defaultValue={f.notes ?? ''}
                      onBlur={(e) => void updateFinding(f, { notes: e.target.value })}
                    />
                    {f.rating === 'partial' || f.rating === 'non_compliant' ? (
                      <div className="grid sm:grid-cols-[1fr_10rem] gap-2 mt-2">
                        <input
                          disabled={selected.status === 'completed'}
                          className="rounded-lg bg-ink border border-line px-3 py-2 text-sm"
                          placeholder="اقدام اصلاحی"
                          defaultValue={f.correctiveAction ?? ''}
                          onBlur={(e) => void updateFinding(f, { correctiveAction: e.target.value })}
                        />
                        <input
                          type="date"
                          disabled={selected.status === 'completed'}
                          className="rounded-lg bg-ink border border-line px-3 py-2 text-sm"
                          defaultValue={f.dueDate ?? ''}
                          onBlur={(e) => void updateFinding(f, { dueDate: e.target.value })}
                        />
                      </div>
                    ) : null}
                    <p className="text-xs text-muted mt-2">وضعیت اقدام: {f.status === 'open' ? 'باز' : 'بسته'}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
