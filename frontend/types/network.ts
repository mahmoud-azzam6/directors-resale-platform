import type { Organization, Permission } from '@/types/auth';

export interface Franchise extends Organization {
  organization_type: 'franchise';
  parent_organization_id: number;
}

export interface PartnerAgency extends Organization {
  organization_type: 'partner_agency';
  parent_organization_id: number;
}

export interface OnboardFranchiseInput {
  franchise: { name: string; code: string; parent_organization_id: number; status: 'active' };
  position: { name: string; code: string };
  administrator: { full_name: string; email: string; phone: string | null };
  permissions: number[];
}

export interface OnboardingResult {
  franchise: Franchise;
  position: { id: number; organization_id: number; name: string; code: string; status: string };
  permissions: Permission[];
  administrator: {
    id: number;
    organization_id: number;
    position_id: number;
    full_name: string;
    email: string;
    phone: string | null;
    status: 'inactive';
  };
  activation_status: 'credential_setup_required';
}
