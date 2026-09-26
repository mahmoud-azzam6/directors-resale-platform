import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/ownerships/${encodeURIComponent(params.id)}/acting-owner/history`); }
