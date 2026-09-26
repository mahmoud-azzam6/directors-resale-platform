import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/organization-properties/${encodeURIComponent(params.id)}/ownerships/current`); }
