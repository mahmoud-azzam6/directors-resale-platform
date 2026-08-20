import Link from 'next/link';
import { cn } from '@/lib/utils';

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & { variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger'; asChild?: boolean };

export function Button({ className, variant = 'primary', asChild, children, ...props }: ButtonProps) {
  const styles = { primary: 'bg-charcoal text-white hover:bg-charcoal/90', secondary: 'bg-brand text-white hover:bg-brand/90', outline: 'border border-line bg-surface text-ink hover:border-brand', ghost: 'text-muted hover:bg-surface-muted hover:text-ink', danger: 'bg-danger text-white hover:bg-danger/90' };
  if (asChild) return <span className={cn('inline-flex items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold transition focus-within:ring-2 focus-within:ring-brand/40', styles[variant], className)}>{children}</span>;
  return <button className={cn('inline-flex items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 disabled:cursor-not-allowed disabled:opacity-50', styles[variant], className)} {...props}>{children}</button>;
}

export function IconButton({ className, label, children, ...props }: React.ButtonHTMLAttributes<HTMLButtonElement> & { label: string }) {
  return <button aria-label={label} title={label} className={cn('grid h-10 w-10 place-items-center rounded-md text-muted transition hover:bg-surface-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40', className)} {...props}>{children}</button>;
}

export { Link };
