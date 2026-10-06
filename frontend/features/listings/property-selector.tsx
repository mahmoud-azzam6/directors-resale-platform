'use client';
import Image from 'next/image';
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '@/lib/api/client';
import { propertyApi } from '@/lib/api/property';
import { listingApi, type Listing } from '@/lib/api/listing';
import { arabicError } from '@/lib/ui/arabic-errors';
import type { ProfileAggregate } from '@/types/property';
import { Alert } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type PropertyChoice = ProfileAggregate & { hasImage: boolean };

// Existing protected endpoints decide scope. No hierarchy is inferred in the client.
export async function loadPropertyChoices() {
  const properties = await propertyApi.list();
  const active = properties.filter((property) => property.status === 'active' && /^PROP-[0-9A-HJKMNP-TV-Z]{26}$/.test(property.property_code) && Number.isSafeInteger(property.id) && property.id > 0);
  const results = await Promise.allSettled(active.map(async (property) => {
    const [aggregate, image] = await Promise.all([
      propertyApi.profile(String(property.id)),
      apiFetch<{ primary_image: unknown | null }>(`/api/properties/${property.id}/primary-image`),
    ]);
    if (aggregate.property.id !== property.id || aggregate.property.organization_id !== property.organization_id || aggregate.property.status !== 'active' || aggregate.property.property_code !== property.property_code) throw new Error('Unavailable');
    return { ...aggregate, hasImage: image.primary_image !== null };
  }));
  return { choices: results.flatMap((result) => result.status === 'fulfilled' ? [result.value] : []), unavailable: results.filter((result) => result.status === 'rejected').length };
}

export function availableChoices(choices: PropertyChoice[], listings: Listing[], search: string) {
  const term = search.trim().toLocaleLowerCase('ar');
  return choices.filter(({ property, profile, geography }) => !listings.some((listing) => listing.organization_property_id === property.id && listing.status !== 'archived')
    && [property.property_label, property.property_code, profile?.address_text, geography?.location?.name_ar, geography?.location?.name_en].some((value) => value?.toLocaleLowerCase('ar').includes(term)));
}

export async function createSelectedDraft(id: number | null, choices: PropertyChoice[], listings: Listing[]) {
  const selected = availableChoices(choices, listings, '').find((choice) => choice.property.id === id);
  if (!selected) throw new Error('اختر وحدة عقارية متاحة أولاً.');
  return listingApi.create(selected.property.id);
}

export function PropertySelector({ selectedId, onSelect, listings, canRead }: { selectedId: number | null; onSelect: (id: number | null) => void; listings: Listing[]; canRead: boolean }) {
  const [search, setSearch] = useState('');
  const query = useQuery({ queryKey: ['listing-property-choices'], queryFn: loadPropertyChoices, enabled: canRead, retry: false });
  if (!canRead) return <Alert tone="info">اختيار الوحدة يتطلب صلاحية عرض الوحدات العقارية. تظل صلاحيات إنشاء الإعلان مستقلة.</Alert>;
  if (query.isPending) return <p role="status">جارٍ تحميل الوحدات المتاحة...</p>;
  if (query.error) return <Alert>{arabicError(query.error, 'تعذر تحميل الوحدات المتاحة.')}</Alert>;
  const choices = availableChoices(query.data?.choices ?? [], listings, search);
  return <div className="space-y-4">
    <div><Label htmlFor="listing-property-search">ابحث عن الوحدة بالكود أو الاسم أو العنوان</Label><Input id="listing-property-search" type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="كود الوحدة أو اسمها أو عنوانها" /></div>
    {!!query.data?.unavailable && <Alert tone="info">بعض الوحدات غير متاحة للعرض حاليًا، لذلك لا يمكن اختيارها.</Alert>}
    {choices.length === 0 ? <Alert tone="info">{search ? 'لا توجد وحدات متاحة تطابق البحث.' : 'لا توجد وحدات متاحة يمكن إنشاء إعلان لها.'}</Alert> : <fieldset className="grid max-h-96 gap-3 overflow-y-auto sm:grid-cols-2"><legend className="sr-only">اختر الوحدة العقارية</legend>{choices.map((choice) => <label key={choice.property.id} className={`flex cursor-pointer items-start gap-3 rounded-md border p-4 ${selectedId === choice.property.id ? 'border-brand bg-canvas' : 'border-line bg-surface'}`}>
      <input type="radio" name="listing-property" checked={selectedId === choice.property.id} onChange={() => onSelect(choice.property.id)} className="mt-1 shrink-0" />
      {choice.hasImage && <Image unoptimized src={`/api/properties/${choice.property.id}/primary-image/content`} alt={`الصورة الأساسية: ${choice.property.property_label}`} width={64} height={64} className="h-16 w-16 shrink-0 rounded-md object-cover" />}
      <span className="min-w-0 space-y-1 break-words"><span className="block font-semibold">{choice.property.property_label}</span><bdi className="block text-xs">{choice.property.property_code}</bdi><span className="block text-sm">{choice.profile?.address_text || 'العنوان غير محفوظ'} · {choice.geography?.location?.name_ar || 'الموقع غير محفوظ'}</span><span className="block text-sm">{choice.profile?.initial_asking_price ?? 'السعر غير محفوظ'} {choice.profile?.currency_code ?? ''}</span></span>
    </label>)}</fieldset>}
    <Alert tone="info">يمكن إنشاء مسودة ببيانات غير مكتملة. يتحقق النظام من متطلبات النشر عند طلب نشر الإعلان.</Alert>
  </div>;
}
