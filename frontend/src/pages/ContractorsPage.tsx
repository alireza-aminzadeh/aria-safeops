import { FormEvent, useEffect, useState } from 'react';
import { api, collection } from '../lib/api';
import { faDate } from '../lib/labels';
import type { Contractor } from '../lib/types';

export function ContractorsPage() {
  const [items, setItems] = useState<Contractor[]>([]);
  const [companyName, setCompanyName] = useState('');
  const [score, setScore] = useState('80');
  const [certType, setCertType] = useState('H2S Awareness');
  const [expiresAt, setExpiresAt] = useState('');
  const [selected, setSelected] = useState<string>('');
  const [error, setError] = useState<string | null>(null);

  async function load() {
    const list = collection<Contractor>(await api('/contractors'));
    setItems(list);
    if (!selected && list[0]) setSelected(list[0].id);
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    await api('/contractors', { method: 'POST', body: JSON.stringify({ companyName, hsePrequalificationScore: score }) });
    setCompanyName('');
    await load();
  }

  async function addCert(event: FormEvent) {
    event.preventDefault();
    if (!selected) return;
    setError(null);
    try {
      await api(`/contractors/${selected}/certifications`, {
        method: 'POST',
        body: JSON.stringify({ type: certType, expiresAt }),
      });
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ثبت گواهی ناموفق');
    }
  }

  return (
    <section className="space-y-6">
      <div className="grid lg:grid-cols-2 gap-6">
        <form onSubmit={onSubmit} className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">پیمانکار جدید</h3>
          <input className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={companyName} onChange={(e) => setCompanyName(e.target.value)} required />
          <input className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={score} onChange={(e) => setScore(e.target.value)} />
          <button className="rounded-lg bg-ember text-ink px-4 py-2">ثبت</button>
        </form>
        <form onSubmit={addCert} className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">گواهی صلاحیت</h3>
          <select className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={selected} onChange={(e) => setSelected(e.target.value)}>
            {items.map((item) => <option key={item.id} value={item.id}>{item.companyName}</option>)}
          </select>
          <input className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={certType} onChange={(e) => setCertType(e.target.value)} />
          <input type="date" className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={expiresAt} onChange={(e) => setExpiresAt(e.target.value)} required />
          <button className="rounded-lg border border-line px-4 py-2 text-sm">افزودن گواهی</button>
        </form>
      </div>
      {error ? <p className="text-rust text-sm">{error}</p> : null}
      <div className="rounded-2xl border border-line bg-panel p-5 space-y-4">
        {items.map((item) => (
          <article key={item.id} className="border-b border-line pb-3">
            <p className="font-medium">{item.companyName}</p>
            <p className="text-sm text-muted">امتیاز پیش‌ارزیابی: {item.hsePrequalificationScore ?? '—'}</p>
            <ul className="mt-2 text-sm">
              {(item.certifications ?? []).map((cert) => (
                <li key={cert.id} className={cert.expiringSoon ? 'text-rust' : 'text-muted'}>
                  {cert.type} · انقضا {faDate(cert.expiresAt)} {cert.expiringSoon ? '· نزدیک انقضا' : ''}
                </li>
              ))}
            </ul>
          </article>
        ))}
      </div>
    </section>
  );
}
