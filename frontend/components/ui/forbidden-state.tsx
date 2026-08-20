import Link from 'next/link';
import { ShieldX } from 'lucide-react';
import { Button } from './button';

export function ForbiddenState() { return <div className="grid min-h-[55vh] place-items-center text-center"><div><div className="mx-auto grid h-16 w-16 place-items-center rounded-full bg-danger/10 text-danger"><ShieldX size={28} /></div><p className="eyebrow mt-6">Access restricted</p><h1 className="mt-2 text-3xl font-semibold text-ink">You don&apos;t have access to this area</h1><p className="mx-auto mt-3 max-w-md text-sm leading-6 text-muted">Your account is signed in, but its current permissions do not include this capability.</p><Button asChild variant="outline" className="mt-6"><Link href="/admin">Return to dashboard</Link></Button></div></div>; }
