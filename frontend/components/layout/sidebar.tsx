'use client';

import Link from 'next/link';
import Image from 'next/image';
import { usePathname } from 'next/navigation';
import { Building2, ChevronRight, Files, LayoutDashboard, LockKeyhole, Network, ShieldCheck, UsersRound, X } from 'lucide-react';
import type { AuthContext } from '@/types/auth';
import { cn } from '@/lib/utils';
import { IconButton } from '@/components/ui/button';

const navigation = [
  { label: 'Dashboard', href: '/admin', icon: LayoutDashboard },
  { label: 'Organizations', href: '/admin/organizations', permission: 'organizations.view', icon: Building2 },
  { label: 'Franchises', href: '/admin/franchises', permission: 'franchises.view', icon: Network },
  { label: 'Partner agencies', href: '/admin/partner-agencies', permission: 'partner_agencies.view', icon: Files },
  { label: 'Users', href: '/admin/users', permission: 'users.view', icon: UsersRound },
  { label: 'Positions', href: '/admin/positions', permission: 'positions.view', icon: ShieldCheck },
  { label: 'Permissions', href: '/admin/permissions', permission: 'permissions.view', icon: LockKeyhole },
];

export function Sidebar({ context, open, onClose }: { context: AuthContext; open: boolean; onClose: () => void }) {
  const pathname = usePathname();
  return <>
    {open && <button aria-label="Close navigation" className="fixed inset-0 z-30 bg-charcoal/25 lg:hidden" onClick={onClose} />}
    <aside className={cn('fixed inset-y-0 left-0 z-40 flex w-[272px] -translate-x-full flex-col border-r border-white/10 bg-charcoal px-5 py-6 text-white transition-transform lg:static lg:translate-x-0', open && 'translate-x-0')}>
      <div className="flex items-center justify-between px-2">
        <Link href="/admin" className="flex items-center gap-3" onClick={onClose}>
          <span className="grid h-10 w-10 place-items-center rounded-md bg-white p-1"><Image src="https://directorsoman.com/wp-content/uploads/2025/12/logo.svg" alt="Directors" width={34} height={34} /></span>
          <span><span className="block text-sm font-bold tracking-wide">DIRECTORS</span><span className="block text-[10px] uppercase tracking-[0.22em] text-white/50">Admin platform</span></span>
        </Link>
        <IconButton label="Close navigation" className="text-white/60 hover:bg-white/10 hover:text-white lg:hidden" onClick={onClose}><X size={18} /></IconButton>
      </div>
      <div className="mt-10 px-2"><p className="text-[10px] font-bold uppercase tracking-[0.18em] text-white/35">Workspace</p></div>
      <nav className="mt-3 space-y-1" aria-label="Primary navigation">
        {navigation.filter((item) => !item.permission || context.permissions.includes(item.permission)).map((item) => {
          const active = item.href === '/admin' ? pathname === item.href : pathname.startsWith(item.href);
          const Icon = item.icon;
          return <Link key={item.href} href={item.href} onClick={onClose} className={cn('group flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-white/65 transition hover:bg-white/10 hover:text-white', active && 'bg-white/10 text-white')}>
            <Icon size={18} className={cn('transition', active ? 'text-brand' : 'text-white/45 group-hover:text-brand')} /><span className="flex-1">{item.label}</span>{active && <ChevronRight size={15} className="text-brand" />}
          </Link>;
        })}
      </nav>
      <div className="mt-auto rounded-lg border border-white/10 bg-white/5 p-4"><p className="text-xs font-semibold text-white/80">Secure workspace</p><p className="mt-1 text-xs leading-5 text-white/45">Access is governed by your active Position and backend permissions.</p></div>
    </aside>
  </>;
}
