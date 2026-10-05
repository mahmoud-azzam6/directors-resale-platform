'use client';

import Link from 'next/link';
import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authApi } from '@/lib/api/auth';
import { ApiClientError } from '@/lib/api/client';
import { networkApi } from '@/lib/api/network';
import type { OrganizationBasicProfile } from '@/types/network';
import { PageHeader } from '@/components/layout/page-header';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ForbiddenState } from '@/components/ui/forbidden-state';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { GeographySelector } from './geography-selector';

function RequestError({ error, retry }: { error: Error; retry?: () => void }) {
  const status = error instanceof ApiClientError ? error.status : null;
  if (status === 401) return <Alert><span>انتهت الجلسة. <Link href="/login" className="underline">تسجيل الدخول مجدداً</Link></span></Alert>;
  if (status === 403) return <Alert>ليس لديك صلاحية لتنفيذ هذه العملية.</Alert>;
  return <Alert><div><p>{status === 422 ? 'يرجى تصحيح البيانات التالية.' : 'تعذر إكمال الطلب. بياناتك المدخلة محفوظة في الصفحة.'}</p>
    {error instanceof ApiClientError && error.fields && <ul className="mt-2 space-y-1">{Object.entries(error.fields).map(([field, message]) => <li key={field}>{field === 'address_text' ? 'العنوان' : field === 'geographic_location_id' ? 'الموقع' : field}: {message}</li>)}</ul>}
    {retry && status !== 422 && <Button type="button" variant="outline" className="mt-3" onClick={retry}>إعادة المحاولة</Button>}
  </div></Alert>;
}

export function OrganizationProfilePage() {
  const context = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context, retry: false });
  const organization = context.data?.organization;
  const [selectedId, setSelectedId] = useState('');
  const isSystem = organization?.organization_type === 'system';
  const canView = ['franchise', 'system'].includes(organization?.organization_type ?? '') && context.data?.permissions.includes('organizations.view');
  const organizations = useQuery({ queryKey: ['profile-organizations'], queryFn: networkApi.organizations, enabled: !!canView && isSystem, retry: false });
  const franchises = organizations.data?.filter((row) => row.organization_type === 'franchise') ?? [];
  const target = isSystem ? franchises.find((row) => String(row.id) === selectedId) : organization;
  const id = String(target?.id ?? '');
  const profile = useQuery({
    queryKey: ['organization-basic-profile', id], queryFn: () => networkApi.basicProfile(id),
    enabled: !!canView && !!id, retry: false, refetchOnWindowFocus: false,
  });

  return <main className="page-shell space-y-6" dir="rtl">
    <PageHeader eyebrow="إدارة المؤسسة" title="الملف الأساسي للمؤسسة" description={organization?.name ?? 'الموقع الجغرافي والعنوان الاختياري للمؤسسة الحالية.'} />
    {canView && isSystem && <div><Label htmlFor="profile-franchise">الفرنشايز</Label>
      {organizations.isPending ? <p role="status">جارٍ تحميل المؤسسات...</p> : organizations.error ? <RequestError error={organizations.error} retry={() => void organizations.refetch()} />
        : <select id="profile-franchise" className="mt-1 h-12 w-full rounded-md border border-line bg-surface px-3" value={selectedId} onChange={(event) => setSelectedId(event.target.value)}>
          <option value="">اختر الفرنشايز</option>{franchises.map((row) => <option key={row.id} value={row.id}>{row.name}</option>)}
        </select>}
    </div>}
    {context.isPending ? <p role="status">جارٍ تحميل الحساب...</p>
      : context.error ? <RequestError error={context.error} retry={() => void context.refetch()} />
      : !canView ? <ForbiddenState />
      : !id ? <p role="status">اختر فرنشايز لعرض ملفه وتعديله.</p>
      : profile.isPending ? <p role="status">جارٍ تحميل الملف...</p>
      : profile.error ? <RequestError error={profile.error} retry={() => void profile.refetch()} />
      : <ProfileForm key={id} id={id} initialName={target?.name ?? ''} initialProfile={profile.data ?? null}
          canUpdate={context.data!.permissions.includes('organizations.update')}
          canReadGeography={context.data!.permissions.includes('property_catalogs.view')} />}
  </main>;
}

