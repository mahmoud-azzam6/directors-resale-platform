import { proxyToBackend } from '@/lib/api/server';
export async function PUT(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/users/${encodeURIComponent(params.id)}`); }
export async function DELETE(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/users/${encodeURIComponent(params.id)}`); }
