import { Construction } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { PageHeader } from './page-header';
import { EmptyState } from '@/components/ui/empty-state';

export function PlaceholderPage({ section, title, description }: { section: string; title: string; description: string }) {
  return <main className="page-shell fade-up"><PageHeader eyebrow={section} title={title} description={description} /><div className="mt-8 grid gap-6 lg:grid-cols-[1.3fr_0.7fr]"><Card><CardContent className="p-0"><div className="flex items-center gap-4 border-b border-line px-6 py-5"><div className="grid h-10 w-10 place-items-center rounded-md bg-brand/10 text-brand"><Construction size={19} /></div><div><h2 className="font-semibold text-ink">Foundation view</h2><p className="mt-1 text-sm text-muted">The workspace is ready for the next approved workflow.</p></div></div><div className="p-6"><EmptyState title="No management records shown yet" description="AF001 establishes the authenticated workspace and navigation. Full management workflows will arrive in a later approved phase." /></div></CardContent></Card><Card className="h-fit"><CardContent><p className="eyebrow">Design reference</p><h2 className="mt-2 text-lg font-semibold text-ink">A calm place to work</h2><p className="mt-3 text-sm leading-6 text-muted">This screen is intentionally composed from reusable layout and UI primitives so its visual language can evolve independently from domain integrations.</p></CardContent></Card></div></main>;
}
