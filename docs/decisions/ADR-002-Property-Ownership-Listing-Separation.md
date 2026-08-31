# ADR-002: Property / Ownership / Listing Separation

Status: Approved

## Context

The platform must preserve the permanent real-estate asset, changes in ownership, and temporary market offerings without losing historical relationships. Marketplace discovery must also remain distinct from Organization administration.

## Decision

- Property is the permanent real-estate asset.
- Ownership changes independently and preserves ownership context/history.
- A Listing is a temporary market offering referencing Property and Ownership context.
- Future Deals reference Listings rather than replacing Property or Ownership facts.
- Listing lifecycle and historical relationships are preserved without hard deletion.
- Marketplace eligibility is governed by Listing publication/lifecycle rules and is separate from Organization administrative scope.

## Consequences

Property, Ownership, Listing, and Deal records must not be collapsed into one entity. Future physical schema and APIs must preserve these boundaries and distinguish marketplace visibility from management authority.

Detailed approved behavior is defined by `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`.
