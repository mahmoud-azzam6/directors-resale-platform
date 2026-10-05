import { cookies } from 'next/headers';
import { NextResponse } from 'next/server';
export async function primaryImageProxy(request: Request, id: string, content = false) {
  const token = cookies().get('directors_admin_token')?.value;
  if (!token) return NextResponse.json({ success: false, error: { code: 'unauthenticated', message: 'Authentication is required.' } }, { status: 401 });
  if (!/^[1-9][0-9]*$/.test(id)) return NextResponse.json({ success: false, error: { code: 'not_found', message: 'Property not found.' } }, { status: 404 });
  let body: FormData | undefined;
  if (request.method === 'POST') {
    const length = request.headers.get('content-length');
    if (length && Number(length) > 11 * 1024 * 1024) return NextResponse.json({ success: false, error: { code: 'validation_error', message: 'Image upload is too large.' } }, { status: 422 });
    try { body = await request.formData(); } catch { return NextResponse.json({ success: false, error: { code: 'validation_error', message: 'One image file is required.' } }, { status: 422 }); }
    const image = body.get('image');
    if (!(image instanceof File) || image.size > 10 * 1024 * 1024 || Array.from(body.keys()).length !== 1) return NextResponse.json({ success: false, error: { code: 'validation_error', message: 'One image up to 10 MB is required.' } }, { status: 422 });
  }
  try {
    const base = process.env.PHP_API_BASE_URL ?? 'http://localhost/directors-resale-platform/public';
    const backend = await fetch(`${base}/organization-properties/${id}/primary-image${content ? '/content' : ''}`, { method: request.method, headers: { Authorization: `Bearer ${token}` }, body, cache: 'no-store' });
    if (content && backend.ok && backend.headers.get('content-type')?.startsWith('image/webp')) return new NextResponse(backend.body, { headers: { 'Content-Type': 'image/webp', 'Cache-Control': 'private, no-store', 'X-Content-Type-Options': 'nosniff' } });
    const response = NextResponse.json(await backend.json(), { status: backend.status });
    if (backend.status === 401) response.cookies.delete('directors_admin_token');
    return response;
  } catch { return NextResponse.json({ success: false, error: { code: 'storage_unavailable', message: 'Image service is temporarily unavailable.' } }, { status: 503 }); }
}
