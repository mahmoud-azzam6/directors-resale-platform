import { Skeleton } from '@/components/ui/skeleton';
export default function AdminLoading() { return <main className="page-shell"><Skeleton className="h-32 w-full" /><div className="mt-8 grid gap-5 md:grid-cols-3"><Skeleton className="h-40" /><Skeleton className="h-40" /><Skeleton className="h-40" /></div></main>; }
