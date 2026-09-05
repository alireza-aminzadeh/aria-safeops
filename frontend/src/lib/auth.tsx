import { createContext, createElement, useContext, useEffect, useState, type ReactNode } from 'react';
import { api, clearToken, getToken } from './api';
import type { Me } from './types';

const AuthContext = createContext<{ me: Me | null; reload: () => Promise<void> }>({
  me: null,
  reload: async () => {},
});

export function AuthProvider({ children }: { children: ReactNode }) {
  const [me, setMe] = useState<Me | null>(null);

  async function reload() {
    if (!getToken()) {
      setMe(null);
      return;
    }
    try {
      setMe(await api<Me>('/me'));
    } catch {
      clearToken();
      setMe(null);
    }
  }

  useEffect(() => { void reload(); }, []);

  return createElement(AuthContext.Provider, { value: { me, reload } }, children);
}

export function useAuth() {
  return useContext(AuthContext);
}
