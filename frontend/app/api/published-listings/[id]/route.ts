import { proxyToBackend } from '@/lib/api/server';
export function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/published-listings/${encodeURIComponent(params.id)}`); }
