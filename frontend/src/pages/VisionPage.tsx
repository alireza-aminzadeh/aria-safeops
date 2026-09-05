import { FormEvent, useEffect, useState } from 'react';
import { api } from '../lib/api';
import { faDate, visionType } from '../lib/labels';
import type { VisionOverview } from '../lib/types';

export function VisionPage() {
  const [data, setData] = useState<VisionOverview | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [name, setName] = useState('');
  const [area, setArea] = useState('process');
  const [rtspUrl, setRtsp] = useState('');

  async function load() {
    setData(await api<VisionOverview>('/vision/overview'));
  }
  useEffect(() => {
    void load().catch((err) => setError(err instanceof Error ? err.message : 'خطا'));
    const timer = window.setInterval(() => { void load().catch(() => undefined); }, 20000);
    return () => window.clearInterval(timer);
  }, []);

  async function addCamera(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await api('/vision/cameras', { method: 'POST', body: JSON.stringify({ name, area, rtspUrl }) });
      setName('');
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ثبت دوربین ناموفق');
    }
  }

  return (
    <section className="space-y-6">
      <div>
        <p className="text-xs tracking-[0.2em] text-ember">PHASE 2 · VISION</p>
        <h2 className="text-2xl mt-1">HSE Vision</h2>
        <p className="text-sm text-muted mt-2">
          {data?.simulator ? 'شبیه‌ساز رویداد PPE روی این سرور فعال است. استنتاج GPU وقتی VISION_GATEWAY_URL ست شود وصل می‌شود.' : 'ingest از سرویس Vision خارجی.'}
        </p>
      </div>
      {error ? <p className="text-rust text-sm">{error}</p> : null}
      <form onSubmit={addCamera} className="rounded-2xl border border-line bg-panel p-5 grid sm:grid-cols-4 gap-3">
        <input className="rounded-lg bg-ink border border-line px-3 py-2" value={name} onChange={(e) => setName(e.target.value)} placeholder="نام دوربین" required />
        <input className="rounded-lg bg-ink border border-line px-3 py-2" value={area} onChange={(e) => setArea(e.target.value)} placeholder="ناحیه" />
        <input className="rounded-lg bg-ink border border-line px-3 py-2" value={rtspUrl} onChange={(e) => setRtsp(e.target.value)} placeholder="rtsp://" />
        <button className="rounded-lg bg-ember text-ink">افزودن دوربین</button>
      </form>
      <div className="grid lg:grid-cols-2 gap-6">
        <div className="rounded-2xl border border-line bg-panel p-5 space-y-2">
          <h3 className="mb-3">دوربین‌ها</h3>
          {(data?.cameras ?? []).map((cam) => (
            <p key={cam.id} className="text-sm">{cam.name} · {cam.area} · <span className="font-mono text-xs text-muted">{cam.rtspUrl ?? 'بدون RTSP'}</span></p>
          ))}
        </div>
        <div className="rounded-2xl border border-line bg-panel p-5 space-y-3">
          <h3 className="mb-3">رویدادها</h3>
          {(data?.events ?? []).map((item) => (
            <article key={item.id} className="border-b border-line pb-2 text-sm">
              <p className={item.status === 'open' ? 'text-rust' : 'text-muted'}>{visionType[item.eventType] ?? item.eventType} · {item.camera.name}</p>
              <p className="text-muted">{item.summary}</p>
              <p className="text-xs text-muted">{Math.round(item.confidence * 100)}٪ · {faDate(item.detectedAt)}</p>
              {item.status === 'open' ? (
                <button className="text-ember text-xs mt-1" onClick={() => void api(`/vision/events/${item.id}/ack`, { method: 'POST' }).then(load)}>تأیید مشاهده</button>
              ) : null}
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
