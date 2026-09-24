'use client';
import Link from 'next/link';
import { useQuery } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { propertyApi } from '@/lib/api/property';
import { ApiClientError } from '@/lib/api/client';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
export function PropertyListPage() {
 const query=useQuery({queryKey:['properties'],queryFn:propertyApi.list}); const error=query.error instanceof ApiClientError?query.error:null;
 return <main className="page-shell fade-up"><div className="flex flex-wrap items-end justify-between gap-4"><div><p className="eyebrow">Property administration</p><h1 className="mt-2 text-3xl font-semibold text-ink">Properties</h1><p className="mt-2 text-sm text-muted">Organization Properties only. This workspace does not create Listings.</p></div><Button asChild><Link href="/admin/properties/new"><Plus size={17} className="mr-2"/>Add Unit</Link></Button></div>{query.isLoading?<p className="mt-8 text-sm text-muted">Loading Properties...</p>:query.error?<div className="mt-8"><Alert>{error?.status===403?'Your current backend permissions do not allow Property access.':error?.message??'Properties could not be loaded.'}</Alert></div>:query.data?.length===0?<div className="mt-8"><EmptyState title="No Properties yet" description="Create an Organization Property to begin Property Data setup."/></div>:<div className="mt-8 grid gap-4">{query.data?.map((property)=><Card key={property.id}><CardHeader><div className="flex items-center justify-between gap-4"><div><h2 className="font-semibold text-ink">{property.property_label}</h2><p className="mt-1 text-xs text-muted">Property #{property.id} · {property.status}</p></div><Button asChild variant="outline"><Link href={`/admin/properties/${property.id}/setup/property-data`}>Property Data</Link></Button></div></CardHeader><CardContent><p className="text-sm text-muted">Continue with canonical catalog selections and the persisted Property Profile.</p></CardContent></Card>)}</div>}</main>;
}