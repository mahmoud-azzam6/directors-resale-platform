'use client';

import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { ApiClientError } from '@/lib/api/client';
import { networkApi, type GeographicLocation } from '@/lib/api/network';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

import { activeChildren, canonicalPath, geographyLevels as levels, geographyLabels as labels } from './geography-contract';

export function GeographySelector({ canRead, onChange, savedLocationId }: { canRead: boolean; onChange: (id: number | null) => void; savedLocationId?: number | null }) {
  if (!canRead) return <Alert>اختيار الموقع غير متاح بصلاحياتك الحالية. الموقع المحفوظ لن يتغير.</Alert>;
  return <HydratedGeography savedLocationId={savedLocationId ?? null} onChange={onChange} />;
}

function HydratedGeography({ savedLocationId, onChange }: { savedLocationId: number | null; onChange: (id: number | null) => void }) {
  const ancestry = useQuery({ queryKey: ['onboarding-geography-ancestry', savedLocationId], queryFn: () => networkApi.geographicAncestry(savedLocationId as number), enabled: savedLocationId !== null, retry: false });
  const path = useMemo(() => canonicalPath(ancestry.data), [ancestry.data]);
  if (ancestry.isPending && savedLocationId !== null) return <p role="status" className="text-sm text-muted">جارٍ تحميل الموقع المحفوظ...</p>;
  if (ancestry.isError || (savedLocationId !== null && ancestry.data && path.length === 0)) {
    const status = ancestry.error instanceof ApiClientError ? ancestry.error.status : null;
    return <Alert>{status === 401 ? 'انتهت الجلسة. سجّل الدخول مجدداً.' : status === 403 ? 'ليس لديك صلاحية قراءة الموقع المحفوظ.' : 'تعذر تحميل الموقع المحفوظ. قيمته محفوظة.'}
      {status !== 401 && status !== 403 && <Button type="button" onClick={() => void ancestry.refetch()}>إعادة المحاولة</Button>}
    </Alert>;
  }
  return <GeographyBranch parent={null} onChange={onChange} savedPath={path} />;
}

function GeographyBranch({ parent, onChange, savedPath }: { parent: GeographicLocation | null; onChange: (id: number | null) => void; savedPath: GeographicLocation[] }) {
  const [selected, setSelected] = useState<GeographicLocation | null | undefined>(undefined);
  const query = useQuery({
    queryKey: ['onboarding-geography', parent?.id ?? 'roots'],
    queryFn: () => parent ? networkApi.geographicChildren(parent.id) : networkApi.geographicLocations(),
    retry: false,
  });
  const rows = activeChildren(query.data ?? [], parent);
  const saved = savedPath.find((row) => row.parent_id === (parent?.id ?? null)) ?? null;
  const active = selected === undefined ? saved : selected;

  if (query.isPending) return <p role="status" className="text-sm text-muted">جارٍ تحميل المواقع...</p>;
  if (query.isError) {
    const status = query.error instanceof ApiClientError ? query.error.status : null;
    return <Alert>{status === 401 ? 'انتهت الجلسة. سجّل الدخول مجدداً.' : status === 403 ? 'ليس لديك صلاحية قراءة المواقع.' : 'تعذر تحميل المواقع.'}
      {status !== 401 && status !== 403 && <Button type="button" onClick={() => void query.refetch()}>إعادة المحاولة</Button>}
    </Alert>;
  }
  if (rows.length === 0) return <p role="status" className="text-sm text-muted">{parent ? 'لا توجد مواقع فرعية نشطة. سيُستخدم الموقع المحدد.' : 'لا توجد دول نشطة. يمكنك ترك الموقع فارغاً.'}</p>;

  function choose(value: string, type: string) {
    const row = rows.find((item) => String(item.id) === value && item.location_type === type) ?? null;
    setSelected(row);
    onChange(row?.id ?? parent?.id ?? null);
  }

  return <div className="space-y-3">
    {levels.map((type, index) => {
      const options = rows.filter((row) => row.location_type === type);
      if (!options.length) return null;
      return <label key={type} className="block text-sm font-medium">{labels[index]}
        <select aria-label={labels[index]} className="mt-1 h-10 w-full rounded-md border border-line bg-surface px-3 text-sm"
          value={active?.location_type === type ? active.id : ''} onChange={(event) => choose(event.target.value, type)}>
          <option value="">بدون تحديد {labels[index]}</option>
          {options.map((row) => <option key={row.id} value={row.id}>{row.name_ar || row.name_en}</option>)}
        </select>
      </label>;
    })}
    {active && active.location_type !== 'DISTRICT' && <GeographyBranch key={active.id} parent={active} onChange={onChange} savedPath={selected === undefined ? savedPath : []} />}
  </div>;
}
