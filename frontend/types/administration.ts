import type { AuthUser, Organization, Permission, Position } from '@/types/auth';

export type ManagedUser = AuthUser;
export type ManagedPosition = Position;
export type ManagedOrganization = Organization;
export type CatalogPermission = Permission;

export interface UserInput {
  organization_id?: number;
  full_name: string;
  email: string;
  phone: string | null;
  position_id: number | null;
  status: 'active' | 'inactive';
}

export interface PositionInput {
  organization_id?: number;
  name: string;
  code: string;
  status: 'active' | 'inactive';
}
