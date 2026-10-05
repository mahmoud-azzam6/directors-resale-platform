import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request) { return proxyToBackend(request, '/geographic-locations' + new URL(request.url).search); }
