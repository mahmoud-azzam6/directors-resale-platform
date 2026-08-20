import { proxyToBackend } from '@/lib/api/server';

export async function GET(request: Request) {
  return proxyToBackend(request, '/franchises');
}
