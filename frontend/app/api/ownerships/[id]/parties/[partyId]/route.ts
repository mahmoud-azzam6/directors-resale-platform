import { proxyToBackend } from '@/lib/api/server';
export async function PUT(request: Request, { params }: { params: { id: string; partyId: string } }) { return proxyToBackend(request, `/ownerships/${encodeURIComponent(params.id)}/parties/${encodeURIComponent(params.partyId)}`); }
export async function DELETE(request: Request, { params }: { params: { id: string; partyId: string } }) { return proxyToBackend(request, `/ownerships/${encodeURIComponent(params.id)}/parties/${encodeURIComponent(params.partyId)}`); }
