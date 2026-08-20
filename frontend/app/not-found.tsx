import Link from 'next/link';
import { Button } from '@/components/ui/button';

export default function NotFound() {
  return <main className="grid min-h-screen place-items-center bg-canvas p-8"><div className="text-center"><p className="eyebrow">Directors Admin</p><h1 className="mt-3 text-4xl font-semibold text-ink">Page not found</h1><p className="mt-3 text-muted">The page you requested does not exist.</p><Button asChild className="mt-7"><Link href="/admin">Return to dashboard</Link></Button></div></main>;
}
