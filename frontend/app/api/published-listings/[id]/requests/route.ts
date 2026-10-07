import { proxyToBackend } from '@/lib/api/server';
export function POST(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/published-listings/${encodeURIComponent(params.id)}/requests`); }
