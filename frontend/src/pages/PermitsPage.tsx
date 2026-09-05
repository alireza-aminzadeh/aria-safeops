import { FormEvent, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, collection } from '../lib/api';
import { faDate } from '../lib/labels';
import type { Permit, PermitType } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

export function PermitsPage() {
  const [items, setItems] = useState<Permit[]>([]);
  const [types, setTypes] = useState<PermitType[]>([]);
  const [permitType, setPermitType] = useState('');
  const [equipmentTag, setEquipmentTag] = useState('P-101');
  const [locationPlotRef, setLocationPlotRef] = useState('Unit-A / Plot-12');
  const [workDescription, setWorkDescription] = useState('');
  const [error, setError] = useState<string | null>(null);

  async function load() {
    const [permits, typeList] = await Promise.all([
      api<unknown>('/permits?itemsPerPage=50'),
      api<unknown>('/permit_types'),
    ]);
    const t = collection<PermitType>(typeList as never);
    setTypes(t);
    setItems(collection<Permit>(permits as never));
    if (!permitType && t[0]) setPermitType(`/api/permit_types/${t[0].id}`);
  }

  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api('/permits', {
        method: 'POST',
        body: JSON.stringify({ permitType, equipmentTag, locationPlotRef, workDescription }),
      });
      setWorkDescription('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'خطا در ثبت مجوز');
    }
  }

  return (
    <section className="grid lg:grid-cols-[22rem_1fr] gap-6">
      <form onSubmit={onSubmit} className="rounded-2xl border border-line bg-panel p-5 h-fit">
        <h3 className="mb-4">صدور مجوز کار</h3>
        <label className="text-xs text-muted">نوع مجوز</label>
        <select className="w-full mb-3 mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={permitType} onChange={(e) => setPermitType(e.target.value)}>
          {types.map((t) => (
            <option key={t.id} value={`/api/permit_types/${t.id}`}>{t.nameFa}</option>
          ))}
        </select>
        <label className="text-xs text-muted">تگ تجهیز</label>
        <input className="w-full mb-3 mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={equipmentTag} onChange={(e) => setEquipmentTag(e.target.value)} required />
        <label className="text-xs text-muted">مرجع Plot Plan</label>
        <input className="w-full mb-3 mt-1 rounded-lg bg-ink border border-line px-3 py-2" value={locationPlotRef} onChange={(e) => setLocationPlotRef(e.target.value)} />
        <label className="text-xs text-muted">شرح کار</label>
        <textarea className="w-full mb-4 mt-1 rounded-lg bg-ink border border-line px-3 py-2 min-h-24" value={workDescription} onChange={(e) => setWorkDescription(e.target.value)} required />
        {error ? <p className="text-rust text-sm mb-3">{error}</p> : null}
        <button className="w-full rounded-lg bg-ember text-ink py-2">ثبت پیش‌نویس</button>
      </form>
      <div className="rounded-2xl border border-line bg-panel overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-panel-2 text-muted">
            <tr>
              <th className="text-right p-3">تجهیز</th>
              <th className="text-right">نوع</th>
              <th className="text-right">وضعیت</th>
              <th className="text-right">تاریخ</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => (
              <tr key={item.id} className="border-t border-line/70">
                <td className="p-3"><Link to={`/permits/${item.id}`} className="text-ember font-mono">{item.equipmentTag}</Link></td>
                <td>{item.permitType?.nameFa ?? '—'}</td>
                <td><StatusBadge value={item.status} /></td>
                <td className="text-muted">{faDate(item.createdAt)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}
