import { FormEvent, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, collection } from '../lib/api';
import { changeType } from '../lib/labels';
import type { Moc } from '../lib/types';
import { StatusBadge } from '../components/StatusBadge';

export function MocPage() {
  const [items, setItems] = useState<Moc[]>([]);
  const [description, setDescription] = useState('');
  const [changeTypeValue, setChangeTypeValue] = useState('permanent');
  const [equipmentTag, setEquipmentTag] = useState('P-101');
  const [error, setError] = useState<string | null>(null);

  async function load() {
    setItems(collection<Moc>(await api('/moc_requests?itemsPerPage=50')));
  }
  useEffect(() => { void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا')); }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    await api('/moc_requests', { method: 'POST', body: JSON.stringify({ description, changeType: changeTypeValue, equipmentTag }) });
    setDescription('');
    await load();
  }

  return (
    <section className="grid lg:grid-cols-[22rem_1fr] gap-6">
      <form onSubmit={onSubmit} className="rounded-2xl border border-line bg-panel p-5 h-fit">
        <h3 className="mb-4">درخواست مدیریت تغییر</h3>
        <select className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={changeTypeValue} onChange={(e) => setChangeTypeValue(e.target.value)}>
          <option value="temporary">موقت</option>
          <option value="permanent">دائم</option>
          <option value="emergency">اضطراری</option>
        </select>
        <input className="w-full mb-3 rounded-lg bg-ink border border-line px-3 py-2" value={equipmentTag} onChange={(e) => setEquipmentTag(e.target.value)} placeholder="تگ تجهیز" />
        <textarea className="w-full mb-4 rounded-lg bg-ink border border-line px-3 py-2 min-h-28" value={description} onChange={(e) => setDescription(e.target.value)} required />
        {error ? <p className="text-rust text-sm mb-3">{error}</p> : null}
        <button className="w-full rounded-lg bg-ember text-ink py-2">ثبت</button>
      </form>
      <div className="rounded-2xl border border-line bg-panel p-5 space-y-4">
        {items.map((item) => (
          <article key={item.id} className="border-b border-line pb-3">
            <Link to={`/moc/${item.id}`} className="text-ember">{item.description}</Link>
            <p className="text-sm text-muted mt-1">{changeType[item.changeType] ?? item.changeType} · {item.equipmentTag ?? '—'}</p>
            <div className="mt-2"><StatusBadge value={item.status} kind="moc" /></div>
          </article>
        ))}
      </div>
    </section>
  );
}
