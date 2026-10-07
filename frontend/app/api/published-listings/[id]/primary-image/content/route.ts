import { cookies } from 'next/headers';
import { NextResponse } from 'next/server';
export async function GET(_request: Request, { params }: { params: { id: string } }) {
  const token = cookies().get('directors_admin_token')?.value;
  if (!token) return NextResponse.json({ success: false, error: { code: 'unauthenticated', message: 'Authentication required.' } }, { status: 401 });
  if (!/^[1-9][0-9]*$/.test(params.id)) return NextResponse.json({ success: false, error: { code: 'not_found', message: 'Resource not found.' } }, { status: 404 });
  try {
    const base = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';
    const response = await fetch(`${base}/published-listings/${params.id}/primary-image/content`, { headers: { Authorization: `Bearer ${token}` }, cache: 'no-store' });
    if (response.ok && response.headers.get('content-type')?.startsWith('image/webp')) return new NextResponse(response.body, { headers: { 'Content-Type': 'image/webp', 'Cache-Control': 'private, no-store', 'X-Content-Type-Options': 'nosniff' } });
    const result = NextResponse.json(await response.json(), { status: response.status });
    if (response.status === 401) result.cookies.delete('directors_admin_token');
    return result;
  } catch { return NextResponse.json({ success: false, error: { code: 'unavailable', message: 'Service unavailable.' } }, { status: 503 }); }
}
