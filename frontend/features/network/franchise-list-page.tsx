'use client';

import Link from 'next/link';
import { Building2, ChevronRight, Network, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { authApi } from '@/lib/api/auth';
import { networkApi } from '@/lib/api/network';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { ForbiddenState } from '@/components/ui/forbidden-state';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/layout/page-header';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { FranchiseOnboardingForm } from './franchise-onboarding-form';

const onboardingPermissions = ['franchises.create', 'positions.create', 'permissions.assign', 'users.create'];

export function FranchiseListPage() {
  const [search, setSearch] = useState('');
  const context = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context });
  const franchises = useQuery({ queryKey: ['franchises'], queryFn: networkApi.franchises, enabled: context.data?.permissions.includes('franchises.view') });
  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return term === '' ? franchises.data ?? [] : (franchises.data ?? []).filter((franchise) =>
      franchise.name.toLowerCase().includes(term) || franchise.code.toLowerCase().includes(term));
  }, [franchises.data, search]);

  if (context.isLoading) return <main className="page-shell"><Skeleton className="h-36" /></main>;
  if (!context.data?.permissions.includes('franchises.view')) return <main className="page-shell"><ForbiddenState /></main>;

  const canOnboard = context.data.organization?.organization_type === 'system'
    && onboardingPermissions.every((permission) => context.data!.permissions.includes(permission));

  return <main className="page-shell fade-up">
    <PageHeader eyebrow="Network administration" title="Franchises" description="Manage the Franchise network, onboarding state, and Organization hierarchy from one System-level workspace." />
    <section className="mt-8">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"><div><h2 className="text-lg font-semibold text-ink">Franchise network</h2><p className="mt-1 text-sm text-muted">Active Organizations directly beneath {context.data.organization?.name ?? 'the System Organization'}.</p></div><div className="relative w-full sm:w-72"><Search size={16} className="pointer-events-none absolute left-3.5 top-4 text-muted" /><Input aria-label="Search Franchises" placeholder="Search name or code" className="pl-10" value={search} onChange={(event) => setSearch(event.target.value)} /></div></div>
      {franchises.isLoading && <div className="mt-5 space-y-3"><Skeleton className="h-16" /><Skeleton className="h-16" /><Skeleton className="h-16" /></div>}
      {franchises.error && <div className="mt-5"><Alert>Franchise network could not be loaded. Try again after confirming the API is available.</Alert></div>}
      {!franchises.isLoading && !franchises.error && filtered.length === 0 && <div className="mt-5"><EmptyState title={search ? 'No matching Franchises' : 'No active Franchises'} description={search ? 'Try a different name or code.' : 'Use the onboarding workflow below when the first Franchise is ready.'} /></div>}
      {filtered.length > 0 && <Card className="mt-5 overflow-hidden"><CardContent className="p-0"><Table><TableHeader><tr><TableHead>Franchise</TableHead><TableHead>Code</TableHead><TableHead>Status</TableHead><TableHead>Hierarchy</TableHead><TableHead className="w-14"><span className="sr-only">Open</span></TableHead></tr></TableHeader><TableBody>{filtered.map((franchise) => <TableRow key={franchise.id}><TableCell><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-md bg-brand/10 text-brand"><Building2 size={17} /></span><span className="font-semibold">{franchise.name}</span></div></TableCell><TableCell className="font-mono text-xs text-muted">{franchise.code}</TableCell><TableCell><Badge variant="success">{franchise.status}</Badge></TableCell><TableCell><span className="inline-flex items-center gap-2 text-muted"><Network size={15} /> System → Franchise</span></TableCell><TableCell><Link aria-label={`Open ${franchise.name}`} href={`/admin/franchises/${franchise.id}`} className="grid h-9 w-9 place-items-center rounded-md text-muted hover:bg-surface-muted hover:text-brand"><ChevronRight size={18} /></Link></TableCell></TableRow>)}</TableBody></Table></CardContent></Card>}
    </section>
    {canOnboard ? <div className="mt-10"><FranchiseOnboardingForm context={context.data} /></div> : <div className="mt-10 border-t border-line pt-6 text-sm text-muted">Franchise onboarding requires System context and the existing Franchise, Position, Permission-assignment, and User creation capabilities.</div>}
  </main>;
}
