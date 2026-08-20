import { proxyToBackend } from '@/lib/api/server';

export async function POST(request: Request) {
  return proxyToBackend(request, '/network/franchises/onboard');
}
