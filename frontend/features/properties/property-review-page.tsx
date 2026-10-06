'use client';
import { arabicError, statusLabel } from '@/lib/ui/arabic-errors';

import Link from 'next/link';
import { useQuery } from '@tanstack/react-query';
import { ApiClientError } from '@/lib/api/client';
import { ownershipApi } from '@/lib/api/ownership';
import { propertyApi } from '@/lib/api/property';
import type { CatalogItem, ProfileAggregate } from '@/types/property';
import type { OwnershipParty } from '@/types/ownership';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { PropertyPrimaryImage } from './property-primary-image';

const ar = {
  eyebrow: '\u0645\u0631\u0627\u062c\u0639\u0629 \u0625\u0639\u062f\u0627\u062f \u0627\u0644\u0648\u062d\u062f\u0629',
  title: '\u0627\u0644\u0645\u0631\u0627\u062c\u0639\u0629',
  property: '\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0648\u062d\u062f\u0629',
  ownership: '\u0627\u0644\u0645\u0627\u0644\u0643 \u0648\u0627\u0644\u0645\u0644\u0643\u064a\u0629',
  propertyData: '\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0648\u062d\u062f\u0629',
  propertyLabel: '\u0627\u0633\u0645 \u0627\u0644\u0648\u062d\u062f\u0629',
  propertyId: '\u0645\u0639\u0631\u0641 \u0627\u0644\u0648\u062d\u062f\u0629',
  organization: '\u0627\u0644\u0645\u0646\u0638\u0645\u0629',
  lifecycle: '\u062d\u0627\u0644\u0629 \u0627\u0644\u0648\u062d\u062f\u0629',
  category: '\u0641\u0626\u0629 \u0627\u0644\u0648\u062d\u062f\u0629',
  unitType: '\u0646\u0648\u0639 \u0627\u0644\u0648\u062d\u062f\u0629',
  configuration: '\u0627\u0644\u062a\u0643\u0648\u064a\u0646 \u0627\u0644\u0645\u062b\u0628\u062a',
  revision: '\u0645\u0631\u0627\u062c\u0639\u0629 \u0627\u0644\u0645\u0644\u0641',
  geography: '\u0627\u0644\u0645\u0648\u0642\u0639 \u0627\u0644\u062c\u063a\u0631\u0627\u0641\u064a',
  development: '\u0645\u0631\u062c\u0639 \u0627\u0644\u062a\u0637\u0648\u064a\u0631',
  measurements: '\u0627\u0644\u0642\u064a\u0627\u0633\u0627\u062a',
  attributes: '\u0627\u0644\u0633\u0645\u0627\u062a',
  currentOwnership: '\u0627\u0644\u0645\u0644\u0643\u064a\u0629 \u0627\u0644\u062d\u0627\u0644\u064a\u0629',
  actingOwner: '\u0627\u0644\u0645\u0627\u0644\u0643 \u0627\u0644\u0645\u062a\u0635\u0631\u0641',
  parties: '\u0623\u0637\u0631\u0627\u0641 \u0627\u0644\u0645\u0644\u0643\u064a\u0629',
  history: '\u0633\u062c\u0644 \u0627\u0644\u0645\u0644\u0643\u064a\u0629',
  mediaBoundary: '\u0627\u0644\u0635\u0648\u0631 \u0648\u0627\u0644\u0645\u0633\u062a\u0646\u062f\u0627\u062a',
  images: '\u0635\u0648\u0631 \u0627\u0644\u0648\u062d\u062f\u0629',
  documents: '\u0645\u0633\u062a\u0646\u062f\u0627\u062a \u062e\u0627\u0635\u0629',
};

const label = (item: CatalogItem | null | undefined) => item ? (item.name_ar || item.name_en || item.code) : null;
const unavailable = '\u063a\u064a\u0631 \u0645\u062a\u0627\u062d \u0641\u064a \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0645\u062d\u0641\u0648\u0638\u0629';

function attributeValue(attribute: ProfileAggregate['attributes'][number]) {
  if (attribute.data_type === 'INTEGER') return attribute.value_integer ?? unavailable;
  if (attribute.data_type === 'DECIMAL') return attribute.value_decimal ?? unavailable;
  if (attribute.data_type === 'BOOLEAN') return attribute.value_boolean === null || attribute.value_boolean === undefined ? unavailable : attribute.value_boolean ? '\u0646\u0639\u0645' : '\u0644\u0627';
  if (attribute.data_type === 'TEXT') return attribute.value_text || unavailable;
  if (attribute.data_type === 'DATE') return attribute.value_date || unavailable;
  if (attribute.data_type === 'ENUM') return attribute.attribute_option_id ? `الخيار رقم ${attribute.attribute_option_id}` : unavailable;
  return unavailable;
}

