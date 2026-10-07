'use client';
import Image from 'next/image';
import Link from 'next/link';
import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { authApi } from '@/lib/api/auth';
import { ApiClientError } from '@/lib/api/client';
import type { Listing } from '@/lib/api/listing';
import { publishedListingApi, type InterestReceipt } from '@/lib/api/published-listing';
import { arabicError, statusLabel } from '@/lib/ui/arabic-errors';

export function requestError(error: unknown) {
  if (error instanceof ApiClientError && error.code === 'already_requested') return 'سبق إرسال طلب اهتمام لهذا الإعلان. لم يُنشأ طلب آخر.';
  if (error instanceof ApiClientError && (error.code === 'listing_unavailable' || error.status === 404)) return 'الإعلان لم يعد متاحًا. لا يمكن إرسال طلب اهتمام له.';
  return arabicError(error, 'تعذر إكمال الطلب. يرجى إعادة المحاولة.');
}

export function PublishedFacts({ listing }: { listing: Listing }) {
  return <div className="space-y-3">
    {listing.property.primary_image_url && <Image unoptimized src={`/api/published-listings/${listing.id}/primary-image/content`} alt={`الصورة الأساسية: ${listing.property.property_label}`} width={600} height={400} className="max-h-72 w-full rounded-md object-cover" />}
    <h2 className="text-xl font-semibold">{listing.property.property_label}</h2>
    <p>كود الوحدة: <bdi>{listing.property.property_code}</bdi></p>
    <p>{listing.property.address_text} · {listing.property.location?.name_ar}</p>
    <p>{listing.property.initial_asking_price} <bdi>{listing.property.currency_code}</bdi></p>
    <p>الحالة: {statusLabel(listing.status)}</p>
  </div>;
}

export function PublishedCatalogPage() {
  const query = useQuery({ queryKey: ['published-listings'], queryFn: publishedListingApi.list, retry: false });
  return <main dir="rtl" className="page-shell space-y-6"><h1 className="text-3xl font-semibold">الإعلانات المتاحة</h1><Alert tone="info">الإعلانات المنشورة والمتاحة ضمن نطاق صلاحياتك. طلب الاهتمام لا يحجز الوحدة ولا ينشئ صفقة.</Alert>
    {query.isPending ? <p role="status">جارٍ تحميل الإعلانات المتاحة...</p> : query.error ? <Alert>{requestError(query.error)}</Alert> : query.data?.length === 0 ? <Alert tone="info">لا توجد إعلانات متاحة حاليًا.</Alert> : <div className="grid gap-5 md:grid-cols-2">{query.data?.map((listing) => <Card key={listing.id}><CardContent className="space-y-5 pt-6"><PublishedFacts listing={listing} /><Button asChild variant="outline"><Link href={`/admin/available-listings/${listing.id}`}>عرض التفاصيل</Link></Button></CardContent></Card>)}</div>}
  </main>;
}

export function InterestResult({ receipt }: { receipt: InterestReceipt }) {
  return <Alert tone="success"><strong>تم إرسال طلب الاهتمام بنجاح.</strong><p>مرجع الطلب: <bdi>{receipt.reference}</bdi></p><p>هذا الطلب تعبير عن الاهتمام فقط؛ لا ينشئ حجزًا أو صفقة أو عقدًا أو عمولة.</p></Alert>;
}

export function RequestConfirmation({ saving, unavailable, onConfirm, onCancel }: { saving: boolean; unavailable: boolean; onConfirm: () => void; onCancel: () => void }) {
  return <Alert tone="warning"><p>هل تؤكد إرسال طلب اهتمام لهذه الوحدة العقارية؟</p><div className="mt-3 flex flex-wrap gap-3"><Button disabled={saving || unavailable} onClick={onConfirm}>{saving ? 'جارٍ إرسال الطلب...' : 'تأكيد الإرسال'}</Button><Button variant="outline" disabled={saving} onClick={onCancel}>إلغاء</Button></div></Alert>;
}