function ProfileForm({ id, initialName, initialProfile, canUpdate, canReadGeography }: {
  id: string; initialName: string; initialProfile: OrganizationBasicProfile | null; canUpdate: boolean; canReadGeography: boolean;
}) {
  const queryClient = useQueryClient();
  const [savedProfile, setSavedProfile] = useState(initialProfile);
  const [name, setName] = useState(initialName);
  const [address, setAddress] = useState(initialProfile?.address_text ?? '');
  const [locationId, setLocationId] = useState<number | null>(initialProfile?.geographic_location_id ?? null);
  const [hydrationId, setHydrationId] = useState<number | null>(initialProfile?.geographic_location_id ?? null);
  const [selectorVersion, setSelectorVersion] = useState(0);
  const [saved, setSaved] = useState(false);
  const mutation = useMutation({
    mutationFn: () => networkApi.saveBasicProfile(id, { organization_name: name.trim(), geographic_location_id: locationId, address_text: address.trim() || null }),
    retry: false,
    onSuccess: (data) => {
      setSavedProfile(data);
      setAddress(data.address_text ?? '');
      setLocationId(data.geographic_location_id);
      setHydrationId(data.geographic_location_id);
      setSelectorVersion((version) => version + 1);
      queryClient.setQueryData(['organization-basic-profile', id], data);
      void queryClient.invalidateQueries({ queryKey: ['profile-organizations'] });
      void queryClient.invalidateQueries({ queryKey: ['auth-context'] });
      setSaved(true);
    },
  });
  const denied = mutation.error instanceof ApiClientError && [401, 403].includes(mutation.error.status);
  function save() {
    if (!canUpdate || denied || mutation.isPending) return;
    setSaved(false);
    mutation.mutate();
  }

  return <Card><CardContent className="space-y-5">
    {!savedProfile && <p role="status" className="text-sm text-muted">لا يوجد ملف أساسي محفوظ. الموقع والعنوان اختياريان.</p>}
    <div><h2 className="font-semibold">العنوان المحفوظ</h2><p className="mt-2 whitespace-pre-wrap text-sm" dir="auto">{savedProfile?.address_text ?? 'لم يتم تحديد عنوان.'}</p></div>
    {saved && <Alert tone="success">تم حفظ الملف الأساسي بنجاح.</Alert>}
    {mutation.error && <RequestError error={mutation.error} retry={save} />}
    {!canUpdate && <p className="text-sm text-muted">عرض فقط: لا تتوفر صلاحية تعديل المؤسسة.</p>}
    <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); save(); }} onChange={() => setSaved(false)}>
      <fieldset disabled={!canUpdate || denied || mutation.isPending} className="space-y-5">
        <legend className="mb-3 font-semibold">الموقع والعنوان (اختياري)</legend>
        <div><Label htmlFor="organization-name">اسم المؤسسة</Label><Input id="organization-name" required maxLength={255} value={name} onChange={(event) => setName(event.target.value)} /></div>
        <GeographySelector key={selectorVersion} canRead={canReadGeography} savedLocationId={hydrationId} onChange={setLocationId} />
        {canReadGeography && <Button type="button" variant="outline" onClick={() => {
          setLocationId(null); setHydrationId(null); setSelectorVersion((version) => version + 1); setSaved(false);
        }}>مسح الموقع</Button>}
        <div><Label htmlFor="organization-address">العنوان</Label><textarea id="organization-address" dir="auto" rows={3}
          className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm"
          maxLength={1000} value={address} onChange={(event) => setAddress(event.target.value)} /></div>
        {canUpdate && <Button type="submit">{mutation.isPending ? 'جارٍ الحفظ...' : 'حفظ الملف الأساسي'}</Button>}
      </fieldset>
    </form>
  </CardContent></Card>;
}
