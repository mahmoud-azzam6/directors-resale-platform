import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/organization-properties/${params.id}/profile`); }
export async function PUT(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/organization-properties/${params.id}/profile`); }