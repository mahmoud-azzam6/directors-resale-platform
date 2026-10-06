'use client';
import { arabicError, statusLabel } from '@/lib/ui/arabic-errors';
import Link from 'next/link';
import { useQuery } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { propertyApi } from '@/lib/api/property';
import { ApiClientError } from '@/lib/api/client';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
export function PropertyListPage() {
 const query=useQuery({queryKey:['properties'],queryFn:propertyApi.list}); const error=query.error instanceof ApiClientError?query.error:null;
 return <main className="page-shell fade-up" dir="rtl"><div className="flex flex-wrap items-end justify-between gap-4"><div><p className="eyebrow">إدارة الوحدات العقارية</p><h1 className="mt-2 text-3xl font-semibold text-ink">الوحدات العقارية</h1><p className="mt-2 text-sm text-muted">إدارة الوحدات العقارية التابعة للمؤسسة. حفظ بيانات الوحدة لا ينشئ إعلانًا.</p></div><Button asChild><Link href="/admin/properties/new"><Plus size={17} className="mr-2"/>إضافة وحدة عقارية</Link></Button></div>{query.isLoading?<p className="mt-8 text-sm text-muted">جارٍ تحميل الوحدات العقارية...</p>:query.error?<div className="mt-8"><Alert>{error?.status===403?'لا تملك صلاحية الوصول إلى الوحدات العقارية.':arabicError(error, 'تعذر تحميل الوحدات العقارية.')}</Alert></div>:query.data?.length===0?<div className="mt-8"><EmptyState title="لا توجد وحدات عقارية بعد" description="أضف وحدة عقارية للمؤسسة لبدء إعداد بياناتها."/></div>:<div className="mt-8 grid gap-4">{query.data?.map((property)=><Card key={property.id}><CardHeader><div className="flex items-center justify-between gap-4"><div><h2 className="font-semibold text-ink">{property.property_label}</h2><p className="mt-1 text-xs text-muted"><bdi>{property.property_code}</bdi> · {statusLabel(property.status)}</p></div><Button asChild variant="outline"><Link href={`/admin/properties/${property.id}/setup/property-data`}>بيانات الوحدة العقارية</Link></Button></div></CardHeader><CardContent><p className="text-sm text-muted">تابع إعداد بيانات الوحدة باستخدام القوائم المعتمدة والبيانات المحفوظة.</p></CardContent></Card>)}</div>}</main>;
}