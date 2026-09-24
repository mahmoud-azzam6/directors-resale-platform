'use client';

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
  const [developer, setDeveloper] = useState<number | null>(null);
  const [measurementValues, setMeasurementValues] = useState<Record<number, string>>({});
  const [attributeValues, setAttributeValues] = useState<Record<number, string>>({});
  const [measurementsDirty, setMeasurementsDirty] = useState(false);
  const [attributesDirty, setAttributesDirty] = useState(false);
  const [conflict, setConflict] = useState(false);
  const [latestServerAggregate, setLatestServerAggregate] = useState<ProfileAggregate | null>(null);

  const core = useQuery({ queryKey: ['property-core-form'], queryFn: propertyApi.coreForm });
  const profile = useQuery({ queryKey: ['property-profile', id], queryFn: () => propertyApi.profile(id) });
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
    setDeveloper(savedProfile?.developer_id ?? null);
    setMeasurementValues(values.measurements);
    setAttributeValues(values.attributes);
    setMeasurementsDirty(false);
    setAttributesDirty(false);
  }, []);

  useEffect(() => {
    if (profile.data && !conflict) {
      hydrateAggregate(profile.data);
    }
  }, [conflict, hydrateAggregate, profile.data]);

  const unitTypes = useMemo(
    () => core.data?.unit_types.filter((item) => item.category_id === category) ?? [],
    [core.data, category],
  );

  const save = useMutation({
    mutationFn: (payload: SaveProfileInput) => propertyApi.saveProfile(id, payload),
    onSuccess: (aggregate) => {
      setConflict(false);
      setLatestServerAggregate(null);
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
        const currentAggregate = await queryClient.fetchQuery({
          queryKey: ['property-profile', id],
          queryFn: () => propertyApi.profile(id),
        });
        setLatestServerAggregate(currentAggregate);
      }
    },
  });

  if (core.isLoading || profile.isLoading) {
    return <main className="page-shell"><p className="text-sm text-muted">Loading Property Data...</p></main>;
  }

  if (core.error || profile.error) {
    const error = (core.error ?? profile.error) as ApiClientError;
    return <main className="page-shell"><Alert>{error?.status === 403 ? 'Your current backend permissions do not allow this Property.' : error?.message ?? 'Property Data could not be loaded.'}</Alert></main>;
  }

  const submit = () => {
    const payload: SaveProfileInput = {
      property_category_id: category,
      unit_type_id: unitType,
    };

    if (geography !== null) {
      payload.geographic_location_id = geography;
    }

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
      payload.expected_revision = profile.data.profile.revision;
    }

    save.mutate(payload);
  };

  const apiError = save.error instanceof ApiClientError ? save.error : null;

  return (
    <main className="page-shell fade-up">
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
            </div>
          </Alert>
        </div>
      )}

      {save.error && !conflict && <div className="mt-6"><Alert>{apiError?.message ?? 'Property Profile could not be saved.'}</Alert></div>}

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
              <Label htmlFor="geography">Geography</Label>
              <select id="geography" className="h-12 w-full rounded-md border border-line bg-surface px-3 text-sm" value={geography ?? ''} onChange={(event) => setGeography(value(event))}>
                <option value="">No Geography selected</option>
                {core.data?.geography.countries.map((item) => <option key={item.id} value={item.id}>{title(item)}</option>)}
              </select>
              <p className="mt-1 text-xs text-muted">The current BF014 core-form contract exposes countries only. Governorate, City, Area, and District selection are not implemented in this bounded AF004.2 UI.</p>
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

      <div className="mt-7 flex items-center gap-4">
        <Button onClick={submit} disabled={save.isPending}>{save.isPending ? 'Saving...' : 'Save Property Data'}</Button>
        {profile.data?.profile && <p className="text-xs text-muted">Profile revision {profile.data.profile.revision}</p>}
      </div>
    </main>
  );
}