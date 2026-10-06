'use client';

import { LogOut, Menu, MapPin } from 'lucide-react';
import type { AuthContext } from '@/types/auth';
import { Avatar } from '@/components/ui/avatar';
import { IconButton } from '@/components/ui/button';

export function Header({ context, onMenu, onLogout }: { context: AuthContext; onMenu: () => void; onLogout: () => void }) {
  return <header className="sticky top-0 z-20 flex min-h-[78px] items-center justify-between border-b border-line bg-canvas/90 px-5 backdrop-blur md:px-8 lg:px-10">
    <div className="flex items-center gap-3"><IconButton label="فتح القائمة" className="lg:hidden" onClick={onMenu}><Menu size={20} /></IconButton><div><p className="eyebrow">مساحة العمل التشغيلية</p><p className="mt-1 hidden text-sm text-muted sm:block">إدارة بيانات مؤسستك ضمن صلاحياتك.</p></div></div>
    <div className="flex items-center gap-3"><div className="hidden items-center gap-2 rounded-md border border-line bg-surface px-3 py-2 text-xs text-muted sm:flex"><MapPin size={14} className="text-brand" /><span className="max-w-[180px] truncate">{context.organization?.name ?? 'المؤسسة الحالية'}</span></div><div className="hidden text-right md:block"><p className="text-sm font-semibold text-ink">{context.user.full_name}</p><p className="text-xs text-muted">{context.position?.name ?? 'لم يُعيّن منصب'}</p></div><Avatar name={context.user.full_name} size="sm" /><IconButton label="تسجيل الخروج" onClick={onLogout}><LogOut size={17} /></IconButton></div>
  </header>;
}
