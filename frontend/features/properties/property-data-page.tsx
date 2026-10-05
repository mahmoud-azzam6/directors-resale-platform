'use client';

import Link from 'next/link';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ApiClientError } from '@/lib/api/client';
import { propertyApi } from '@/lib/api/property';
import type { ProfileAggregate, SaveProfileInput } from '@/types/property';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { GeographySelector } from '@/features/network/geography-selector';
import { PropertyPrimaryImage } from './property-primary-image';

type LocalValueState = {
  measurements: Record<number, string>;
  attributes: Record<number, string>;
};

const value = (event: React.ChangeEvent<HTMLSelectElement>) => (
  event.target.value === '' ? null : Number(event.target.value)
);

const title = (item: { name_en: string; name_ar: string }) => item.name_ar || item.name_en;

function hydrateValues(aggregate: ProfileAggregate): LocalValueState {
  const measurements: Record<number, string> = {};
  const attributes: Record<number, string> = {};

  aggregate.measurements.forEach((measurement) => {
    measurements[measurement.measurement_definition_id] = measurement.value_decimal;
  });

  aggregate.attributes.forEach((attribute) => {
    const definitionId = attribute.attribute_definition_id;

    switch (attribute.data_type) {
      case 'INTEGER':
        if (attribute.value_integer !== null && attribute.value_integer !== undefined) {
          attributes[definitionId] = String(attribute.value_integer);
        }
        break;
      case 'DECIMAL':
        if (attribute.value_decimal !== null && attribute.value_decimal !== undefined) {
          attributes[definitionId] = attribute.value_decimal;
        }
        break;
      case 'BOOLEAN':
        if (attribute.value_boolean !== null && attribute.value_boolean !== undefined) {
          attributes[definitionId] = attribute.value_boolean ? 'true' : 'false';
        }
        break;
      case 'TEXT':
        if (attribute.value_text !== null && attribute.value_text !== undefined) {
          attributes[definitionId] = attribute.value_text;
        }
        break;
      case 'DATE':
        if (attribute.value_date !== null && attribute.value_date !== undefined) {
          attributes[definitionId] = attribute.value_date;
        }
        break;
      case 'ENUM':
        if (attribute.attribute_option_id !== null && attribute.attribute_option_id !== undefined) {
          attributes[definitionId] = String(attribute.attribute_option_id);
        }
        break;
    }
  });

  return { measurements, attributes };
}

