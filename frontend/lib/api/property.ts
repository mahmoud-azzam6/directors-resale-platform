import { apiFetch } from './client';
import type { CorePropertyForm, DynamicPropertyForm, OrganizationProperty, ProfileAggregate, SaveProfileInput } from '@/types/property';
export const propertyApi = {
  list: () => apiFetch<OrganizationProperty[]>('/api/properties'),
  create: (property_label: string) => apiFetch<OrganizationProperty>('/api/properties', { method: 'POST', body: JSON.stringify({ property_label }) }),
  profile: (id: string) => apiFetch<ProfileAggregate>(`/api/properties/${id}/profile`),
  saveProfile: (id: string, data: SaveProfileInput) => apiFetch<ProfileAggregate>(`/api/properties/${id}/profile`, { method: 'PUT', body: JSON.stringify(data) }),
  coreForm: () => apiFetch<CorePropertyForm>('/api/properties/core-form'),
  unitTypeForm: (id: number) => apiFetch<DynamicPropertyForm>(`/api/properties/unit-types/${id}/property-form`),
};