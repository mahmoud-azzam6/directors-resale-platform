import { cn } from '@/lib/utils';

export function Badge({ children, variant = 'muted' }: { children: React.ReactNode; variant?: 'muted' | 'success' | 'warning' | 'danger' | 'info' }) {
  const styles = { muted: 'bg-surface-muted text-muted', success: 'bg-success/10 text-success', warning: 'bg-warning/10 text-warning', danger: 'bg-danger/10 text-danger', info: 'bg-info/10 text-info' };
  return <span className={cn('inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', styles[variant])}>{children}</span>;
}
