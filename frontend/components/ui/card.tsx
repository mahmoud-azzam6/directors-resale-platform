import { cn } from '@/lib/utils';

export function Card({ className, children }: { className?: string; children: React.ReactNode }) { return <section className={cn('rounded-lg border border-line bg-surface shadow-soft', className)}>{children}</section>; }
export function CardHeader({ className, children }: { className?: string; children: React.ReactNode }) { return <div className={cn('border-b border-line px-6 py-5', className)}>{children}</div>; }
export function CardContent({ className, children }: { className?: string; children: React.ReactNode }) { return <div className={cn('px-6 py-6', className)}>{children}</div>; }
