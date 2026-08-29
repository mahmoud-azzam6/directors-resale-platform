import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/positions/${encodeURIComponent(params.id)}/permissions`); }
export async function PUT(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/positions/${encodeURIComponent(params.id)}/permissions`); }
