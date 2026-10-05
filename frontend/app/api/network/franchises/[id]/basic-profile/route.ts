import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/organizations/${params.id}/basic-profile`); }
export async function PUT(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/organizations/${params.id}/basic-profile`); }
