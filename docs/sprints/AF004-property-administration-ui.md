# AF004 — Property Administration UI

**Milestone:** Admin Frontend / Property Administration  
**Status:** ARCHITECTURE APPROVED / NOT IMPLEMENTED  
**Current internal unit:** AF004.1 — Architecture & Route Contract  
**Depends on:** AF001, AF002, AF003, BF013, BF014, BF015  
**Frontend:** Existing `frontend/` Next.js Admin Application

---

## 1. Purpose and status

AF004 defines the approved frontend contract for administration of an Organization Property. It composes the implemented BF013 Property and Ownership APIs, BF014 canonical catalogs and dynamic form projections, and BF015 Property Profile Persistence Bridge inside the existing AF001–AF003 Admin application.

This document approves architecture only. It does not implement a frontend route, component, API proxy, backend endpoint, schema, migration, media store, completeness calculation, Listing behavior, or a new authorization rule.

The status terms are distinct:

- **Architecture Approved** means this document locks the intended frontend contract.
- **Implemented** means executable frontend or backend behavior exists in the repository.
- **Verified** means the implemented behavior has passed its agreed evidence.
- **Closed** means the relevant internal unit has completed its approved delivery and evidence.

AF004 is not implemented or verified. AF004.1 is ready for implementation planning after architecture review; AF004.2–AF004.6 are not started.

---

## 2. Information architecture

Property Administration lives in the existing protected `/admin` application. It adds a permission-aware **Properties** destination when the implemented frontend work begins; navigation visibility is UX only and never substitutes for backend authorization.

The approved experience contains:

- **Properties list** — Organization-scoped Property shells returned by BF013; it may link to Add Unit and Property details.
- **Add Unit** — the entry point creates or resumes an Organization Property setup journey.
- **Property details** — a bounded administrative view of the Property shell, persisted Profile, Ownership state, and setup/review context available through existing APIs.
- **Property setup flow** — the three-step orchestration below; it saves real server state progressively rather than maintaining a frontend-only draft as the source of truth.
- **Owner & Ownership flow** — a distinct BF013 composition within the setup journey and Property details.
- **Review state** — a read-only summary of persisted Property/Profile/Ownership data and unresolved workflow notices. It is not Listing review, publication, or completeness truth.

No AF004 screen may represent one Organization's private Property, Profile, Owner, Ownership, or Acting Owner data as a global marketplace resource.

---

## 3. Approved App Router structure

AF004 extends the established `/admin` App Router namespace. It does not create a second admin application, an unprotected Property namespace, or a Listing namespace.

```text
/admin/properties
/admin/properties/new
/admin/properties/[id]
/admin/properties/[id]/setup
/admin/properties/[id]/setup/property-data
/admin/properties/[id]/setup/ownership
/admin/properties/[id]/setup/review
```

`/admin/properties/new` is the Add Unit entry point. It creates a BF013 Organization Property shell only through the existing backend contract, then continues at the Property Data step. The nested setup routes describe UI state and may redirect to the appropriate first incomplete user task; they must not create a parallel persistence model.

`/admin/properties/[id]` is the administrative details view. The setup routes may be revisited for authorized edits. A future route may be added only when it is backed by an approved implementation contract; AF004 does not predeclare routes for media, documents, Listing, marketplace, Request, Deal, or Commission workflows.

Feature code must follow the AF001 convention: App Router pages compose feature components, API communication remains centralized under `frontend/lib/api/`, and components do not make arbitrary domain `fetch()` calls.

---

## 4. Three-step orchestration

### Step 1 — Property Data / بيانات الوحدة

Step 1 composes the BF013 shell, BF014 projection/catalog reads, and BF015 profile save contract.

It presents and persists, only through implemented backend contracts:

- Property Category and Unit Type.
- The dynamic Unit Type form from `GET /unit-types/{id}/property-form`; core selectors may use `GET /property-profile/core-form`.
- The selected canonical Geography hierarchy.
- The selected canonical Development reference hierarchy.
- Measurements in their canonical units.
- Attributes, including active owned ENUM options where applicable.
- Progressive Profile persistence through `GET` and `PUT /organization-properties/{id}/profile`.

The UI may save a Category-only or partial Profile because BF015 supports progressive persistence. Omitted values remain unchanged; explicit clears use the existing BF015 profile contract. The frontend must render the server aggregate after a successful save and must use the returned pinned configuration/version semantics rather than inferring a replacement configuration from current catalog state.

For `PROFILE_REVISION_CONFLICT`, the UI must retain unsaved user input locally, fetch the current server aggregate, explain that the Profile changed, and require an explicit user reconciliation/retry. It must not silently overwrite the newer Profile, discard the server version, or invent conflict-resolution rules. Other machine-readable backend validation and lifecycle errors remain backend-defined and are displayed safely through the shared API error handling.

### Step 2 — Owner & Ownership / المالك والملكية

Step 2 composes existing BF013 APIs; it does not add Owner or Ownership columns to the BF015 Profile.

The flow may provide:

- search, create, view, update, activate, and deactivate Owner actions through the existing `/owners` endpoints;
- a current Ownership and historical Ownership view for the selected Property;
- Ownership Parties for co-ownership and their share semantics;
- Authorized Acting Owner designation, change, clearing, and history.

