import { proxyToBackend } from '@/lib/api/server';
export async function POST(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/owners/${encodeURIComponent(params.id)}/reactivate`); }
