import { primaryImageProxy } from '@/lib/api/primary-image-proxy';
export function GET(request: Request, { params }: { params: { id: string } }) { return primaryImageProxy(request, params.id, true); }
