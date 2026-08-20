import { cn } from '@/lib/utils';

export function Table({ className, ...props }: React.TableHTMLAttributes<HTMLTableElement>) {
  return <div className="w-full overflow-x-auto"><table className={cn('w-full border-collapse text-left text-sm', className)} {...props} /></div>;
}
export function TableHeader(props: React.HTMLAttributes<HTMLTableSectionElement>) { return <thead className="border-b border-line bg-surface-muted/70 text-xs font-semibold uppercase text-muted" {...props} />; }
export function TableBody(props: React.HTMLAttributes<HTMLTableSectionElement>) { return <tbody className="divide-y divide-line" {...props} />; }
export function TableRow({ className, ...props }: React.HTMLAttributes<HTMLTableRowElement>) { return <tr className={cn('transition hover:bg-surface-muted/45', className)} {...props} />; }
export function TableHead({ className, ...props }: React.ThHTMLAttributes<HTMLTableCellElement>) { return <th className={cn('px-5 py-3 font-semibold', className)} {...props} />; }
export function TableCell({ className, ...props }: React.TdHTMLAttributes<HTMLTableCellElement>) { return <td className={cn('px-5 py-4 text-ink', className)} {...props} />; }
