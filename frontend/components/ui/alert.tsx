import { AlertCircle, CheckCircle2, Info, TriangleAlert } from 'lucide-react';

export function Alert({ children, tone = 'danger' }: { children: React.ReactNode; tone?: 'danger' | 'success' | 'warning' | 'info' }) {
  const Icon = { danger: AlertCircle, success: CheckCircle2, warning: TriangleAlert, info: Info }[tone];
  const colors = { danger: 'border-red-200 bg-red-50 text-red-900', success: 'border-green-200 bg-green-50 text-green-900', warning: 'border-amber-200 bg-amber-50 text-amber-900', info: 'border-line bg-canvas text-ink' }[tone];
  return <div role={tone === 'danger' || tone === 'warning' ? 'alert' : 'status'} className={`flex items-start gap-3 rounded-md border px-4 py-3 text-sm leading-6 ${colors}`}><Icon aria-hidden="true" size={20} className="mt-0.5 shrink-0" /><div className="min-w-0 flex-1">{children}</div></div>;
}
