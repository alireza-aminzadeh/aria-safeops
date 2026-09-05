import { FormEvent, useEffect, useState } from 'react';
import { api } from '../lib/api';
import type { Api754, HazopItem } from '../lib/types';

export function PsmPage() {
  const [items, setItems] = useState<HazopItem[]>([]);
  const [kpis, setKpis] = useState<Api754 | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [nodeDescription, setNode] = useState('پمپ خوراک P-101');
  const [deviation, setDeviation] = useState('More temperature');
  const [cause, setCause] = useState('');
  const [consequence, setConsequence] = useState('');
  const [safeguards, setSafeguards] = useState('');
  const [riskRanking, setRisk] = useState('medium');
  const [equipmentTag, setTag] = useState('P-101');

  async function load() {
    const [hazop, kpi] = await Promise.all([
      api<{ items: HazopItem[] }>('/hazop-items'),
      api<Api754>('/kpis/api754'),
    ]);
    setItems(hazop.items);
    setKpis(kpi);
  }

  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api('/hazop-items', {
        method: 'POST',
        body: JSON.stringify({ nodeDescription, deviation, cause, consequence, safeguards, riskRanking, equipmentTag }),
      });
      setCause('');
      setConsequence('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ثبت HAZOP ناموفق');
    }
  }

  async function addLopa(id: string) {
    await api(`/hazop-items/${id}/lopa`, {
      method: 'POST',
      body: JSON.stringify({ initiatingEvent: 'شکست کنترل', iplCount: 2, targetFrequency: '1e-4 /yr', residualRisk: 'medium' }),
    });
    await load();
  }

  async function addBarrier(id: string, side: 'prevention' | 'mitigation') {
    await api(`/hazop-items/${id}/barriers`, {
      method: 'POST',
      body: JSON.stringify({ side, description: side === 'prevention' ? 'لایه پیشگیری' : 'لایه کاهش پیامد', effectiveness: 'medium' }),
    });
    await load();
  }

  return (
    <section className="space-y-6">
      <div>
        <p className="text-xs tracking-[0.2em] text-ember">PHASE 2 · PSM</p>
        <h2 className="text-2xl mt-1">HAZOP / LOPA / Bowtie و API 754</h2>
      </div>
      {kpis ? (
        <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">Tier 1</p><p className="text-2xl">{kpis.tier1}</p></div>
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">Tier 2</p><p className="text-2xl">{kpis.tier2}</p></div>
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">Tier 3</p><p className="text-2xl">{kpis.tier3}</p></div>
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">شاخص پیش‌رو</p><p className="text-sm mt-2">HAZOP باز بالا: {kpis.leading.openHighHazop}</p></div>
        </div>
      ) : null}
      <div className="grid lg:grid-cols-[22rem_1fr] gap-6">
        <form onSubmit={onSubmit} className="rounded-2xl border border-line bg-panel p-5 h-fit">
          <h3 className="mb-3">گره HAZOP</h3>
          <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={equipmentTag} onChange={(e) => setTag(e.target.value)} placeholder="تگ تجهیز" />
          <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={nodeDescription} onChange={(e) => setNode(e.target.value)} required />
          <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={deviation} onChange={(e) => setDeviation(e.target.value)} required />
          <textarea className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={cause} onChange={(e) => setCause(e.target.value)} placeholder="علت" required />
          <textarea className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={consequence} onChange={(e) => setConsequence(e.target.value)} placeholder="پیامد" required />
          <textarea className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={safeguards} onChange={(e) => setSafeguards(e.target.value)} placeholder="لایه‌های حفاظتی" required />
          <select className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={riskRanking} onChange={(e) => setRisk(e.target.value)}>
            <option value="low">کم</option>
            <option value="medium">متوسط</option>
            <option value="high">بالا</option>
            <option value="critical">بحرانی</option>
          </select>
          {error ? <p className="text-rust text-sm mb-2">{error}</p> : null}
          <button className="w-full rounded-lg bg-ember text-ink py-2">ثبت گره</button>
        </form>
        <div className="space-y-4">
          {items.map((item) => (
            <article key={item.id} className="rounded-2xl border border-line bg-panel p-5">
              <p className="font-mono text-ember">{item.equipmentTag ?? '—'} · {item.deviation} · {item.riskRanking}</p>
              <p className="text-sm mt-2">{item.nodeDescription}</p>
              <p className="text-sm text-muted mt-1">{item.cause} → {item.consequence}</p>
              <p className="text-xs text-muted mt-2">حفاظت: {item.safeguards}</p>
              <div className="flex flex-wrap gap-2 mt-3 text-xs">
                <button className="rounded-lg border border-line px-3 py-1" onClick={() => void addLopa(item.id)}>افزودن LOPA</button>
                <button className="rounded-lg border border-line px-3 py-1" onClick={() => void addBarrier(item.id, 'prevention')}>مانع پیشگیری</button>
                <button className="rounded-lg border border-line px-3 py-1" onClick={() => void addBarrier(item.id, 'mitigation')}>مانع کاهش</button>
              </div>
              <ul className="mt-3 text-xs text-muted space-y-1">
                {item.lopa.map((row) => <li key={row.id}>LOPA · {row.initiatingEvent} · IPL {row.iplCount} · {row.targetFrequency}</li>)}
                {item.barriers.map((row) => <li key={row.id}>Bowtie · {row.side} · {row.description}</li>)}
              </ul>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