export function PublishedDetailPage({ id }: { id: string }) {
  const client = useQueryClient();
  const context = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context });
  const query = useQuery({ queryKey: ['published-listing', id], queryFn: () => publishedListingApi.detail(id), retry: false });
  const canReadOwn = context.data?.permissions.includes('requests.view') ?? false;
  const own = useQuery({ queryKey: ['own-listing-requests'], queryFn: publishedListingApi.own, enabled: canReadOwn, retry: false });
  const [confirming, setConfirming] = useState(false);
  const [saving, setSaving] = useState(false);
  const [receipt, setReceipt] = useState<InterestReceipt | null>(null);
  const [error, setError] = useState<unknown>(null);
  const alreadyRequested = Boolean(own.data?.some((request) => request.listing_id === Number(id))) || (error instanceof ApiClientError && error.code === 'already_requested');
  const unavailable = error instanceof ApiClientError && (error.code === 'listing_unavailable' || error.status === 404);
  async function submit() {
    if (!query.data || saving || !confirming || alreadyRequested || unavailable) return;
    setSaving(true); setError(null);
    try { setReceipt(await publishedListingApi.submit(id)); setConfirming(false); await client.invalidateQueries({ queryKey: ['own-listing-requests'] }); }
    catch (failure) { setError(failure); }
    finally { setSaving(false); }
  }
  return <main dir="rtl" className="page-shell space-y-6"><Link href="/admin/available-listings">العودة إلى الإعلانات المتاحة</Link><h1 className="text-3xl font-semibold">تفاصيل الإعلان</h1>
    {query.isPending ? <p role="status">جارٍ تحميل تفاصيل الإعلان...</p> : query.error ? <Alert>{requestError(query.error)}</Alert> : query.data && <Card><CardContent className="space-y-5 pt-6"><PublishedFacts listing={query.data} />
      {query.data.status !== 'published' ? <Alert tone="warning">الإعلان لم يعد متاحًا. لا يمكن إرسال طلب اهتمام له.</Alert> : receipt ? <InterestResult receipt={receipt} /> : alreadyRequested ? <Alert tone="info">سبق إرسال طلب اهتمام لهذا الإعلان.</Alert> : context.data?.permissions.includes('requests.create') && context.data.permissions.includes('published_listings.view') ? <div className="space-y-4">
        <Alert tone="info">طلب الاهتمام لا يحجز الوحدة ولا يضمن إتمام البيع. تُراجع إتاحة الإعلان عند الإرسال.</Alert>
        {confirming ? <RequestConfirmation saving={saving} unavailable={unavailable} onConfirm={submit} onCancel={() => setConfirming(false)} /> : <Button disabled={unavailable} onClick={() => setConfirming(true)}>إرسال طلب اهتمام</Button>}
      </div> : <Alert tone="info">لا تملك صلاحية إرسال طلب اهتمام.</Alert>}
    </CardContent></Card>}
    {error != null && <Alert>{requestError(error)}</Alert>}
    {canReadOwn && <Button asChild variant="outline"><Link href="/admin/my-requests">عرض طلبات الاهتمام الخاصة بي</Link></Button>}
  </main>;
}

export function MyInterestRequestsPage() {
  const query = useQuery({ queryKey: ['own-listing-requests'], queryFn: publishedListingApi.own, retry: false });
  return <main dir="rtl" className="page-shell space-y-6"><h1 className="text-3xl font-semibold">طلبات الاهتمام الخاصة بي</h1>
    {query.isPending ? <p role="status">جارٍ تحميل الطلبات...</p> : query.error ? <Alert>{requestError(query.error)}</Alert> : query.data?.length === 0 ? <Alert tone="info">لم ترسل أي طلب اهتمام بعد.</Alert> : query.data?.map((receipt) => <Card key={receipt.id}><CardContent className="space-y-3 pt-6"><h2 className="font-semibold">{receipt.property.property_label}</h2><p>كود الوحدة: <bdi>{receipt.property.property_code}</bdi></p><p>مرجع الطلب: <bdi>{receipt.reference}</bdi></p><p>الحالة: تم الإرسال</p><p>تاريخ الإرسال: {receipt.created_at}</p><Button asChild variant="outline"><Link href={`/admin/available-listings/${receipt.listing_id}`}>عرض تفاصيل الإعلان إن كان متاحًا</Link></Button></CardContent></Card>)}
  </main>;
}
