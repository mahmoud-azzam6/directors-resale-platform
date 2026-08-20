import { proxyToBackend } from '@/lib/api/server';

export async function GET(request: Request, { params }: { params: { id: string } }) {
  return proxyToBackend(request, `/franchises/${encodeURIComponent(params.id)}`);
}

export async function DELETE(request: Request, { params }: { params: { id: string } }) {
  return proxyToBackend(request, `/franchises/${encodeURIComponent(params.id)}`);
}
