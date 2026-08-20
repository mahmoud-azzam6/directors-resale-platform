'use client';

import { useRouter } from 'next/navigation';
import { Building2, Network, ShieldAlert } from 'lucide-react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authApi } from '@/lib/api/auth';
import { networkApi } from '@/lib/api/network';
import { ApiClientError } from '@/lib/api/client';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { ForbiddenState } from '@/components/ui/forbidden-state';
import { PageHeader } from '@/components/layout/page-header';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export function FranchiseDetailPage({ id }: { id: string }) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const context = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context });
  const franchise = useQuery({ queryKey: ['franchise', id], queryFn: () => networkApi.franchise(id), enabled: context.data?.permissions.includes('franchises.view') });
  const agencies = useQuery({ queryKey: ['partner-agencies'], queryFn: networkApi.partnerAgencies, enabled: context.data?.permissions.includes('partner_agencies.view') });
  const archive = useMutation({
    mutationFn: () => networkApi.archiveFranchise(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['franchises'] });
      router.replace('/admin/franchises');
    },
  });

  if (context.isLoading) return <main className="page-shell"><Skeleton className="h-40" /></main>;
  if (!context.data?.permissions.includes('franchises.view')) return <main className="page-shell"><ForbiddenState /></main>;
  if (franchise.isLoading) return <main className="page-shell"><Skeleton className="h-40" /><Skeleton className="mt-6 h-64" /></main>;
  if (franchise.error || !franchise.data) return <main className="page-shell"><Alert>{franchise.error instanceof ApiClientError && franchise.error.status === 404 ? 'Franchise not found or inactive.' : 'Franchise details could not be loaded.'}</Alert></main>;

  const partnerAgencies = (agencies.data ?? []).filter((agency) => agency.parent_organization_id === franchise.data.id);
  const canArchive = context.data.permissions.includes('franchises.archive');

  function confirmArchive() {
    if (window.confirm(`Deactivate ${franchise.data!.name}? The Organization record will remain in the database.`)) archive.mutate();
  }

  return <main className="page-shell fade-up">
    <PageHeader eyebrow="Network administration" title={franchise.data.name} description="Franchise identity, System relationship, lifecycle state, and Partner Agency hierarchy." />
    {archive.error && <div className="mt-5"><Alert>Franchise deactivation failed. No local state was changed.</Alert></div>}
    <div className="mt-8 grid gap-6 lg:grid-cols-[1fr_0.72fr]"><Card><CardContent><div className="flex items-center justify-between gap-4"><div className="flex items-center gap-4"><span className="grid h-12 w-12 place-items-center rounded-md bg-brand/10 text-brand"><Building2 size={21} /></span><div><h2 className="font-semibold text-ink">Franchise Organization</h2><p className="mt-1 font-mono text-xs text-muted">{franchise.data.code}</p></div></div><Badge variant="success">{franchise.data.status}</Badge></div><dl className="mt-7 grid gap-5 border-t border-line pt-6 sm:grid-cols-2"><div><dt className="text-xs font-semibold uppercase text-muted">Organization type</dt><dd className="mt-1 text-sm font-semibold text-ink">Franchise</dd></div><div><dt className="text-xs font-semibold uppercase text-muted">Parent Organization</dt><dd className="mt-1 text-sm font-semibold text-ink">{context.data.organization?.name ?? `System #${franchise.data.parent_organization_id}`}</dd></div><div><dt className="text-xs font-semibold uppercase text-muted">Organization ID</dt><dd className="mt-1 text-sm text-ink">{franchise.data.id}</dd></div><div><dt className="text-xs font-semibold uppercase text-muted">Created</dt><dd className="mt-1 text-sm text-ink">{franchise.data.created_at ? new Date(franchise.data.created_at).toLocaleDateString() : 'Unavailable'}</dd></div></dl></CardContent></Card><Card className="h-fit"><CardContent><div className="flex items-start gap-3"><ShieldAlert size={19} className="mt-0.5 text-warning" /><div><h2 className="font-semibold text-ink">Lifecycle</h2><p className="mt-2 text-sm leading-6 text-muted">Deactivation archives this Franchise by setting it inactive. It does not physically delete the Organization row.</p></div></div>{canArchive && <Button variant="danger" className="mt-6 w-full" disabled={archive.isPending} onClick={confirmArchive}>{archive.isPending ? 'Deactivating...' : 'Deactivate Franchise'}</Button>}</CardContent></Card></div>
    <section className="mt-9"><div className="flex items-end justify-between gap-4"><div><p className="eyebrow">Organization hierarchy</p><h2 className="mt-2 text-xl font-semibold text-ink">Partner Agencies</h2><p className="mt-2 text-sm text-muted">Active Partner Agencies directly beneath this Franchise.</p></div><span className="inline-flex items-center gap-2 text-sm text-muted"><Network size={16} /> {partnerAgencies.length} active</span></div>{!context.data.permissions.includes('partner_agencies.view') ? <div className="mt-5"><Alert>You do not have the Partner Agency viewing capability.</Alert></div> : agencies.isLoading ? <div className="mt-5"><Skeleton className="h-36" /></div> : agencies.error ? <div className="mt-5"><Alert>Partner Agency overview could not be loaded.</Alert></div> : partnerAgencies.length === 0 ? <div className="mt-5"><EmptyState title="No active Partner Agencies" description="No Partner Agency currently appears beneath this Franchise in your authorized scope." /></div> : <Card className="mt-5 overflow-hidden"><CardContent className="p-0"><Table><TableHeader><tr><TableHead>Partner Agency</TableHead><TableHead>Code</TableHead><TableHead>Status</TableHead></tr></TableHeader><TableBody>{partnerAgencies.map((agency) => <TableRow key={agency.id}><TableCell className="font-semibold">{agency.name}</TableCell><TableCell className="font-mono text-xs text-muted">{agency.code}</TableCell><TableCell><Badge variant="success">{agency.status}</Badge></TableCell></TableRow>)}</TableBody></Table></CardContent></Card>}</section>
  </main>;
}
