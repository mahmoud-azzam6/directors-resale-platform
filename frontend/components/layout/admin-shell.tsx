'use client';

import { useRouter } from 'next/navigation';
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import type { AuthContext } from '@/types/auth';
import { authApi } from '@/lib/api/auth';
import { Sidebar } from './sidebar';
import { Header } from './header';
import { Skeleton } from '@/components/ui/skeleton';

export function AdminShell({ initialContext, children }: { initialContext: AuthContext; children: React.ReactNode }) {
  const router = useRouter();
  const [menuOpen, setMenuOpen] = useState(false);
  const query = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context, initialData: initialContext });
  const context = query.data ?? initialContext;

  async function logout() {
    await authApi.logout().catch(() => undefined);
    router.replace('/login');
    router.refresh();
  }

  if (query.isLoading) return <div className="grid min-h-screen place-items-center p-8"><Skeleton className="h-20 w-80" /></div>;
  if (query.error) return <div className="grid min-h-screen place-items-center p-8 text-center"><p className="text-sm text-muted">Your session has expired. <button className="font-semibold text-brand" onClick={() => router.replace('/login')}>Sign in again</button></p></div>;

  return <div className="flex min-h-screen bg-canvas"><Sidebar context={context} open={menuOpen} onClose={() => setMenuOpen(false)} /><div className="min-w-0 flex-1"><Header context={context} onMenu={() => setMenuOpen(true)} onLogout={logout} />{children}</div></div>;
}
