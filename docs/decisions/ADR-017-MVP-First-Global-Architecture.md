# ADR-017

## Title

MVP First, Global Architecture

---

## Status

Approved

---

## Context

The platform will initially launch in Egypt.

Future expansion is planned for GCC countries.

Potential future expansion may include additional international markets.

The architecture must support expansion without requiring database redesign.

---

## Decision

The system architecture will always be global.

The MVP implementation will initially include:

- Egypt
- Arabic
- Egyptian Pound (EGP)
- Africa/Cairo Timezone

Additional countries, languages and currencies will be introduced through Seed Data and Configuration.

---

## Consequences

### Benefits

- No future database redesign.
- Faster expansion.
- Cleaner architecture.
- Simpler MVP implementation.
- Lower development cost.

### Trade-offs

- Some reference tables contain future-ready structures.
- Initial seed data is intentionally minimal.

---

## Related ADRs

ADR-005 UUID / ULID Strategy

ADR-013 Reference Data Strategy

ADR-014 Multi-Currency Strategy

ADR-016 Business Entities vs Reference Data

---

Approved By

Product Owner

Solution Architect