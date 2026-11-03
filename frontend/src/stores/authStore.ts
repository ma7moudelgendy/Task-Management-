import { create } from 'zustand';
import { User } from '@/types/user';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  setAuth: (user: User, token: string) => void;
  updateUser: (user: Partial<User>) => void;
  clearAuth: () => void;
}

const STORAGE_KEY_TOKEN = 'taskflow_auth_token';
const STORAGE_KEY_USER = 'taskflow_auth_user';

export const useAuthStore = create<AuthState>((set) => {
  const initialToken = localStorage.getItem(STORAGE_KEY_TOKEN);
  let initialUser: User | null = null;
  const userJson = localStorage.getItem(STORAGE_KEY_USER);
  if (userJson) {
    try {
      initialUser = JSON.parse(userJson);
    } catch {
      localStorage.removeItem(STORAGE_KEY_USER);
    }
  }

  return {
    user: initialUser,
    token: initialToken,
    isAuthenticated: Boolean(initialToken),

    setAuth: (user, token) => {
      localStorage.setItem(STORAGE_KEY_TOKEN, token);
      localStorage.setItem(STORAGE_KEY_USER, JSON.stringify(user));
      set({ user, token, isAuthenticated: true });
    },

    updateUser: (partialUser) => {
      set((state) => {
        if (!state.user) return state;
        const updated = { ...state.user, ...partialUser };
        localStorage.setItem(STORAGE_KEY_USER, JSON.stringify(updated));
        return { user: updated };
      });
    },

    clearAuth: () => {
      localStorage.removeItem(STORAGE_KEY_TOKEN);
      localStorage.removeItem(STORAGE_KEY_USER);
      set({ user: null, token: null, isAuthenticated: false });
    },
  };
});

