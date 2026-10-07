import { proxyToBackend } from '@/lib/api/server';
export function GET(request: Request) { return proxyToBackend(request, '/my-listing-requests'); }
