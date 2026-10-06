'use client';
import Link from 'next/link';
import Image from 'next/image';
import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { authApi } from '@/lib/api/auth';
import { ApiClientError } from '@/lib/api/client';
import { arabicError, statusLabel } from '@/lib/ui/arabic-errors';
import { PropertySelector, createSelectedDraft, loadPropertyChoices } from './property-selector';
import { listingApi, type Listing } from '@/lib/api/listing';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

const errorText = (error: unknown) => arabicError(error, 'تعذر إكمال طلب الإعلان. تحقق من البيانات ثم أعد المحاولة.');
function ListingFacts({ listing }: { listing: Listing }) {
  const property = listing.property;
  return <div className="space-y-3">
    {property.primary_image_url && <Image unoptimized src={`/api/listings/${listing.id}/primary-image/content`} alt={property.property_label} width={600} height={400} className="max-h-64 w-full rounded-md object-cover" />}
    <h2 className="text-xl font-semibold">{property.property_label}</h2>
    <p dir="ltr">{property.property_code}</p>
    <p>{property.address_text ?? 'العنوان غير مكتمل'} · {property.location?.name_ar ?? 'الموقع غير محدد'}</p>
    <p>{property.initial_asking_price ?? 'السعر غير محدد'} {property.currency_code ?? ''}</p>
    <p>المؤسسة المالكة: {listing.organization.name}</p>
    <p>الحالة: {statusLabel(listing.status)} · المراجعة: {listing.revision}</p>
    <Alert tone="info">{{ draft: 'مسودة خاصة لم تُنشر بعد. يتحقق النظام من البيانات المطلوبة عند النشر.', published: 'الإعلان منشور ضمن نطاق المؤسسة المسموح به.', archived: 'الإعلان مؤرشف ولا يمكن إعادة نشره من هذه الصفحة.' }[listing.status]}</Alert>
  </div>;
}
export function ListingListPage() {
  const context = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context });
  const query = useQuery({ queryKey: ['listings'], queryFn: listingApi.list, retry: false });
  const client = useQueryClient();
  const [propertyId, setPropertyId] = useState<number | null>(null);
  const [success, setSuccess] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  async function create(event: React.FormEvent) {
    event.preventDefault(); setSaving(true); setError(null); setSuccess('');
    try {
      const choices = await client.fetchQuery({ queryKey: ['listing-property-choices'], queryFn: loadPropertyChoices });
      await createSelectedDraft(propertyId, choices.choices, query.data ?? []);
      setPropertyId(null); setSuccess('تم إنشاء مسودة الإعلان بنجاح.'); await client.invalidateQueries({ queryKey: ['listings'] });
    }
    catch (failure) { setError(errorText(failure)); }
    finally { setSaving(false); }
  }
  return <main dir="rtl" className="page-shell space-y-6"><h1 className="text-3xl font-semibold">الإعلانات</h1>
    {context.data?.permissions.includes('listings.manage') && <Card><CardContent className="pt-6"><form onSubmit={create} className="space-y-5"><h2 className="text-xl font-semibold">إنشاء مسودة إعلان</h2><PropertySelector selectedId={propertyId} onSelect={setPropertyId} listings={query.data ?? []} canRead={context.data.permissions.includes('properties.view')} /><Button disabled={saving || propertyId === null || query.isFetching || !!query.error}>{saving ? 'جارٍ إنشاء المسودة...' : 'إنشاء مسودة'}</Button></form></CardContent></Card>}
    {success && <Alert tone="success">{success}</Alert>}
    {error && <Alert>{error}</Alert>}
    {query.isLoading ? <p role="status">جارٍ تحميل الإعلانات...</p> : query.error ? <Alert>{errorText(query.error)}</Alert> : query.data?.length === 0 ? <Alert tone="info">لا توجد إعلانات بعد.</Alert> : query.data?.map((listing) => <Card key={listing.id}><CardContent className="space-y-4 pt-6"><ListingFacts listing={listing} /><Button asChild variant="outline"><Link href={`/admin/listings/${listing.id}`}>تفاصيل الإعلان</Link></Button></CardContent></Card>)}
  </main>;
}
export function ListingDetailPage({ id }: { id: string }) {
  const context = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context });
  const query = useQuery({ queryKey: ['listing', id], queryFn: () => listingApi.detail(id), retry: false });
  const [choice, setChoice] = useState<'publish' | 'archive'>('publish');
  const [conflict, setConflict] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState('');
  const client = useQueryClient();
  async function save() {
    if (!query.data || conflict) return;
    setSaving(true); setError(null); setSuccess('');
    try { const data = await listingApi.transition(id, choice, query.data.revision); client.setQueryData(['listing', id], data); setSuccess(choice === 'publish' ? 'تم نشر الإعلان بنجاح.' : 'تمت أرشفة الإعلان بنجاح.'); await client.invalidateQueries({ queryKey: ['listings'] }); }
    catch (failure) { if (failure instanceof ApiClientError && failure.status === 409) setConflict(true); setError(errorText(failure)); }
    finally { setSaving(false); }
  }
  async function reload() {
    const result = await query.refetch();
    if (!result.error) { setConflict(false); setError(null); }
  }
  return <main dir="rtl" className="page-shell space-y-6"><Link href="/admin/listings">العودة للإعلانات</Link><h1 className="text-3xl font-semibold">تفاصيل الإعلان</h1>
    {query.isLoading ? <p>جارٍ التحميل...</p> : query.error ? <Alert>{errorText(query.error)}</Alert> : query.data && <ListingFacts listing={query.data} />}
    {error && <Alert>{error}</Alert>}
    {success && <Alert tone="success">{success}</Alert>}
    {conflict && <Alert tone="warning"><p>تغير الإعلان. اختيارك محفوظ. أعد تحميل النسخة ثم اضغط التنفيذ للمحاولة مجددًا.</p><Button disabled={query.isFetching} onClick={reload}>تحميل النسخة الحالية</Button></Alert>}
    {query.data && query.data.status !== 'archived' && context.data?.permissions.includes('listings.manage') && <div className="flex flex-wrap items-end gap-3"><label>الإجراء<select className="rounded-md border border-line p-3" value={choice} onChange={(event) => setChoice(event.target.value as 'publish' | 'archive')}><option value="publish" disabled={query.data.status !== 'draft'}>نشر</option><option value="archive">أرشفة</option></select></label><Button disabled={saving || conflict || (choice === 'publish' && query.data.status !== 'draft')} onClick={save}>{saving ? 'جارٍ تنفيذ الإجراء...' : 'تنفيذ الإجراء'}</Button></div>}
  </main>;
}
