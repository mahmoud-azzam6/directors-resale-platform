export type PermissionCode = string;

export interface AuthUser {
  id: number;
  organization_id: number;
  position_id: number | null;
  full_name: string;
  email: string;
  phone: string | null;
  status: 'active' | 'inactive';
  created_at: string | null;
  updated_at: string | null;
}

export interface Organization {
  id: number;
  parent_organization_id?: number | null;
  name: string;
  code: string;
  organization_type: string;
  status: string;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface Position {
  id: number;
  organization_id: number;
  name: string;
  code: string;
  status: string;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface Permission {
  id: number;
  code: string;
  name: string;
  status: string;
}

export interface AuthContext {
  user: AuthUser;
  organization: Organization | null;
  position: Position | null;
  permissions: PermissionCode[];
}
