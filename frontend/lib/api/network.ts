import type { Permission } from '@/types/auth';
import type { Franchise, OnboardFranchiseInput, OnboardingResult, OrganizationBasicProfile, PartnerAgency } from '@/types/network';
import { apiFetch } from './client';
import type { Organization } from '@/types/auth';
export interface GeographicLocation {
 id: number; parent_id: number | null; code: string; name_en: string; name_ar: string; location_type: string; status: string;
}
export interface GeographicAncestry { locations: GeographicLocation[]; stop_reason: 'root' | 'missing_parent' | 'cycle' | 'depth_limit'; }
async function geographicPages(path: string): Promise<GeographicLocation[]> {
 const rows: GeographicLocation[] = [];
 for (let offset = 0; ; offset += 200) {
  const page = await apiFetch<GeographicLocation[]>(path + (path.includes('?') ? '&' : '?') + 'status=active&limit=200&offset=' + offset);
  rows.push(...page);
  if (page.length < 200) return rows;
 }
}


export const networkApi = {
  organizations: () => apiFetch<Organization[]>('/api/network/organizations'),
  franchises: () => apiFetch<Franchise[]>('/api/network/franchises'),
  franchise: (id: string) => apiFetch<Franchise>(`/api/network/franchises/${id}`),
  onboard: (input: OnboardFranchiseInput) => apiFetch<OnboardingResult>('/api/network/franchises/onboard', {
    method: 'POST',
    body: JSON.stringify(input),
  }),
  archiveFranchise: (id: string) => apiFetch<unknown>(`/api/network/franchises/${id}`, { method: 'DELETE' }),
  partnerAgencies: () => apiFetch<PartnerAgency[]>('/api/network/partner-agencies'),
  permissions: () => apiFetch<Permission[]>('/api/network/permissions'),
  basicProfile: (id: string) => apiFetch<{ profile: OrganizationBasicProfile | null }>(`/api/network/franchises/${id}/basic-profile`).then(({ profile }) => profile),
  saveBasicProfile: (id: string, input: { organization_name?: string; geographic_location_id?: number | null; address_text?: string | null }) => apiFetch<{ profile: OrganizationBasicProfile }>(`/api/network/franchises/${id}/basic-profile`, { method: 'PUT', body: JSON.stringify(input) }).then(({ profile }) => profile),
  geographicLocations: () => geographicPages('/api/catalog/geographic-locations?parent_id=null&location_type=COUNTRY'),
  geographicChildren: (id: number) => geographicPages('/api/catalog/geographic-locations/' + id + '/children'),
  geographicAncestry: (id: number) => apiFetch<GeographicAncestry>('/api/catalog/geographic-locations/' + id + '/ancestry'),
};
