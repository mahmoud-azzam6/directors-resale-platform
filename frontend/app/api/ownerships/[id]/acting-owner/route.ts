import { proxyToBackend } from '@/lib/api/server';
export async function GET(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/ownerships/${encodeURIComponent(params.id)}/acting-owner`); }
export async function POST(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/ownerships/${encodeURIComponent(params.id)}/acting-owner`); }
export async function PUT(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/ownerships/${encodeURIComponent(params.id)}/acting-owner`); }
export async function DELETE(request: Request, { params }: { params: { id: string } }) { return proxyToBackend(request, `/ownerships/${encodeURIComponent(params.id)}/acting-owner`); }
