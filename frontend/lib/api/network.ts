import type { Permission } from '@/types/auth';
import type { Franchise, OnboardFranchiseInput, OnboardingResult, PartnerAgency } from '@/types/network';
import { apiFetch } from './client';

export const networkApi = {
  franchises: () => apiFetch<Franchise[]>('/api/network/franchises'),
  franchise: (id: string) => apiFetch<Franchise>(`/api/network/franchises/${id}`),
  onboard: (input: OnboardFranchiseInput) => apiFetch<OnboardingResult>('/api/network/franchises/onboard', {
    method: 'POST',
    body: JSON.stringify(input),
  }),
  archiveFranchise: (id: string) => apiFetch<unknown>(`/api/network/franchises/${id}`, { method: 'DELETE' }),
  partnerAgencies: () => apiFetch<PartnerAgency[]>('/api/network/partner-agencies'),
  permissions: () => apiFetch<Permission[]>('/api/network/permissions'),
};
