# ADR-001: Organization Hierarchy

Status: Approved

## Context

The platform requires one consistent business hierarchy for the implemented Organization, Franchise,
Partner Agency, and User scope.

## Decision

- The System Organization is the single highest business entity.
- A Franchise belongs to the System Organization.
- A Partner Agency belongs to one Franchise.
- The approved hierarchy is Organization -> Franchise -> Partner Agency.
- Users belong to the appropriate business Organization within this approved hierarchy.
- The hierarchy defines organizational ownership and Administrative Scope; it does not isolate future
  Global Marketplace Visibility.

## Scope

This decision records the Organization hierarchy only. Admin experience, provisioning, future Global
Marketplace Visibility, and Administrative Scope are defined by
`docs/architecture/ADMIN_EXPERIENCE_ARCHITECTURE.md`. No additional hierarchy level is approved here.
