import { apiFetch } from './client';
import type { ActingOwner, ActingOwnerInput, CreateOwnershipInput, Owner, OwnerInput, Ownership, OwnershipParty } from '@/types/ownership';

export const ownershipApi = {
  owners: () => apiFetch<Owner[]>('/api/owners'),
  owner: (id: number) => apiFetch<Owner>(`/api/owners/${id}`),
  createOwner: (data: OwnerInput) => apiFetch<Owner>('/api/owners', { method: 'POST', body: JSON.stringify(data) }),
  updateOwner: (id: number, data: Partial<OwnerInput>) => apiFetch<Owner>(`/api/owners/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deactivateOwner: (id: number) => apiFetch<Owner>(`/api/owners/${id}`, { method: 'DELETE' }),
  reactivateOwner: (id: number) => apiFetch<Owner>(`/api/owners/${id}/reactivate`, { method: 'POST' }),
  currentOwnership: (propertyId: string) => apiFetch<Ownership>(`/api/properties/${propertyId}/ownerships/current`),
  ownershipHistory: (propertyId: string) => apiFetch<Ownership[]>(`/api/properties/${propertyId}/ownerships`),
  createOwnership: (propertyId: string, data: CreateOwnershipInput) => apiFetch<Ownership>(`/api/properties/${propertyId}/ownerships`, { method: 'POST', body: JSON.stringify(data) }),
  closeOwnership: (id: number) => apiFetch<Ownership>(`/api/ownerships/${id}/close`, { method: 'POST' }),
  parties: (ownershipId: number) => apiFetch<OwnershipParty[]>(`/api/ownerships/${ownershipId}/parties`),
  addParty: (ownershipId: number, owner_id: number, share_percentage: string) => apiFetch<OwnershipParty>(`/api/ownerships/${ownershipId}/parties`, { method: 'POST', body: JSON.stringify({ owner_id, share_percentage }) }),
  updatePartyShare: (ownershipId: number, partyId: number, share_percentage: string) => apiFetch<OwnershipParty>(`/api/ownerships/${ownershipId}/parties/${partyId}`, { method: 'PUT', body: JSON.stringify({ share_percentage }) }),
  removeParty: (ownershipId: number, partyId: number) => apiFetch<void>(`/api/ownerships/${ownershipId}/parties/${partyId}`, { method: 'DELETE' }),
  actingOwner: (ownershipId: number) => apiFetch<ActingOwner>(`/api/ownerships/${ownershipId}/acting-owner`),
  actingOwnerHistory: (ownershipId: number) => apiFetch<ActingOwner[]>(`/api/ownerships/${ownershipId}/acting-owner/history`),
  designateActingOwner: (ownershipId: number, data: ActingOwnerInput) => apiFetch<ActingOwner>(`/api/ownerships/${ownershipId}/acting-owner`, { method: 'POST', body: JSON.stringify(data) }),
  changeActingOwner: (ownershipId: number, data: ActingOwnerInput) => apiFetch<ActingOwner>(`/api/ownerships/${ownershipId}/acting-owner`, { method: 'PUT', body: JSON.stringify(data) }),
  clearActingOwner: (ownershipId: number) => apiFetch<void>(`/api/ownerships/${ownershipId}/acting-owner`, { method: 'DELETE' }),
};
