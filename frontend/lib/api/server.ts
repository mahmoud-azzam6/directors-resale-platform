import { cookies } from 'next/headers';
import { NextResponse } from 'next/server';

const baseUrl = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';

export async function proxyToBackend(request: Request, path: string): Promise<NextResponse> {
  const token = cookies().get('directors_admin_token')?.value;
  if (!token) {
    return NextResponse.json(
      { success: false, error: { code: 'unauthenticated', message: 'Authentication is required.' } },
      { status: 401 },
    );
  }

  const hasBody = !['GET', 'HEAD'].includes(request.method);
  const backend = await fetch(`${baseUrl}${path}`, {
    method: request.method,
    headers: {
      Authorization: `Bearer ${token}`,
      ...(hasBody ? { 'Content-Type': 'application/json' } : {}),
    },
    body: hasBody ? await request.text() : undefined,
    cache: 'no-store',
  });
  const payload = await backend.json();
  const response = NextResponse.json(payload, { status: backend.status });
  if (backend.status === 401) response.cookies.delete('directors_admin_token');
  return response;
}
