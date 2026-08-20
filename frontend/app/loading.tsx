import { Skeleton } from '@/components/ui/skeleton';

export default function Loading() {
  return <main className="grid min-h-screen place-items-center bg-canvas p-8"><Skeleton className="h-24 w-full max-w-xl" /></main>;
}
