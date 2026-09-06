import { FormEvent, useEffect, useState } from 'react';
import { api } from '../lib/api';
import { faDate, scenarioType } from '../lib/labels';
import type { EffluentReading, EmergencyDrill, EmergencyOverview, ErpPlan } from '../lib/types';

export function EmergencyPage() {
  const [plans, setPlans] = useState<ErpPlan[]>([]);
  const [drills, setDrills] = useState<EmergencyDrill[]>([]);
  const [readings, setReadings] = useState<EffluentReading[]>([]);
  const [overview, setOverview] = useState<EmergencyOverview | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [planTitle, setPlanTitle] = useState('');
  const [planScenario, setPlanScenario] = useState('fire');
  const [planDescription, setPlanDescription] = useState('');

  const [drillScenario, setDrillScenario] = useState('');
  const [drillParticipants, setDrillParticipants] = useState('10');
  const [drillDuration, setDrillDuration] = useState('30');

  const [readingParameter, setReadingParameter] = useState('BOD');
  const [readingValue, setReadingValue] = useState('');
  const [readingLocation, setReadingLocation] = useState('خروجی پساب واحد');

  async function load() {
    const [p, d, r, o] = await Promise.all([
      api<{ items: ErpPlan[] }>('/erp-plans'),
      api<{ items: EmergencyDrill[] }>('/emergency-drills'),
      api<{ items: EffluentReading[] }>('/effluent-readings'),
      api<EmergencyOverview>('/emergency/overview'),
    ]);
    setPlans(p.items);
    setDrills(d.items);
    setReadings(r.items);
    setOverview(o);
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function createPlan(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api('/erp-plans', { method: 'POST', body: JSON.stringify({ title: planTitle, scenarioType: planScenario, description: planDescription }) });
      setPlanTitle('');
      setPlanDescription('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ثبت طرح ناموفق');
    }
  }

  async function createDrill(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api('/emergency-drills', {
        method: 'POST',
        body: JSON.stringify({
          scenario: drillScenario,
          participantCount: Number(drillParticipants) || 0,
          durationMinutes: Number(drillDuration) || 0,
        }),
      });
      setDrillScenario('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ثبت مانور ناموفق');
    }
  }

  async function createReading(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api('/effluent-readings', {
        method: 'POST',
        body: JSON.stringify({ parameter: readingParameter, value: Number(readingValue) || 0, location: readingLocation }),
      });
      setReadingValue('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ثبت پساب ناموفق');
    }
  }

  return (
    <section className="space-y-6">
      <div>
        <p className="text-xs tracking-[0.2em] text-ember">PHASE 3 · EMERGENCY & ENVIRONMENT</p>
        <h2 className="text-2xl mt-1">واکنش اضطراری و محیط‌زیست</h2>
      </div>
      {error ? <p className="text-rust text-sm">{error}</p> : null}
      {overview ? (
        <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">طرح‌های ERP</p><p className="text-2xl mt-1">{overview.totalPlans}</p></div>
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">طرح‌های معوق بازنگری</p><p className="text-2xl mt-1 text-rust">{overview.overduePlans}</p></div>
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">مانور در ۱۲ ماه گذشته</p><p className="text-2xl mt-1">{overview.drillsLastYear}</p></div>
          <div className="rounded-2xl border border-line bg-panel p-4"><p className="text-xs text-muted">پساب غیرمنطبق (۱۲ ماه)</p><p className="text-2xl mt-1 text-rust">{overview.nonCompliantReadingsLastYear}</p></div>
        </div>
      ) : null}

      <div className="grid lg:grid-cols-3 gap-6">
        <div>
          <form onSubmit={createPlan} className="rounded-2xl border border-line bg-panel p-5">
            <h3 className="mb-3">طرح واکنش اضطراری (ERP)</h3>
            <select className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={planScenario} onChange={(e) => setPlanScenario(e.target.value)}>
              {Object.entries(scenarioType).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
            <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" placeholder="عنوان طرح" value={planTitle} onChange={(e) => setPlanTitle(e.target.value)} required />
            <textarea className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2 min-h-20" placeholder="شرح اقدامات واکنش" value={planDescription} onChange={(e) => setPlanDescription(e.target.value)} required />
            <button className="w-full rounded-lg bg-ember text-ink py-2">ثبت طرح</button>
          </form>
          <div className="mt-4 space-y-2 text-sm">
            {plans.map((p) => (
              <div key={p.id} className={`rounded-xl border p-3 ${p.overdue ? 'border-rust bg-rust/10' : 'border-line bg-panel'}`}>
                <p>{scenarioType[p.scenarioType] ?? p.scenarioType} — {p.title}</p>
                <p className="text-xs text-muted mt-1">بازنگری بعدی: {faDate(p.nextReviewDue)}{p.overdue ? ' (معوق)' : ''}</p>
              </div>
            ))}
            {plans.length === 0 ? <p className="text-muted">هنوز طرحی ثبت نشده است.</p> : null}
          </div>
        </div>

        <div>
          <form onSubmit={createDrill} className="rounded-2xl border border-line bg-panel p-5">
            <h3 className="mb-3">مانور اضطراری</h3>
            <input className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" placeholder="سناریوی مانور" value={drillScenario} onChange={(e) => setDrillScenario(e.target.value)} required />
            <div className="grid grid-cols-2 gap-2 mb-3">
              <input type="number" min={0} className="rounded-lg bg-ink border border-line px-3 py-2" placeholder="تعداد شرکت‌کننده" value={drillParticipants} onChange={(e) => setDrillParticipants(e.target.value)} />
              <input type="number" min={0} className="rounded-lg bg-ink border border-line px-3 py-2" placeholder="مدت (دقیقه)" value={drillDuration} onChange={(e) => setDrillDuration(e.target.value)} />
            </div>
            <button className="w-full rounded-lg bg-ember text-ink py-2">ثبت مانور</button>
          </form>
          <div className="mt-4 space-y-2 text-sm">
            {drills.map((d) => (
              <div key={d.id} className="rounded-xl border border-line bg-panel p-3">
                <p>{d.scenario}</p>
                <p className="text-xs text-muted mt-1">{faDate(d.heldAt)} · {d.participantCount} نفر · {d.durationMinutes} دقیقه · رهبر: {d.leaderName}</p>
              </div>
            ))}
            {drills.length === 0 ? <p className="text-muted">هنوز مانوری ثبت نشده است.</p> : null}
          </div>
        </div>

        <div>
          <form onSubmit={createReading} className="rounded-2xl border border-line bg-panel p-5">
            <h3 className="mb-3">قرائت پساب/انتشار</h3>
            <select className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" value={readingParameter} onChange={(e) => setReadingParameter(e.target.value)}>
              <option value="BOD">BOD</option>
              <option value="COD">COD</option>
              <option value="TSS">TSS</option>
              <option value="pH">pH</option>
              <option value="Oil_Grease">روغن و چربی</option>
            </select>
            <input type="number" step="0.01" className="w-full mb-2 rounded-lg bg-ink border border-line px-3 py-2" placeholder="مقدار اندازه‌گیری‌شده" value={readingValue} onChange={(e) => setReadingValue(e.target.value)} required />
            <input className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" placeholder="محل نمونه‌برداری" value={readingLocation} onChange={(e) => setReadingLocation(e.target.value)} />
            <button className="w-full rounded-lg bg-ember text-ink py-2">ثبت قرائت</button>
          </form>
          <div className="mt-4 space-y-2 text-sm">
            {readings.map((r) => (
              <div key={r.id} className={`rounded-xl border p-3 ${r.compliant ? 'border-line bg-panel' : 'border-rust bg-rust/10'}`}>
                <p>{r.parameter}: {r.value} {r.unit} {r.limitValue != null ? `(حد: ${r.limitValue})` : ''}</p>
                <p className="text-xs text-muted mt-1">{r.location} · {faDate(r.sampledAt)} · {r.compliant ? 'منطبق' : 'غیرمنطبق'}</p>
              </div>
            ))}
            {readings.length === 0 ? <p className="text-muted">هنوز قرائتی ثبت نشده است.</p> : null}
          </div>
        </div>
      </div>
    </section>
  );
}
