import { apiFetch } from './client';
import type { CatalogPermission, ManagedOrganization, ManagedPosition, ManagedUser, PositionInput, UserInput } from '@/types/administration';

export const administrationApi = {
  users: () => apiFetch<ManagedUser[]>('/api/administration/users'),
  userOrganizations: () => apiFetch<ManagedOrganization[]>('/api/administration/users/organizations'),
  createUser: (data: UserInput & { organization_id: number }) => apiFetch<ManagedUser>('/api/administration/users', { method: 'POST', body: JSON.stringify(data) }),
  updateUser: (id: number, data: UserInput) => apiFetch<ManagedUser>(`/api/administration/users/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deactivateUser: (id: number) => apiFetch<unknown>(`/api/administration/users/${id}`, { method: 'DELETE' }),
  positions: () => apiFetch<ManagedPosition[]>('/api/administration/positions'),
  positionOrganizations: () => apiFetch<ManagedOrganization[]>('/api/administration/positions/organizations'),
  createPosition: (data: PositionInput & { organization_id: number }) => apiFetch<ManagedPosition>('/api/administration/positions', { method: 'POST', body: JSON.stringify(data) }),
  updatePosition: (id: number, data: PositionInput) => apiFetch<ManagedPosition>(`/api/administration/positions/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deactivatePosition: (id: number) => apiFetch<unknown>(`/api/administration/positions/${id}`, { method: 'DELETE' }),
  catalog: () => apiFetch<CatalogPermission[]>('/api/administration/permissions'),
  positionPermissions: (id: number) => apiFetch<CatalogPermission[]>(`/api/administration/positions/${id}/permissions`),
  assignPermissions: (id: number, permissions: number[]) => apiFetch<CatalogPermission[]>(`/api/administration/positions/${id}/permissions`, { method: 'PUT', body: JSON.stringify({ permissions }) }),
};
