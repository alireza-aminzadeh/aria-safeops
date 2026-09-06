import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../lib/api';
import { faDate, incidentType, mocTransitions, permitTransitions } from '../lib/labels';
import type { EquipmentStatus } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';
import { QrCard } from '../components/QrCard';

export function EquipmentStatusPage() {
  const { tag = '' } = useParams();
  const [data, setData] = useState<EquipmentStatus | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api<EquipmentStatus>(`/equipment/${encodeURIComponent(tag)}/status`)
      .then(setData)
      .catch((err) => setError(err instanceof Error ? err.message : 'خطا'));
  }, [tag]);

  if (error) return <p className="text-rust">{error}</p>;
  if (!data) return <p className="text-muted">در حال بارگذاری وضعیت تجهیز…</p>;

  const qrValue = `${window.location.origin}/equipment/${encodeURIComponent(tag)}`;

  return (
    <section className="grid xl:grid-cols-[1fr_16rem] gap-6">
      <div className="space-y-6">
        <div>
          <p className="text-xs tracking-[0.2em] text-ember">وضعیت تجهیز</p>
          <h2 className="text-2xl font-mono mt-1">{data.equipmentTag}</h2>
        </div>

        <div className={`rounded-2xl border p-5 ${data.hold.blocked ? 'border-rust bg-rust/10' : 'border-line bg-panel'}`}>
          <h3 className="mb-2">نگه‌داشت PetroOps</h3>
          {data.hold.blocked ? (
            <div>
              <p className="text-rust">این تجهیز به‌دلیل آنومالی باز، مسدود است — نمی‌توان مجوز جدید صادر کرد.</p>
              {data.hold.summary ? <p className="text-sm text-muted mt-2">{data.hold.summary}</p> : null}
              {data.hold.score != null ? <p className="text-xs text-muted mt-1">امتیاز ریسک: {data.hold.score}</p> : null}
            </div>
          ) : (
            <p className="text-mint text-sm">هیچ نگه‌داشت بازی از PetroOps ثبت نشده است.</p>
          )}
        </div>

        <div className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">مجوزهای باز/جاری روی این تجهیز</h3>
          <ul className="text-sm space-y-2">
            {data.permits.map((p) => (
              <li key={p.id} className="flex justify-between items-center border-b border-line/50 pb-1">
                <Link to={`/permits/${p.id}`} className="text-ember">{permitTransitions[p.permitTypeCode] ?? p.permitTypeCode}</Link>
                <StatusBadge value={p.status} />
              </li>
            ))}
            {data.permits.length === 0 ? <li className="text-muted">مجوز باز/جاری ثبت نشده است.</li> : null}
          </ul>
        </div>

        <div className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">MOC باز روی این تجهیز</h3>
          <ul className="text-sm space-y-2">
            {data.mocs.map((m) => (
              <li key={m.id} className="flex justify-between items-center border-b border-line/50 pb-1">
                <Link to={`/moc/${m.id}`} className="text-ember">{m.description.slice(0, 60)}</Link>
                <span className="text-muted">{mocTransitions[m.status] ?? m.status}</span>
              </li>
            ))}
            {data.mocs.length === 0 ? <li className="text-muted">MOC بازی برای این تجهیز ثبت نشده است.</li> : null}
          </ul>
        </div>

        <div className="rounded-2xl border border-line bg-panel p-5">
          <h3 className="mb-3">حوادث اخیر مرتبط با ناحیه</h3>
          <ul className="text-sm space-y-2">
            {data.recentIncidents.map((i) => (
              <li key={i.id} className="flex justify-between items-center border-b border-line/50 pb-1">
                <Link to={`/incidents/${i.id}`} className="text-ember">{incidentType[i.type] ?? i.type}</Link>
                <span className="text-muted">{faDate(i.reportedAt)}</span>
              </li>
            ))}
            {data.recentIncidents.length === 0 ? <li className="text-muted">حادثه‌ای برای این ناحیه ثبت نشده است.</li> : null}
          </ul>
        </div>
      </div>
      <QrCard value={qrValue} caption="QR روی تجهیز — اسکن برای مشاهده وضعیت لحظه‌ای" />
    </section>
  );
}
