import { NextResponse } from 'next/server';
import { cookies } from 'next/headers';

const baseUrl = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';

export async function POST() {
  const cookieStore = cookies();
  const token = cookieStore.get('directors_admin_token')?.value;
  if (token) {
    await fetch(`${baseUrl}/auth/logout`, { method: 'POST', headers: { Authorization: `Bearer ${token}` }, cache: 'no-store' }).catch(() => undefined);
  }
  const response = NextResponse.json({ success: true, data: null });
  response.cookies.set('directors_admin_token', '', { httpOnly: true, expires: new Date(0), path: '/' });
  return response;
}
