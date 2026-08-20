import { forwardRef } from 'react';
import { cn } from '@/lib/utils';

export const Checkbox = forwardRef<HTMLInputElement, React.InputHTMLAttributes<HTMLInputElement>>(
  function Checkbox({ className, ...props }, ref) {
    return <input ref={ref} type="checkbox" className={cn('h-4 w-4 rounded-sm border-line text-brand accent-[var(--brand-gold)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40', className)} {...props} />;
  },
);
