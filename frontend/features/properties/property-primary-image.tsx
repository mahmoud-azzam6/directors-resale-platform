'use client';
import { useEffect, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ApiClientError, apiFetch } from '@/lib/api/client';
import { authApi } from '@/lib/api/auth';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
type ImagePayload = { primary_image: { reference: string; mime_type: 'image/webp'; width: number; height: number; byte_size: number } | null };
export function PropertyPrimaryImage({ id, readOnly = false }: { id: string; readOnly?: boolean }) {
  const client = useQueryClient();
  const context = useQuery({ queryKey: ['auth-context'], queryFn: authApi.context });
  const canManage = !readOnly && context.data?.permissions.includes('properties.manage');
  const path = `/api/properties/${id}/primary-image`;
  const image = useQuery({ queryKey: ['property-primary-image', id], queryFn: () => apiFetch<ImagePayload>(path), retry: false });
  const [selected, setSelected] = useState<File | null>(null);
  const input = useRef<HTMLInputElement>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [validation, setValidation] = useState('');
  const [loadError, setLoadError] = useState(false);
  const [previewVersion, setPreviewVersion] = useState(0);
  useEffect(() => { const url = selected ? URL.createObjectURL(selected) : null; setPreview(url); return () => { if (url) URL.revokeObjectURL(url); }; }, [selected]);
  const mutation = useMutation({
    mutationFn: async (remove: boolean) => {
      if (remove) return apiFetch<ImagePayload>(path, { method: 'DELETE' });
      if (!selected) throw new Error('اختر صورة أولاً.');
      const body = new FormData(); body.append('image', selected);
      const response = await fetch(path, { method: 'POST', credentials: 'include', body });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new ApiClientError(response.status, payload);
      return payload.data as ImagePayload;
    },
    retry: false,
    onSuccess: (data) => { client.setQueryData(['property-primary-image', id], data); setSelected(null); if (input.current) input.current.value = ''; setValidation(''); setLoadError(false); },
  });
  const error = mutation.error ?? image.error;
  const status = error instanceof ApiClientError ? error.status : null;
  const forbidden = status === 401 || status === 403;
  const current = image.data?.primary_image;
  return <section dir="rtl"><Card className="mt-6"><CardHeader><h2 className="font-semibold">الصورة الأساسية للوحدة</h2></CardHeader><CardContent className="space-y-4">
    {image.isPending && <p role="status">جارٍ تحميل الصورة...</p>}
    {error && <Alert>{status === 401 ? 'انتهت الجلسة. سجّل الدخول مجدداً.' : status === 403 ? 'ليس لديك صلاحية الوصول إلى الصورة.' : status === 422 ? 'الصورة غير صالحة: JPEG أو PNG أو WebP ثابتة، حتى 10 ميجابايت، بحد أدنى 600×600 وحتى 4 ملايين بكسل.' : 'تعذر إكمال طلب الصورة. يمكنك إعادة المحاولة.'}
      {!forbidden && status !== 422 && <Button onClick={() => { void image.refetch(); }}>إعادة تحميل الصورة</Button>}
    </Alert>}
    {validation && <Alert>{validation}</Alert>}
    {!forbidden && (preview || current) && <>
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img className="max-h-80 max-w-full rounded-md object-contain" alt={preview ? 'معاينة الصورة المختارة' : 'الصورة الأساسية المحفوظة'} src={preview ?? `${path}/content?v=${current?.reference}&retry=${previewVersion}`} onLoad={() => setLoadError(false)} onError={() => setLoadError(true)} />
      {loadError && <Alert>تعذر تحميل المعاينة. <Button onClick={() => { setLoadError(false); setPreviewVersion((v) => v + 1); }}>إعادة المحاولة</Button></Alert>}
    </>}
    {!image.isPending && !image.error && !current && !preview && <Alert tone="info">لا توجد صورة أساسية محفوظة. يمكن حفظ بيانات الوحدة الآن وإضافة الصورة لاحقًا. الصورة الأساسية مطلوبة عند نشر الإعلان.</Alert>}
    {canManage && <fieldset disabled={mutation.isPending || forbidden} className="space-y-3">
      <p className="text-sm text-muted">JPEG أو PNG أو WebP ثابتة، حتى 10 ميجابايت؛ الأبعاد 600×600 على الأقل وحتى 4 ملايين بكسل.</p>
      <label className="block">اختيار صورة واحدة<input ref={input} aria-label="اختيار الصورة الأساسية" type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => {
        const file = event.target.files?.[0] ?? null;
        if (file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 10485760)) { setValidation('اختر صورة JPEG أو PNG أو WebP حتى 10 ميجابايت.'); setSelected(null); return; }
        setValidation(''); setSelected(file);
      }} /></label>
      <Button disabled={!selected} onClick={() => mutation.mutate(false)}>{mutation.isPending ? 'جارٍ الحفظ...' : current ? 'استبدال الصورة الأساسية' : 'حفظ الصورة الأساسية'}</Button>
      {current && <Button variant="outline" onClick={() => mutation.mutate(true)}>إزالة الصورة الأساسية</Button>}
    </fieldset>}
    <Alert tone="info">صورة واحدة خاصة بالمؤسسة. وجود الصورة لا يعني اكتمال الوحدة أو جاهزية نشر الإعلان. معارض الصور والفيديو والمستندات الخاصة مؤجلة.</Alert>
  </CardContent></Card></section>;
}
