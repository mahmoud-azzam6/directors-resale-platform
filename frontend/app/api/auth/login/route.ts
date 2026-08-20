import { NextResponse } from 'next/server';

const baseUrl = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';

export async function POST(request: Request) {
  const body = await request.json();
  const backend = await fetch(`${baseUrl}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
    cache: 'no-store',
  });
  const payload = await backend.json();
  if (!backend.ok || !payload.success) {
    return NextResponse.json({ success: false, error: { code: 'authentication_failed', message: 'Invalid credentials.' } }, { status: 401 });
  }
  const token = payload.data.token as string;
  const response = NextResponse.json({ success: true, data: { user: payload.data.user } });
  response.cookies.set('directors_admin_token', token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: 60 * 60,
  });
  return response;
}
