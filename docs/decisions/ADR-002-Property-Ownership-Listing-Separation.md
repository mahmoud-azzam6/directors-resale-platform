# ADR-002: Property / Ownership / Listing Separation

Status: Approved

## Context

The platform must preserve the permanent real-estate asset, changes in ownership, and temporary market offerings without losing historical relationships. Marketplace discovery must also remain distinct from Organization administration.

## Decision

- Global Physical Property Identity is a System-only analytical identity for the persistent real-estate asset.
- Organization Property is an Organization-owned operational representation and remains independent even when System reconciliation confirms that multiple records represent the same real-world unit.
- Ownership changes independently and preserves ownership context/history.
- A Listing is a temporary market offering referencing Property and Ownership context.
- Future Deals reference Listings rather than replacing Property or Ownership facts.
- Listing lifecycle and historical relationships are preserved without hard deletion.
- Marketplace eligibility is governed by Listing publication/lifecycle rules and is separate from Organization administrative scope.

## Consequences

Global identity reconciliation never merges, transfers, deduplicates, or exposes Organization operational records, Ownerships, Listings, Owners, documents, or history. Property, Ownership, Listing, Sale, and Ownership Transfer must not be collapsed into one entity. Future schema and APIs must preserve these boundaries and distinguish marketplace visibility from management authority.

Detailed approved behavior is defined by `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`; the approved conceptual physical model is defined by `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md`. BF013 defines the approved Property & Ownership Foundation implementation boundary in `docs/sprints/BF013-property-ownership-foundation.md`; implementation has not started.
