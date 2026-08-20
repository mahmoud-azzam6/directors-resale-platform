import { AlertCircle, CheckCircle2 } from 'lucide-react';

export function Alert({ children, tone = 'danger' }: { children: React.ReactNode; tone?: 'danger' | 'success' }) {
  const Icon = tone === 'danger' ? AlertCircle : CheckCircle2;
  return <div role="alert" className={`flex items-start gap-3 rounded-md border px-4 py-3 text-sm ${tone === 'danger' ? 'border-danger/20 bg-danger/5 text-danger' : 'border-success/20 bg-success/5 text-success'}`}><Icon size={17} className="mt-0.5 shrink-0" />{children}</div>;
}
