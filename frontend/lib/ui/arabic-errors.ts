import { ApiClientError } from '@/lib/api/client';

const fields: Record<string, string> = {
  property_code: 'كود الوحدة غير صالح. يلزم كود محفوظ أنشأه النظام لتحديد الوحدة.',
  address: 'العنوان التفصيلي والموقع الجغرافي الصحيح مطلوبان قبل نشر الإعلان لتحديد مكان الوحدة.',
  price: 'السعر الموجب مطلوب قبل نشر الإعلان لتوضيح القيمة المطلوبة.',
  currency: 'العملة الصحيحة مطلوبة قبل نشر الإعلان لتوضيح السعر.',
  image: 'يجب إضافة صورة أساسية واحدة صالحة قبل نشر الإعلان لعرض الوحدة.',
  ownership: 'بيانات الملكية الحالية غير مكتملة. يلزم مالك نشط ضمن الملكية الحالية قبل النشر.',
  organization_property_id: 'اختر وحدة عقارية متاحة ضمن نطاق صلاحياتك.',
  property: 'الوحدة العقارية غير متاحة لهذا الإجراء.',
  status: 'حالة الإعلان الحالية لا تسمح بهذا الإجراء.',
  revision: 'تغيرت النسخة المحفوظة. حمّل النسخة الحالية ثم أعد المحاولة صراحةً.',
  listing: 'توجد مسودة أو إعلان غير مؤرشف لهذه الوحدة بالفعل.',
  property_label: 'اسم الوحدة العقارية مطلوب.',
  address_text: 'تحقق من العنوان التفصيلي للوحدة.',
  geographic_location_id: 'اختر موقعًا جغرافيًا صحيحًا ومتاحًا.',
  initial_asking_price: 'أدخل سعرًا رقميًا موجبًا.',
  currency_code: 'اختر عملة مدعومة للسعر.',
};

export function arabicError(error: unknown, fallback = 'تعذر إكمال الطلب. يرجى إعادة المحاولة.'): string {
  if (!(error instanceof ApiClientError)) return fallback;
  if (error.status === 401) return 'انتهت الجلسة. يرجى تسجيل الدخول مجددًا.';
  if (error.status === 403) return 'لا تملك صلاحية الوصول إلى هذه البيانات أو تنفيذ هذا الإجراء.';
  if (error.status === 404) return 'البيانات المطلوبة غير متاحة أو لم تعد موجودة.';
  if (error.status === 409) return fields.revision;
  if (error.fields && Object.keys(error.fields).length) {
    return Array.from(new Set(Object.keys(error.fields).map((key) => fields[key] ?? 'تحقق من البيانات المدخلة ثم أعد المحاولة.'))).join(' ');
  }
  return fallback;
}

export const statusLabel = (status: string) => ({ active: 'نشط', inactive: 'غير نشط', draft: 'مسودة', published: 'منشور', archived: 'مؤرشف', closed: 'مغلقة', current: 'حالية' }[status] ?? status);
