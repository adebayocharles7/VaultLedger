import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User } from '../api/types';

interface AuthState {
  token: string | null;
  user: User | null;
  setSession: (token: string, user: User) => void;
  logout: () => void;
}

// persist() writes this store to localStorage under "vaultledger-auth",
// so refreshing the page doesn't log you out. This is the same idea as
// Sanctum's cookie/session persistence, just on the client side.
export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      setSession: (token, user) => set({ token, user }),
      logout: () => set({ token: null, user: null }),
    }),
    { name: 'vaultledger-auth' }
  )
);
