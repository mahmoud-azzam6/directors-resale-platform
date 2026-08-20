'use client';

import { useEffect } from 'react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

export default function AdminError({ reset }: { error: Error & { digest?: string }; reset: () => void }) { useEffect(() => undefined, []); return <main className="page-shell"><div className="mx-auto mt-16 max-w-lg"><Alert>We could not load this workspace right now.</Alert><Button variant="outline" className="mt-5" onClick={() => reset()}>Try again</Button></div></main>; }