export function PropertyReviewPage({ id }: { id: string }) {
  const profile = useQuery({ queryKey: ['property-profile', id], queryFn: () => propertyApi.profile(id) });
  const owners = useQuery({ queryKey: ['owners'], queryFn: ownershipApi.owners });
  const current = useQuery({ queryKey: ['property-ownership-current', id], queryFn: () => ownershipApi.currentOwnership(id), retry: false });
  const history = useQuery({ queryKey: ['property-ownership-history', id], queryFn: () => ownershipApi.ownershipHistory(id) });
  const ownershipId = current.data?.id;
  const parties = useQuery({ queryKey: ['ownership-parties', ownershipId], queryFn: () => ownershipApi.parties(ownershipId!), enabled: Boolean(ownershipId) });
  const actingOwner = useQuery({ queryKey: ['acting-owner', ownershipId], queryFn: () => ownershipApi.actingOwner(ownershipId!), enabled: Boolean(ownershipId), retry: false });

  const errors = [profile.error, owners.error, history.error, current.error, parties.error, actingOwner.error];
  const accessError = errors.find((error) => error instanceof ApiClientError && error.status !== 404) as ApiClientError | undefined;
  const currentMissing = current.error instanceof ApiClientError && current.error.status === 404;
  const actingMissing = actingOwner.error instanceof ApiClientError && actingOwner.error.status === 404;

  if (profile.isLoading || owners.isLoading || history.isLoading || current.isLoading) {
    return <main className="page-shell" dir="rtl"><p className="text-sm text-muted">جارٍ تحميل مراجعة إعداد الوحدة العقارية...</p></main>;
  }

  if (accessError) {
    return <main className="page-shell" dir="rtl"><Alert>{accessError.status === 403 ? 'لا تملك صلاحية مراجعة بيانات هذه الوحدة العقارية الخاصة.' : arabicError(accessError)}</Alert></main>;
  }

  const aggregate = profile.data!;
  const property = aggregate.property;
  const currentParties = parties.data ?? [];
  const ownerFor = (party: OwnershipParty) => owners.data?.find((owner) => owner.id === party.owner_id);
  const actingParty = currentParties.find((party) => party.id === actingOwner.data?.ownership_party_id);
  const geography = aggregate.geography?.ancestry?.locations.slice().reverse().map(label).filter(Boolean).join(' / ') || label(aggregate.geography?.location) || unavailable;
  const development = label(aggregate.development?.phase) || label(aggregate.development?.project) || label(aggregate.development?.developer) || unavailable;
  const configuration = aggregate.configuration ? `${aggregate.configuration.code}${aggregate.configuration.version_number ?? aggregate.configuration.version ? `  -  V${aggregate.configuration.version_number ?? aggregate.configuration.version}` : ''}` : aggregate.profile?.accepted_configuration_version_id ? `التكوين رقم ${aggregate.profile.accepted_configuration_version_id}` : unavailable;

  return (
    <main className="page-shell fade-up" dir="rtl">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="eyebrow">{ar.eyebrow}  -  3 {"\u0645\u0646"} 3</p>
          <h1 className="mt-2 text-3xl font-semibold text-ink">{ar.title}</h1>
          <p className="mt-2 text-sm text-muted">مراجعة إدارية لبيانات الوحدة العقارية والملكية المحفوظة. هذه الصفحة لا تنشئ إعلانًا ولا تنشره.</p>
        </div>
        <div className="flex flex-wrap gap-3"><Button asChild variant="outline"><Link href={`/admin/properties/${id}/setup/property-data`}>{ar.propertyData}</Link></Button><Button asChild variant="outline"><Link href={`/admin/properties/${id}/setup/ownership`}>{ar.ownership}</Link></Button></div>
      </div>

      <Alert tone="info"><div><strong>مراجعة إعداد الوحدة لا تعني جاهزية نشر الإعلان.</strong><p className="mt-1">هذه الصفحة للعرض فقط. لا تحسب اكتمال البيانات ولا تنشئ إعلانًا أو صورًا أو مستندات خاصة.</p></div></Alert>

      <Card className="mt-6">
        <CardHeader><h2 className="font-semibold text-ink">{ar.property}</h2></CardHeader>
        <CardContent><dl className="grid gap-5 sm:grid-cols-2"><Summary label={ar.propertyLabel} value={property.property_label} /><Summary label="كود الوحدة" value={property.property_code || unavailable} /><Summary label={ar.propertyId} value={`#${property.id}`} /><Summary label={ar.organization} value={property.organization_id ? `المؤسسة رقم ${property.organization_id}` : unavailable} /><Summary label={ar.lifecycle} value={statusLabel(property.status)} /></dl>{property.status === 'archived' && <p className="mt-5 text-sm text-muted">هذه الوحدة العقارية مؤرشفة وتُعرض للقراءة فقط وفق حالتها المحفوظة.</p>}</CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader><h2 className="font-semibold text-ink">{ar.propertyData}</h2></CardHeader>
        <CardContent>{aggregate.profile === null ? <EmptyState title="لا توجد بيانات تفصيلية محفوظة للوحدة" description="الوحدة متاحة لك، لكن بياناتها التفصيلية لم تُحفظ بعد." /> : <><dl className="grid gap-5 sm:grid-cols-2"><Summary label={ar.category} value={label(aggregate.category) || unavailable} /><Summary label={ar.unitType} value={label(aggregate.unit_type) || unavailable} /><Summary label={ar.configuration} value={configuration} /><Summary label={ar.revision} value={String(aggregate.profile.revision)} /><Summary label={ar.geography} value={geography} /><Summary label={ar.development} value={development} /><Summary label="الشارع والعنوان" value={aggregate.profile.address_text || unavailable} /><Summary label="سعر الطلب الابتدائي" value={aggregate.profile.initial_asking_price ? `${aggregate.profile.initial_asking_price} ${aggregate.profile.currency_code}` : unavailable} /></dl><ValueSection title={ar.measurements} empty="لا توجد قياسات محفوظة." values={aggregate.measurements.map((measurement) => [`التعريف رقم ${measurement.measurement_definition_id}`, `${measurement.value_decimal} ${measurement.unit_code}`])} /><ValueSection title={ar.attributes} empty="لا توجد سمات محفوظة." values={aggregate.attributes.map((attribute) => [`التعريف رقم ${attribute.attribute_definition_id} (${attribute.data_type})`, String(attributeValue(attribute))])} /></>}</CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader><h2 className="font-semibold text-ink">{ar.ownership}</h2></CardHeader>
        <CardContent>{currentMissing ? <EmptyState title="لا توجد ملكية حالية" description="لا توجد ملكية حالية محفوظة لهذه الوحدة العقارية." /> : current.data ? <><dl className="grid gap-5 sm:grid-cols-2"><Summary label={ar.currentOwnership} value={`#${current.data.id}  -  ${statusLabel(current.data.status)}`} /><Summary label={ar.actingOwner} value={actingMissing ? 'لا يوجد مالك متصرف حالي.' : actingParty ? ownerFor(actingParty)?.display_name || `المالك رقم ${actingParty.owner_id}` : unavailable} /></dl><ValueSection title={ar.parties} empty="لا توجد أطراف ملكية متاحة." values={currentParties.map((party) => [ownerFor(party)?.display_name || `المالك رقم ${party.owner_id}`, `${party.share_percentage}%`])} /></> : <Alert>{arabicError(current.error, 'تعذر تحميل الملكية الحالية.')}</Alert>}<ValueSection title={ar.history} empty="لا يوجد سجل ملكية متاح." values={history.data?.map((ownership) => [`الملكية رقم ${ownership.id}`, ownership.closed_at ? `مغلقة - ${ownership.closed_at}` : statusLabel(ownership.status)]) ?? []} /></CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader><h2 className="font-semibold text-ink">{ar.mediaBoundary}</h2></CardHeader>
        <CardContent>
          <Alert tone="info">تتوفر صورة أساسية واحدة خاصة بالمؤسسة أدناه. معارض الصور والفيديو والمستندات الخاصة مؤجلة وليست أخطاء في إعداد الوحدة.</Alert>
          <div className="mt-5 grid gap-4 md:grid-cols-2">
            <section className="rounded-md border border-dashed border-line bg-surface-muted/40 p-5">
              <h3 className="font-semibold text-ink">{ar.images}</h3>
              <p className="mt-2 text-sm text-muted">يمكن رفع الصورة الأساسية واستبدالها وإزالتها من صفحة بيانات الوحدة. لا يوجد معرض صور.</p>
            </section>
            <section className="rounded-md border border-dashed border-line bg-surface-muted/40 p-5">
              <h3 className="font-semibold text-ink">{ar.documents}</h3>
              <p className="mt-2 text-sm text-muted">رفع المستندات الخاصة مؤجل وغير متاح حاليًا.</p>
            </section>
          </div>
          <p className="mt-5 text-sm text-muted">الميزات المؤجلة لا تمنع حفظ بيانات الوحدة. يتحقق النظام من متطلبات نشر الإعلان عند طلب النشر.</p>
        </CardContent>
      </Card>
      <PropertyPrimaryImage id={id} readOnly />
    </main>
  );
}

function Summary({ label, value }: { label: string; value: string }) {
  return <div><dt className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</dt><dd className="mt-1 text-sm font-semibold text-ink">{value}</dd></div>;
}

function ValueSection({ title, empty, values }: { title: string; empty: string; values: Array<[string, string]> }) {
  return <section className="mt-8 border-t border-line pt-6"><h3 className="font-semibold text-ink">{title}</h3>{values.length === 0 ? <div className="mt-3"><Alert tone="info">{empty}</Alert></div> : <div className="mt-4 space-y-2">{values.map(([name, value]) => <div key={`${name}-${value}`} className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-line p-3 text-sm"><span className="font-semibold text-ink">{name}</span><span className="text-muted">{value}</span></div>)}</div>}</section>;
}
