import { apiFetch } from './client';

export type Listing = {
  id: number; organization_id: number; organization_property_id: number;
  status: 'draft' | 'published' | 'archived'; revision: number;
  organization: { id: number; name: string; organization_type: string };
  property: {
    id: number; property_code: string; property_label: string; address_text: string | null;
    location: { id: number; name_ar: string; name_en: string; location_type: string } | null;
    initial_asking_price: string | null; currency_code: string | null; primary_image_url: string | null;
  };
};
export const listingApi = {
  list: () => apiFetch<Listing[]>('/api/listings'),
  detail: (id: string) => apiFetch<Listing>(`/api/listings/${id}`),
  create: (organization_property_id: number) => apiFetch<Listing>('/api/listings', { method: 'POST', body: JSON.stringify({ organization_property_id }) }),
  transition: (id: string, operation: 'publish' | 'archive', revision: number) => apiFetch<Listing>(`/api/listings/${id}/${operation}`, { method: 'POST', body: JSON.stringify({ revision }) }),
};
