# Business Rules

## Listing Domain

The Listing Domain Architecture is approved but not implemented. The canonical source is `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`.

- MVP Listing scope is Residential Resale, with category-extensible architecture.
- Property is permanent, Ownership changes independently, and Listing is a temporary market offering.
- Listing history is preserved; Listings are not hard-deleted.
- All authenticated platform Users may browse marketplace-eligible Listings platform-wide.
- Marketplace visibility does not grant management or private-data authority.
- Managing Organization, Created By, Originated By, and Assigned To remain separate facts.
- Assignment does not change origination or create transfer rights.
- Assignment remains within the Managing Organization.
- Listing transfer is never automatic and is derived later from provenance through the Transfer Engine.
- Franchise and Partner Agency Listings require authorized internal approval in their own Organization; normal Partner Agency publication does not require Parent Franchise approval.
- An authorized Admin may approve their own submitted or sold action; audit history remains required.
- Owner approval is version-specific and channel-agnostic. Material changes invalidate it and require renewed review/approval.
- `AVAILABLE` is marketplace-visible and accepts Requests. `HOLD` remains visible but accepts no new Requests.
- Final `SOLD` requires `SOLD_PENDING_APPROVAL` and authorized approval.
- External sales do not create fake Requests.
- Withdrawn Listings returning to market reuse their history and repeat current approval requirements.
- Owner identity and private Listing data are Organization-scoped and hidden from the global marketplace by default.
- Publication media and private documents remain separate.
- Exact Listing Permission codes, schema, API, and implementation sprint remain deferred.
