import { FormEvent, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api } from '../lib/api';
import { faDate, sumCounts } from '../lib/labels';
import type { Api754, Dashboard, SafetyPeriodMetric } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

function Card({ title, value, hint }: { title: string; value: number | string; hint: string }) {
  return (
    <div className="rounded-2xl border border-line bg-panel p-5">
      <p className="text-sm text-muted">{title}</p>
      <p className="text-3xl mt-2 font-medium">{value}</p>
      <p className="text-xs text-muted mt-2">{hint}</p>
    </div>
  );
}

export function DashboardPage() {
  const navigate = useNavigate();
  const [data, setData] = useState<Dashboard | null>(null);
  const [kpis, setKpis] = useState<Api754 | null>(null);
  const [metrics, setMetrics] = useState<SafetyPeriodMetric[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [equipmentQuery, setEquipmentQuery] = useState('');
  const [periodStart, setPeriodStart] = useState('');
  const [periodEnd, setPeriodEnd] = useState('');
  const [hoursWorked, setHoursWorked] = useState('');
  const [employeeCount, setEmployeeCount] = useState('');
  const [metricError, setMetricError] = useState<string | null>(null);

  async function loadAll() {
    const [dash, kpi, metricList] = await Promise.all([
      api<Dashboard>('/dashboard'),
      api<Api754>('/kpis/api754'),
      api<{ items: SafetyPeriodMetric[] }>('/safety-period-metrics'),
    ]);
    setData(dash);
    setKpis(kpi);
    setMetrics(metricList.items ?? []);
  }

  useEffect(() => {
    loadAll().catch((err) => setError(err instanceof Error ? err.message : 'خطا'));
  }, []);

  async function submitMetric(event: FormEvent) {
    event.preventDefault();
    setMetricError(null);
    try {
      await api('/safety-period-metrics', {
        method: 'POST',
        body: JSON.stringify({
          periodStart,
          periodEnd,
          hoursWorked,
          employeeCount: employeeCount || null,
        }),
      });
      setPeriodStart('');
      setPeriodEnd('');
      setHoursWorked('');
      setEmployeeCount('');
      await loadAll();
    } catch (err) {
      setMetricError(err instanceof Error ? err.message : 'ثبت ساعت‌کار ناموفق');
    }
  }

  if (error) return <p className="text-rust">{error}</p>;
  if (!data) return <p className="text-muted">در حال بارگذاری داشبورد…</p>;

  return (
    <section className="space-y-8">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs tracking-[0.2em] text-ember">PHASE 2 · PSM + BPMS</p>
          <h2 className="text-2xl mt-1">وضعیت عملیات ایمنی</h2>
        </div>
        <form
          className="flex gap-2"
          onSubmit={(e) => {
            e.preventDefault();
            if (equipmentQuery.trim()) navigate(`/equipment/${encodeURIComponent(equipmentQuery.trim())}`);
          }}
        >
          <input
            className="rounded-lg bg-ink border border-line px-3 py-2 text-sm w-48"
            placeholder="جست‌وجوی وضعیت تجهیز (تگ)"
            value={equipmentQuery}
            onChange={(e) => setEquipmentQuery(e.target.value)}
          />
          <button className="rounded-lg border border-line px-4 py-2 text-sm">مشاهده</button>
        </form>
      </div>
      <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <Card title="مجوزهای فعال" value={data.permits.active ?? 0} hint="مجوزهایی که الان در میدان معتبرند" />
        <Card title="در انتظار بررسی HSE" value={(data.permits.submitted ?? 0) + (data.permits.hse_review ?? 0)} hint="نیاز به تأیید یا بررسی" />
        <Card title="MOC باز" value={sumCounts(data.mocs, ['proposed', 'risk_assessment', 'approval', 'implementation', 'pssr'])} hint="بدون PSSR بسته نمی‌شود" />
        <Card title="حوادث باز" value={sumCounts(data.incidents, ['reported', 'under_investigation', 'capa_assigned'])} hint="تا بستن CAPA پیگیری شود" />
      </div>
      {kpis ? (
        <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <Card title="API 754 T1" value={kpis.tier1} hint="رویداد پس‌روی جدی" />
          <Card title="API 754 T2" value={kpis.tier2} hint="LOPC خفیف‌تر" />
          <Card title="HAZOP پرریسک باز" value={kpis.leading.openHighHazop} hint="شاخص پیش‌رو" />
          <Card title="رویداد Vision باز" value={kpis.leading.openVisionEvents} hint="PPE / ناحیه ممنوعه" />
        </div>
      ) : null}
      {kpis ? (
        <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <Card
            title="LTIFR (۱۲ ماه گذشته)"
            value={kpis.safety.ltifr ?? '—'}
            hint={kpis.safety.hoursWorkedTtm != null ? `بر مبنای ${kpis.safety.hoursWorkedTtm.toLocaleString('fa-IR')} ساعت‌کار` : 'ابتدا ساعت‌کار دوره را ثبت کنید'}
          />
          <Card
            title="TRIR (۱۲ ماه گذشته)"
            value={kpis.safety.trir ?? '—'}
            hint={`${kpis.safety.recordableCountTtm} رویداد ثبت‌شدنی`}
          />
          <Card title="آسیب با ازدست‌رفتن روز کار" value={kpis.safety.lostTimeInjuriesTtm} hint="۱۲ ماه گذشته" />
          <Card title="گواهی منقضی/نزدیک انقضا" value={kpis.leading.expiredCertifications} hint="پیمانکاران" />
        </div>
      ) : null}
      <div className="grid lg:grid-cols-2 gap-6">
        <div className="rounded-2xl border border-line bg-panel p-5">
          <div className="flex justify-between items-center mb-4">
            <h3>توزیع مجوز کار</h3>
            <Link to="/permits" className="text-sm text-ember">مشاهده</Link>
          </div>
          <div className="space-y-2 text-sm">
            {Object.entries(data.permits).map(([status, count]) => (
              <div key={status} className="flex justify-between items-center">
                <StatusBadge value={status} />
                <span className="font-mono">{count}</span>
              </div>
            ))}
            {Object.keys(data.permits).length === 0 ? <p className="text-muted">هنوز مجوزی ثبت نشده است.</p> : null}
          </div>
        </div>
        <div className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-4">گواهی‌های در حال انقضا</h3>
          <div className="space-y-3">
            {data.expiringCertifications.map((item) => (
              <div key={item.id} className="flex justify-between gap-3 text-sm border-b border-line/60 pb-2">
                <div>
                  <p>{item.companyName}</p>
                  <p className="text-muted text-xs">{item.type}</p>
                </div>
                <span className={item.expiringSoon ? 'text-rust' : 'text-muted'}>{faDate(item.expiresAt)}</span>
              </div>
            ))}
            {data.expiringCertifications.length === 0 ? <p className="text-muted text-sm">گواهی نزدیک به انقضا نیست.</p> : null}
          </div>
        </div>
      </div>
      <div className="rounded-2xl border border-line bg-panel p-5">
        <h3 className="mb-1">ثبت ساعت‌کار دوره (مخرج LTIFR/TRIR)</h3>
        <p className="text-xs text-muted mb-4">بدون ثبت ساعت‌کار واقعی، این دو شاخص محاسبه نمی‌شوند — عدد ساختگی نشان داده نمی‌شود.</p>
        <form onSubmit={submitMetric} className="grid sm:grid-cols-4 gap-3">
          <div>
            <label className="text-xs text-muted">شروع دوره</label>
            <input type="date" required className="w-full mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={periodStart} onChange={(e) => setPeriodStart(e.target.value)} />
          </div>
          <div>
            <label className="text-xs text-muted">پایان دوره</label>
            <input type="date" required className="w-full mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={periodEnd} onChange={(e) => setPeriodEnd(e.target.value)} />
          </div>
          <div>
            <label className="text-xs text-muted">ساعت‌کار کل</label>
            <input type="number" min={0} step="0.01" required className="w-full mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={hoursWorked} onChange={(e) => setHoursWorked(e.target.value)} />
          </div>
          <div>
            <label className="text-xs text-muted">تعداد نفرات (اختیاری)</label>
            <input type="number" min={0} className="w-full mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={employeeCount} onChange={(e) => setEmployeeCount(e.target.value)} />
          </div>
          <button className="sm:col-span-4 rounded-lg bg-ember text-ink py-2 text-sm">ثبت دوره</button>
        </form>
        {metricError ? <p className="text-rust text-sm mt-3">{metricError}</p> : null}
        {metrics.length > 0 ? (
          <ul className="mt-4 text-sm space-y-1">
            {metrics.slice(0, 6).map((m) => (
              <li key={m.id} className="flex justify-between border-b border-line/50 py-1">
                <span>{faDate(m.periodStart)} تا {faDate(m.periodEnd)}</span>
                <span className="font-mono">{m.hoursWorked} ساعت</span>
              </li>
            ))}
          </ul>
        ) : null}
      </div>
    </section>
  );
}
