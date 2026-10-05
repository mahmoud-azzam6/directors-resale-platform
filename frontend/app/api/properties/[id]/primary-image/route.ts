import { primaryImageProxy } from '@/lib/api/primary-image-proxy';
type Context = { params: { id: string } };
export function GET(request: Request, { params }: Context) { return primaryImageProxy(request, params.id); }
export function POST(request: Request, { params }: Context) { return primaryImageProxy(request, params.id); }
export function DELETE(request: Request, { params }: Context) { return primaryImageProxy(request, params.id); }
