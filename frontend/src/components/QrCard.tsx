import { useEffect, useState } from 'react';

export function QrCard({ value, caption }: { value: string; caption: string }) {
  const [src, setSrc] = useState<string>('');

  useEffect(() => {
    let cancelled = false;
    void import('qrcode').then((QR) =>
      QR.toDataURL(value, {
        margin: 1,
        width: 196,
        color: { dark: '#140b0a', light: '#f3ebe4' },
      }).then((url) => {
        if (!cancelled) setSrc(url);
      }),
    );
    return () => { cancelled = true; };
  }, [value]);

  return (
    <div className="rounded-2xl border border-line bg-panel p-4 text-center">
      <p className="text-xs text-muted mb-3">{caption}</p>
      {src ? <img src={src} alt="QR" className="mx-auto rounded-lg w-44 h-44 bg-paper" /> : <div className="h-44 grid place-items-center text-muted text-sm">در حال ساخت QR…</div>}
      <p className="mt-3 font-mono text-[11px] text-muted break-all">{value}</p>
    </div>
  );
}
