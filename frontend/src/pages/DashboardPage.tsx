import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../lib/api';
import { faDate, sumCounts } from '../lib/labels';
import type { Api754, Dashboard } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

function Card({ title, value, hint }: { title: string; value: number; hint: string }) {
  return (
    <div className="rounded-2xl border border-line bg-panel p-5">
      <p className="text-sm text-muted">{title}</p>
      <p className="text-3xl mt-2 font-medium">{value}</p>
      <p className="text-xs text-muted mt-2">{hint}</p>
    </div>
  );
}

export function DashboardPage() {
  const [data, setData] = useState<Dashboard | null>(null);
  const [kpis, setKpis] = useState<Api754 | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    Promise.all([api<Dashboard>('/dashboard'), api<Api754>('/kpis/api754')])
      .then(([dash, kpi]) => { setData(dash); setKpis(kpi); })
      .catch((err) => setError(err instanceof Error ? err.message : 'خطا'));
  }, []);

  if (error) return <p className="text-rust">{error}</p>;
  if (!data) return <p className="text-muted">در حال بارگذاری داشبورد…</p>;

  return (
    <section className="space-y-8">
      <div>
        <p className="text-xs tracking-[0.2em] text-ember">PHASE 2 · PSM + BPMS</p>
        <h2 className="text-2xl mt-1">وضعیت عملیات ایمنی</h2>
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
    </section>
  );
}
