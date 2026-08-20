# Implementation Plan - Current Status

## Completed

| Milestone | Status | Verified State |
|---|---|---|
| BF001 | Completed | Backend bootstrap, container, router, response, exception handling, health endpoint |
| BF002 / BF002.1 / BF002.2 | Completed | Contracts and reusable database foundation |
| BF003 | Completed | Generic BaseRepository |
| BF004 | Completed | Service-container functionality present; no separate Git commit |
| BF005 | Completed | HTTP Kernel and Request abstraction |
| BF006 | Completed | Organization CRUD/API, staged `organizations` table, and routing fix `a017c94` |
| BF007 | Completed | Organization-backed Franchise CRUD/API, `parent_organization_id`, System-parent validation, and status-based archival |
| BF008 | Implemented | Organization-backed Partner Agency CRUD/API, Franchise-parent validation, isolation, and status-based archival |
| BF009 | Implemented | Staged User CRUD/API, active Organization ownership, global unique email, immutable ownership, and status-based deactivation |
| BF010 | Implemented | Secure password credentials, hashed expiring bearer tokens, login/logout/me, reusable authentication protection, and protected business routes |
| BF011 | Implemented | Dynamic Organization-owned Positions, scoped code uniqueness, nullable same-Organization User assignment, and status-based deactivation |
| BF012 | Implemented | Controlled Permission catalog, Position-Permission inheritance, reusable authorization, Organization scope enforcement, and isolated lists |
| AF001 | Implemented | Next.js Admin UI foundation, secure HttpOnly auth bridge, context, protected layout, permission-aware navigation, dashboard, and placeholders |

BF006 smoke tests and BF007 manual acceptance/regression checks passed. No automated test suite exists.

## Next Development Target

No next milestone is selected. AF002 has an approved product direction of Network Administration UI,
but no AF002 sprint contract is approved and AF002 is not implemented. AF003 is not selected or designed.

## Constraints for Future Planning

- Preserve the BF006-BF007 staged `organizations` schema unless an approved milestone changes it.
- Resolve the documented no-hard-delete versus Organization physical DELETE discrepancy before
  relying on deletion behavior for future business modules.
- Franchise DELETE is scoped to status-based archival (`inactive`); it does not create a general lifecycle framework.
- Authentication and authorization are implemented through BF010-BF012 and remain backend-authoritative.
- Preserve the approved System Organization -> Franchise -> Partner Agency hierarchy. Users belong to an
  Organization in this hierarchy; do not treat Users as another hierarchy level.
- Preserve the approved distinction between future Global Marketplace Visibility and Administrative
  Scope. Do not use Organization management scope to isolate marketplace-eligible available Listings.
- Keep exact Listing Permission codes, Team scope, Request workflow, reporting metrics, and commission
  behavior deferred until their domain-specific contracts are approved.
- Do not infer implementation from broader planned architecture documents.
