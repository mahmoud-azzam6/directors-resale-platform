import { redirect } from 'next/navigation';
import { AdminShell } from '@/components/layout/admin-shell';
import { getServerAuthContext } from '@/lib/server-auth';

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const context = await getServerAuthContext();
  if (!context) redirect('/login');
  return <AdminShell initialContext={context}>{children}</AdminShell>;
}