It must reuse the implemented BF013 endpoints, including `/organization-properties/{id}/ownerships`, `/ownerships/{id}/parties`, and `/ownerships/{id}/acting-owner`. Owner, Ownership, Ownership Party, and Acting Owner state remain Organization-private BF013 aggregates. The Property Profile only references canonical BF014 selections and values; it never duplicates Owner or Ownership data.

### Step 3 — Images & Review / الصور والمراجعة

Step 3 may render a review summary of the persisted Property shell, Profile selections/values, and available Ownership context. It may identify that images or private documents are not yet supported.

AF004 creates no media persistence, upload endpoint, fake upload state, browser-only media record, private-document store, or Listing draft. Images and private documents require a separate approved backend contract and implementation before a real upload experience can exist.

---

## 5. Authorization, privacy, and lifecycle

The frontend consumes the authenticated context and effective backend permission codes established by AF001–AF003. It may hide unavailable navigation or actions for clarity, but TypeScript, route guards, and component conditions do not decide authority.

- Property list/read actions require the backend's `properties.view` enforcement; Property/Profile writes require `properties.manage`.
- Owner and Ownership views and mutations use their existing BF013 capability checks.
- A System actor, a Franchise actor within its authorized scope, and a Partner Agency actor within its authorized scope receive only the backend-authorized result.
- A Parent Franchise does not automatically receive child Partner Agency private Property/Profile, Owner, Ownership, or Acting Owner access.
- Global marketplace visibility never grants administrative Property access, private profile access, Owner access, Ownership access, reporting access, or mutation authority.
- Archived Properties retain readable state where authorized and reject normal Profile mutation according to BF013/BF015; the UI reflects the server outcome rather than creating its own lifecycle rule.

---

## 6. Completeness and Listing boundary

AF004 does not define frontend-only completeness truth. It must not add `COMPLETE` or `INCOMPLETE` filters, badges, gates, or counts unless a separately implemented backend contract provides them.

A Property setup review may describe persisted data and server-returned validation results, but **Property Setup Complete is not Listing Ready**. Saving a Property shell, Profile, Owner, Ownership, or review action creates no Listing, Listing Version, marketplace publication, Owner approval, Request, Deal, Commission, or transfer side effect.

The approved Listing Domain and Rich Property Profile architecture remain separate, approved, and not implemented beyond the BF015 persistence bridge. AF004 does not narrow or implement those domain boundaries.

---

## 7. AF004 internal units

| Unit | Objective | Scope | Dependencies | Non-goals | Acceptance evidence |
| --- | --- | --- | --- | --- | --- |
| AF004.1 | Lock Property Administration information architecture, routes, orchestration, and boundaries. | This document and aligned canonical status records. | AF001–AF003; BF013–BF015. | Frontend code, backend changes, media, completeness, Listing. | Architecture review confirms route/API reuse, privacy, and deferred boundaries. |
| AF004.2 | Implement the Property Data step. | Properties list/Add Unit shell, Step 1, centralized API clients, dynamic projection, progressive Profile saves, and revision-conflict UX. | AF004.1; BF013, BF014, BF015. | Owner/Ownership UI, media, completeness, Listing. | Frontend build/type/lint plus real backend integration and Profile conflict/error evidence. |
| AF004.3 | Implement the Owner & Ownership step. | BF013 Owner, Ownership Party/co-ownership, and Acting Owner composition. | AF004.1, AF004.2, BF013. | Duplicating Owner/Ownership data in Profile; Listing approval. | Real scoped Owner/Ownership journeys and privacy/authorization evidence. |
| AF004.4 | Implement the review experience. | Read-only summary of persisted Property/Profile/Ownership state and safe server errors. | AF004.2, AF004.3. | Completeness truth, Listing readiness, publication, media upload. | Deterministic review rendering from backend aggregates and route/authorization checks. |
| AF004.5 | Define the media placeholder boundary in the implemented UI. | Honest unavailable-state messaging only if needed by the reviewed experience. | AF004.1, AF004.4. | Upload controls, media persistence, private documents, browser-only fake state. | Review confirms no media/document request, storage, or Listing side effect exists. |
| AF004.6 | Verify and close the implemented AF004 frontend scope. | Focused frontend acceptance, backend-contract regression, privacy, authorization, and documentation closure. | AF004.2–AF004.5. | New backend domain scope or Listing implementation. | Approved acceptance suite, build/type/lint, regression evidence, and closure review. |

---

## 8. Non-goals

AF004 does not implement:

- media or private-document persistence;
- frontend-owned completeness rules;
- Listing, Listing Version, marketplace publication, approval, hold, sold, withdrawal, Request, Deal, Commission, or transfer behavior;
- a new Property/Profile/Owner/Ownership backend API or authorization scheme;
- duplicate Owner or Ownership persistence in a Property Profile;
- a separate System, Franchise, or Partner Agency frontend application;
- an authorization decision derived from Position names or frontend organization assumptions.

## 9. AF004.1 acceptance

AF004.1 is ready when this contract:

- uses the existing `/admin` App Router convention and existing BF013/BF014/BF015 routes;
- locks the three-step orchestration and the Profile revision-conflict behavior;
- preserves BF013 Owner/Ownership aggregate separation;
- marks media/private documents, completeness truth, and Listing behavior as deferred;
- states backend-authoritative authorization and the Parent Franchise/child Partner Agency privacy boundary;
- records AF004.2–AF004.6 scope, dependencies, non-goals, and acceptance evidence;
- makes no source, test, schema, or backend behavior change.