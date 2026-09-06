import { useOfflineQueue } from '../lib/offlineQueue';

export function OfflineBanner() {
  const { online, pendingCount } = useOfflineQueue();

  if (online && pendingCount === 0) return null;

  return (
    <div
      className={`mb-4 flex items-center gap-2 rounded-lg px-3 py-2 text-sm ${
        online ? 'bg-ember/20 text-ember' : 'bg-rust/20 text-rust'
      }`}
    >
      <span className={`h-2 w-2 rounded-full ${online ? 'bg-ember' : 'bg-rust'}`} />
      {online ? null : <span>آفلاین — اتصال اینترنت برقرار نیست.</span>}
      {pendingCount > 0 ? (
        <span>
          {pendingCount} مورد در صف ارسال {online ? '— در حال ارسال…' : '— با بازگشت اتصال ارسال می‌شود'}
        </span>
      ) : null}
    </div>
  );
}
