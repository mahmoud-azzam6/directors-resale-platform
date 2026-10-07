import { apiFetch } from './client';
import type { Listing } from './listing';

export type InterestReceipt = {
  id: number; reference: string; listing_id: number; organization_property_id: number;
  status: 'submitted'; created_at: string;
  property: { property_code: string; property_label: string };
};
export const publishedListingApi = {
  list: () => apiFetch<Listing[]>('/api/published-listings'),
  detail: (id: string) => apiFetch<Listing>(`/api/published-listings/${id}`),
  submit: (id: string) => apiFetch<InterestReceipt>(`/api/published-listings/${id}/requests`, { method: 'POST', body: JSON.stringify({}) }),
  own: () => apiFetch<InterestReceipt[]>('/api/my-listing-requests'),
};
