import { cookies } from 'next/headers';
import { NextResponse } from 'next/server';
export async function GET(request: Request, { params }: { params: { id: string } }) {
  const token = cookies().get('directors_admin_token')?.value;
  if (!token) return NextResponse.json({ success: false, error: { code: 'unauthenticated', message: 'Authentication is required.' } }, { status: 401 });
  if (!/^[1-9][0-9]*$/.test(params.id)) return NextResponse.json({ success: false, error: { code: 'not_found', message: 'Listing not found.' } }, { status: 404 });
  try {
    const base = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';
    const backend = await fetch(`${base}/listings/${params.id}/primary-image/content`, { headers: { Authorization: `Bearer ${token}` }, cache: 'no-store' });
    if (backend.ok && backend.headers.get('content-type')?.startsWith('image/webp')) return new NextResponse(backend.body, { headers: { 'Content-Type': 'image/webp', 'Cache-Control': 'private, no-store', 'X-Content-Type-Options': 'nosniff' } });
    const response = NextResponse.json(await backend.json(), { status: backend.status });
    if (backend.status === 401) response.cookies.delete('directors_admin_token');
    return response;
  } catch { return NextResponse.json({ success: false, error: { code: 'storage_unavailable', message: 'Image service is temporarily unavailable.' } }, { status: 503 }); }
}
