export type OwnerPartyType = 'individual' | 'legal_entity';

export interface Owner {
  id: number;
  organization_id: number;
  party_type: OwnerPartyType;
  display_name: string;
  status: 'active' | 'inactive';
  mobile: string | null;
  email: string | null;
  preferred_contact_method: 'phone' | 'whatsapp' | 'email' | null;
  contact_person_name: string | null;
  contact_person_mobile: string | null;
  contact_person_email: string | null;
}

export interface OwnerInput {
  party_type: OwnerPartyType;
  display_name: string;
  mobile?: string | null;
  email?: string | null;
  preferred_contact_method?: 'phone' | 'whatsapp' | 'email' | null;
  contact_person_name?: string | null;
  contact_person_mobile?: string | null;
  contact_person_email?: string | null;
}

export interface Ownership {
  id: number;
  organization_property_id: number;
  organization_id: number;
  status: 'current' | 'closed';
  closed_at: string | null;
  parties?: OwnershipParty[];
  acting_owner?: ActingOwner | null;
}

export interface OwnershipParty {
  id: number;
  ownership_id: number;
  owner_id: number;
  organization_id: number;
  share_percentage: string;
}

export interface ActingOwner {
  id: number;
  ownership_id: number;
  ownership_party_id: number;
  basis_source: string;
  notes: string | null;
  started_at: string;
  ended_at: string | null;
}

export interface CreateOwnershipInput {
  parties: Array<{ owner_id: number; share_percentage: string }>;
  acting_owner?: { owner_id: number; basis_source: string; notes?: string | null } | null;
}

export interface ActingOwnerInput {
  ownership_party_id: number;
  basis_source: string;
  notes?: string | null;
}
