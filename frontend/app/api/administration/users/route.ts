import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request) { return proxyToBackend(request, '/user-administration/users'); }
export async function POST(request: Request) { return proxyToBackend(request, '/users'); }
