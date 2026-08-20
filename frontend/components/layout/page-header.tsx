import { ArrowUpRight } from 'lucide-react';

export function PageHeader({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
  return <div className="flex flex-col justify-between gap-5 border-b border-line pb-7 md:flex-row md:items-end"><div><p className="eyebrow">{eyebrow}</p><h1 className="mt-2 text-3xl font-semibold tracking-[-0.03em] text-ink md:text-4xl">{title}</h1><p className="mt-3 max-w-2xl text-sm leading-6 text-muted">{description}</p></div><div className="hidden h-10 w-10 place-items-center rounded-md border border-line bg-surface text-brand md:grid"><ArrowUpRight size={18} /></div></div>;
}
