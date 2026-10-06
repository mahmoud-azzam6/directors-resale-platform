import { proxyToBackend } from '@/lib/api/server';
export function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/listings/${encodeURIComponent(params.id)}`); }
