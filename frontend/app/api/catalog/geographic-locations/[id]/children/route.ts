import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/geographic-locations/${params.id}/children` + new URL(request.url).search); }
