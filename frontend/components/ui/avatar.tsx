import { initials } from '@/lib/utils';

export function Avatar({ name, size = 'md' }: { name: string; size?: 'sm' | 'md' | 'lg' }) {
  const styles = { sm: 'h-8 w-8 text-[10px]', md: 'h-10 w-10 text-xs', lg: 'h-14 w-14 text-sm' };
  return <div className={`${styles[size]} grid shrink-0 place-items-center rounded-full bg-charcoal text-white font-semibold ring-4 ring-brand/10`}>{initials(name)}</div>;
}
