import { NextResponse } from 'next/server';
import { cookies } from 'next/headers';

const baseUrl = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';

export async function GET() {
  const token = cookies().get('directors_admin_token')?.value;
  if (!token) return NextResponse.json({ success: false, error: { code: 'unauthenticated', message: 'Authentication is required.' } }, { status: 401 });
  const backend = await fetch(`${baseUrl}/auth/context`, { headers: { Authorization: `Bearer ${token}` }, cache: 'no-store' });
  const payload = await backend.json();
  const response = NextResponse.json(payload, { status: backend.status });
  if (backend.status === 401) response.cookies.delete('directors_admin_token');
  return response;
}