export function PropertyDataPage({ id }: { id: string }) {
  const queryClient = useQueryClient();
  const [category, setCategory] = useState<number | null>(null);
  const [unitType, setUnitType] = useState<number | null>(null);
  const [geography, setGeography] = useState<number | null>(null);
  const [geographyHydration, setGeographyHydration] = useState<number | null>(null);
  const [selectorVersion, setSelectorVersion] = useState(0);
  const [address, setAddress] = useState('');
  const [askingPrice, setAskingPrice] = useState('');
  const [currency, setCurrency] = useState('EGP');
  const [dirty, setDirty] = useState(false);
  const [conflictReadError, setConflictReadError] = useState(false);
  const [developer, setDeveloper] = useState<number | null>(null);
  const [measurementValues, setMeasurementValues] = useState<Record<number, string>>({});
  const [attributeValues, setAttributeValues] = useState<Record<number, string>>({});
  const [measurementsDirty, setMeasurementsDirty] = useState(false);
  const [attributesDirty, setAttributesDirty] = useState(false);
  const [conflict, setConflict] = useState(false);
  const [latestServerAggregate, setLatestServerAggregate] = useState<ProfileAggregate | null>(null);

  const core = useQuery({ queryKey: ['property-core-form'], queryFn: propertyApi.coreForm });
  const profile = useQuery({ queryKey: ['property-profile', id], queryFn: () => propertyApi.profile(id), refetchOnWindowFocus: false, retry: false });
  const projection = useQuery({
    queryKey: ['property-form', unitType],
    queryFn: () => propertyApi.unitTypeForm(unitType!),
    enabled: unitType !== null,
  });

  const hydrateAggregate = useCallback((aggregate: ProfileAggregate) => {
    const savedProfile = aggregate.profile;
    const values = hydrateValues(aggregate);

    setCategory(savedProfile?.property_category_id ?? null);
    setUnitType(savedProfile?.unit_type_id ?? null);
    setGeography(savedProfile?.geographic_location_id ?? null);
    setGeographyHydration(savedProfile?.geographic_location_id ?? null);
    setSelectorVersion((version) => version + 1);
    setAddress(savedProfile?.address_text ?? '');
    setAskingPrice(savedProfile?.initial_asking_price ?? '');
    setCurrency(savedProfile?.currency_code ?? 'EGP');
    setDeveloper(savedProfile?.developer_id ?? null);
    setMeasurementValues(values.measurements);
    setAttributeValues(values.attributes);
    setMeasurementsDirty(false);
    setAttributesDirty(false);
  }, []);

  useEffect(() => {
    if (profile.data && !conflict && !dirty) {
      hydrateAggregate(profile.data);
    }
  }, [conflict, dirty, hydrateAggregate, profile.data]);

  const unitTypes = useMemo(
    () => core.data?.unit_types.filter((item) => item.category_id === category) ?? [],
    [core.data, category],
  );

  const save = useMutation({
    mutationFn: (payload: SaveProfileInput) => propertyApi.saveProfile(id, payload),
    onSuccess: (aggregate) => {
      setConflict(false);
      setLatestServerAggregate(null);
      setConflictReadError(false);
      setDirty(false);
      queryClient.setQueryData(['property-profile', id], aggregate);
      hydrateAggregate(aggregate);
    },
    onError: async (error) => {
      if (
        error instanceof ApiClientError
        && error.code === 'validation_error'
        && error.fields?.PROFILE_REVISION_CONFLICT
      ) {
        setConflict(true);
        setLatestServerAggregate(null);
        try {
          const currentAggregate = await queryClient.fetchQuery({ queryKey: ['property-profile', id], queryFn: () => propertyApi.profile(id) });
          setLatestServerAggregate(currentAggregate);
          setConflictReadError(false);
        } catch { setConflictReadError(true); }
      }
    },
  });

  if (core.isLoading || profile.isLoading) {
    return <main className="page-shell"><p className="text-sm text-muted">Loading Property Data...</p></main>;
  }

  if ((core.error && !core.data) || (profile.error && !profile.data)) {
    const error = (core.error ?? profile.error) as ApiClientError;
    return <main className="page-shell" dir="rtl"><Alert>{error?.status === 401 ? 'انتهت الجلسة. سجّل الدخول مجدداً.' : error?.status === 403 ? 'ليس لديك صلاحية عرض بيانات هذه الوحدة.' : error?.message ?? 'تعذر تحميل بيانات الوحدة.'}
      {![401, 403].includes(error?.status) && <Button onClick={() => { void core.refetch(); void profile.refetch(); }}>إعادة المحاولة</Button>}
    </Alert></main>;
  }

  const submit = () => {
    const payload: SaveProfileInput = {
      property_category_id: category,
      unit_type_id: unitType,
      geographic_location_id: geography,
      address_text: address.trim() || null,
      initial_asking_price: askingPrice.trim() || null,
      currency_code: askingPrice.trim() ? currency : null,
    };

    if (developer !== null) {
      payload.development_reference_type = 'DEVELOPER';
      payload.developer_id = developer;
    }

    // BF015 patch semantics preserve collections that this UI has not edited.
    if (measurementsDirty) {
      payload.measurements = [];
      projection.data?.measurements.forEach((field) => {
        const raw = measurementValues[field.definition_id];
        if (raw) {
          payload.measurements?.push({ definition_id: field.definition_id, value: raw, unit_code: field.unit });
        }
      });
    }

    if (attributesDirty) {
      payload.attributes = [];
      projection.data?.attributes.forEach((field) => {
        const raw = attributeValues[field.definition_id];
        if (raw === undefined || raw === '') return;

        if (field.data_type === 'ENUM') {
          payload.attributes?.push({ definition_id: field.definition_id, option_id: Number(raw) });
        } else if (field.data_type === 'BOOLEAN') {
          payload.attributes?.push({ definition_id: field.definition_id, value: raw === 'true' });
        } else if (field.data_type === 'INTEGER') {
          payload.attributes?.push({ definition_id: field.definition_id, value: Number(raw) });
        } else {
          payload.attributes?.push({ definition_id: field.definition_id, value: raw });
        }
      });
    }

    if (profile.data?.profile) {
      payload.expected_revision = conflict ? latestServerAggregate?.profile?.revision : profile.data.profile.revision;
    }

    save.mutate(payload);
  };

  const apiError = save.error instanceof ApiClientError ? save.error : null;

  return (
    <main className="page-shell fade-up" dir="rtl" onChange={() => setDirty(true)}>
      <div>
        <p className="eyebrow">Property setup · 1 of 3</p>
        <h1 className="mt-2 text-3xl font-semibold text-ink">Property Data / بيانات الوحدة</h1>
        <p className="mt-2 text-sm text-muted">Save progressively. Requiredness and Listing readiness are not calculated here.</p>
      </div>

      {conflict && (
        <div className="mt-6">
          <Alert>
            <div>
              <strong>This Profile changed elsewhere.</strong>
              <p className="mt-1">Your input is still on this page. The latest server revision is {latestServerAggregate?.profile?.revision ?? 'available'}; review it, reconcile intentionally, then explicitly save again to retry.</p>
              {conflictReadError && <Button onClick={async () => {
                try { const latest = await queryClient.fetchQuery({ queryKey: ['property-profile', id], queryFn: () => propertyApi.profile(id) }); setLatestServerAggregate(latest); setConflictReadError(false); }
                catch { setConflictReadError(true); }
              }}>إعادة تحميل المراجعة الحالية</Button>}
            </div>
          </Alert>
        </div>
      )}

      {save.error && !conflict && <div className="mt-6"><Alert>{apiError?.message ?? 'Property Profile could not be saved.'}</Alert></div>}
      {apiError?.fields && !conflict && <Alert><ul>{Object.entries(apiError.fields).map(([field, message]) => <li key={field}>{field}: {message}</li>)}</ul></Alert>}

      <Card className="mt-8">
        <CardHeader><h2 className="font-semibold text-ink">البيانات الإدارية للوحدة</h2></CardHeader>
        <CardContent className="space-y-5">
          <div><Label htmlFor="property-code">كود الوحدة (يُنشأ تلقائياً)</Label><Input id="property-code" dir="ltr" readOnly value={profile.data?.property.property_code ?? ''} /></div>
          <GeographySelector key={selectorVersion} canRead savedLocationId={geographyHydration} onChange={setGeography} />
          <Button variant="outline" onClick={() => { setGeography(null); setGeographyHydration(null); setSelectorVersion((version) => version + 1); setDirty(true); }}>مسح الموقع</Button>
          <div><Label htmlFor="property-address">الشارع والعنوان</Label><Input id="property-address" maxLength={1000} value={address} onChange={(event) => setAddress(event.target.value)} /></div>
          <div className="grid gap-5 md:grid-cols-2">
            <div><Label htmlFor="initial-price">سعر الطلب الابتدائي (اختياري)</Label><Input id="initial-price" dir="ltr" inputMode="decimal" value={askingPrice} onChange={(event) => setAskingPrice(event.target.value)} /></div>
            <div><Label htmlFor="price-currency">العملة</Label><select id="price-currency" className="h-12 w-full rounded-md border border-line bg-surface px-3" value={currency} onChange={(event) => setCurrency(event.target.value)}>{['EGP', 'USD', 'SAR', 'AED'].map((code) => <option key={code}>{code}</option>)}</select></div>
          </div>
          <p className="text-xs text-muted">السعر بيانات إدارية فقط. حفظ إعداد الوحدة لا ينشئ إعلاناً أو عمولة ولا يحدد جاهزية النشر.</p>
        </CardContent>
      </Card>

      <Card className="mt-8">
        <CardHeader><h2 className="font-semibold text-ink">Canonical selections</h2></CardHeader>
        <CardContent>
          <div className="grid gap-5 md:grid-cols-2">
            <div>
              <Label htmlFor="category">Property Category</Label>
              <select id="category" className="h-12 w-full rounded-md border border-line bg-surface px-3 text-sm" value={category ?? ''} onChange={(event) => { setCategory(value(event)); setUnitType(null); }}>
                <option value="">Select category</option>
                {core.data?.categories.map((item) => <option key={item.id} value={item.id}>{title(item)}</option>)}
              </select>
            </div>
            <div>
              <Label htmlFor="unit">Unit Type</Label>
              <select id="unit" disabled={!category} className="h-12 w-full rounded-md border border-line bg-surface px-3 text-sm disabled:opacity-60" value={unitType ?? ''} onChange={(event) => setUnitType(value(event))}>
                <option value="">Select Unit Type</option>
                {unitTypes.map((item) => <option key={item.id} value={item.id}>{title(item)}</option>)}
              </select>
            </div>
            <div>
              <Label htmlFor="developer">Development hierarchy</Label>
              <select id="developer" className="h-12 w-full rounded-md border border-line bg-surface px-3 text-sm" value={developer ?? ''} onChange={(event) => setDeveloper(value(event))}>
                <option value="">No Development selected</option>
                {core.data?.development.developers.map((item) => <option key={item.id} value={item.id}>{title(item)}</option>)}
              </select>
              <p className="mt-1 text-xs text-muted">Developer selection uses the implemented BF015 reference contract.</p>
            </div>
          </div>
        </CardContent>
      </Card>

      {unitType && projection.isLoading && <p className="mt-6 text-sm text-muted">Loading the Unit Type form...</p>}
      {projection.error && <div className="mt-6"><Alert>{(projection.error as ApiClientError).message ?? 'Dynamic form could not be loaded.'}</Alert></div>}
      {projection.data && (
        <Card className="mt-6">
          <CardHeader><h2 className="font-semibold text-ink">Unit Type fields</h2></CardHeader>
          <CardContent>
            <div className="grid gap-5 md:grid-cols-2">
              {projection.data.measurements.map((field) => (
                <div key={field.definition_id}>
                  <Label htmlFor={`m-${field.definition_id}`}>{title(field)} ({field.unit})</Label>
                  <Input id={`m-${field.definition_id}`} inputMode="decimal" value={measurementValues[field.definition_id] ?? ''} onChange={(event) => { setMeasurementsDirty(true); setMeasurementValues({ ...measurementValues, [field.definition_id]: event.target.value }); }} />
                </div>
              ))}
              {projection.data.attributes.map((field) => (
                <div key={field.definition_id}>
                  <Label htmlFor={`a-${field.definition_id}`}>{title(field)}</Label>
                  {field.data_type === 'ENUM' ? (
                    <select id={`a-${field.definition_id}`} className="h-12 w-full rounded-md border border-line bg-surface px-3 text-sm" value={attributeValues[field.definition_id] ?? ''} onChange={(event) => { setAttributesDirty(true); setAttributeValues({ ...attributeValues, [field.definition_id]: event.target.value }); }}>
                      <option value="">Select option</option>
                      {field.options.map((option) => <option key={option.id} value={option.id}>{title(option)}</option>)}
                    </select>
                  ) : field.data_type === 'BOOLEAN' ? (
                    <select id={`a-${field.definition_id}`} className="h-12 w-full rounded-md border border-line bg-surface px-3 text-sm" value={attributeValues[field.definition_id] ?? ''} onChange={(event) => { setAttributesDirty(true); setAttributeValues({ ...attributeValues, [field.definition_id]: event.target.value }); }}>
                      <option value="">Select value</option>
                      <option value="true">Yes</option>
                      <option value="false">No</option>
                    </select>
                  ) : (
                    <Input id={`a-${field.definition_id}`} type={field.data_type === 'DATE' ? 'date' : field.data_type === 'INTEGER' || field.data_type === 'DECIMAL' ? 'number' : 'text'} value={attributeValues[field.definition_id] ?? ''} onChange={(event) => { setAttributesDirty(true); setAttributeValues({ ...attributeValues, [field.definition_id]: event.target.value }); }} />
                  )}
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      <PropertyPrimaryImage id={id} />
      <div className="mt-7 flex items-center gap-4">
        <Button onClick={submit} disabled={save.isPending || (conflict && !latestServerAggregate?.profile) || (apiError !== null && [401, 403].includes(apiError.status))}>{save.isPending ? 'جارٍ الحفظ...' : conflict ? 'إعادة الحفظ بالمراجعة الحالية' : 'حفظ بيانات الوحدة'}</Button>
        <Button asChild variant="outline"><Link href={`/admin/properties/${id}/setup/ownership`}>Owner & Ownership</Link></Button>
        {profile.data?.profile && <p className="text-xs text-muted">Profile revision {profile.data.profile.revision}</p>}
      </div>
    </main>
  );
}
