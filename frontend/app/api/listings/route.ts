import { proxyToBackend } from '@/lib/api/server';
export function GET(request: Request) { return proxyToBackend(request, `/listings${new URL(request.url).search}`); }
export function POST(request: Request) { return proxyToBackend(request, '/listings'); }
