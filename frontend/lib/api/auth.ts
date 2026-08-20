import type { AuthContext } from '@/types/auth';
import { apiFetch } from './client';

export const authApi = {
  context: () => apiFetch<AuthContext>('/api/auth/context'),
  logout: () => apiFetch<unknown>('/api/auth/logout', { method: 'POST' }),
};
