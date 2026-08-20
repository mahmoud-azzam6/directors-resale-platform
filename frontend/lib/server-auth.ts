import { cookies } from 'next/headers';
import type { AuthContext } from '@/types/auth';

const baseUrl = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';

export async function getServerAuthContext(): Promise<AuthContext | null> {
  const token = cookies().get('directors_admin_token')?.value;
  if (!token) return null;
  const response = await fetch(`${baseUrl}/auth/context`, {
    headers: { Authorization: `Bearer ${token}` },
    cache: 'no-store',
  });
  if (!response.ok) return null;
  const payload = (await response.json()) as { success: boolean; data: AuthContext };
  return payload.success ? payload.data : null;
}
