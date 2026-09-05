import { useEffect, useRef } from 'react';
import { api, getToken } from './api';

type MercureConfig = {
  enabled: boolean;
  hubUrl: string;
  token: string | null;
  topics: string[];
};

export function useMercure(onMessage: (payload: { type?: string; message?: string }) => void) {
  const onMessageRef = useRef(onMessage);
  onMessageRef.current = onMessage;

  useEffect(() => {
    if (!getToken()) return;
    let source: EventSource | null = null;
    let cancelled = false;
    void api<MercureConfig>('/mercure/config').then((config) => {
      if (cancelled || !config.enabled || !config.token) return;
      const url = new URL(config.hubUrl, window.location.origin);
      for (const topic of config.topics) {
        url.searchParams.append('topic', topic);
      }
      url.searchParams.set('authorization', `Bearer ${config.token}`);
      source = new EventSource(url.toString());
      source.onmessage = (event) => {
        try {
          onMessageRef.current(JSON.parse(event.data) as { type?: string; message?: string });
        } catch {
          onMessageRef.current({ type: 'event' });
        }
      };
    }).catch(() => undefined);
    return () => {
      cancelled = true;
      source?.close();
    };
  }, []);
}
