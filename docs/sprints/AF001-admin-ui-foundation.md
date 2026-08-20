# AF001 - Admin UI Foundation

## Status

**APPROVED DEVELOPMENT SPRINT**

Phase:

**Admin Frontend Foundation**

Depends on:

- BF006 - Organization Management
- BF007 - Franchise Management
- BF008 - Partner Agency Management
- BF009 - User Management
- BF010 - Authentication Foundation
- BF011 - Dynamic Positions
- BF012 - Permissions & Authorization

Design reference:

- `docs/frontend/ADMIN_DESIGN_FOUNDATION.md`

---

## Objective

Implement the first real Admin UI foundation for the Directors Resale Platform.

AF001 must produce a polished, management-presentable Admin UI while keeping visual concerns replaceable so a future dedicated designer can supply a custom Figma design without requiring frontend business/integration rewrites.

AF001 introduces:

- Next.js frontend application
- TypeScript
- Tailwind CSS
- design-token foundation
- reusable source-owned UI component layer
- API client architecture
- secure authentication bridge
- real Login UI
- Logout flow
- protected Admin routes
- current authenticated User context
- current authorization context
- permission-aware navigation
- Admin layout
- Dashboard shell
- global loading/error/403 behavior

AF001 must NOT implement complete CRUD screens for Organizations, Franchises, Partner Agencies, Users, Positions, or Permissions.

---

## Brand Reference

Use the Directors company logo as the initial visual brand reference:

https://directorsoman.com/wp-content/uploads/2025/12/logo.svg

The supplied SVG contains the primary visual colors:

- Directors Gold: `#CE9821`
- Directors Charcoal: `#4D4D4D`

Use these through centralized tokens only.

Do not scatter hard-coded brand colors across feature components.

---

## Design Quality Requirement

The AF001 UI is NOT a wireframe and NOT an intentionally plain developer shell.

It must be visually acceptable for management presentation and real internal use.

Target character:

- premium
- clean
- modern
- restrained
- trustworthy
- professional
- real-estate/franchise appropriate

Avoid:

- generic Bootstrap admin appearance
- excessive gold
- overly dark luxury dashboards
- crypto/gaming visual language
- fake metrics
- decorative clutter

---

## Designer Handoff Requirement

AF001 must remain ready for a future custom designer.

A future Figma design must be implementable primarily by changing:

- tokens
- typography
- UI primitives
- layout components
- component variants
- feature composition/styling

without rewriting:

- authentication
- authorization
- API clients
- TanStack Query logic
- routing
- form submission logic
- backend integrations
- business/domain types

The current design is a polished reference design, not an irreversible final visual identity.

---

## Architecture

Development architecture:

```text
Browser
   ↓
Next.js Admin Frontend
   ↓
Secure Server-Side API Bridge
   ↓
PHP REST API
   ↓
MySQL
```

The PHP backend remains authoritative for:

- Authentication
- Authorization
- Organization scope
- Validation
- Business rules
- Persistence

---

## Frontend Stack

Use:

- Next.js
- TypeScript
- App Router
- Tailwind CSS
- source-owned / shadcn-style reusable components
- TanStack Query
- React Hook Form
- Zod
- Lucide Icons

Do NOT use Bootstrap.

Do NOT add another large UI framework.

---

## Project Location

Create:

```text
frontend/
```

Expected structure:

```text
frontend/
├── app/
├── components/
│   ├── ui/
│   └── layout/
├── features/
├── hooks/
├── lib/
│   └── api/
├── services/
├── styles/
├── types/
├── public/
└── ...
```

Keep frontend and PHP backend source separated.

---

## Design Tokens

Centralize the visual system.

At minimum establish tokens for:

- brand gold
- brand charcoal
- background
- surface
- muted surface
- primary text
- secondary text
- border
- success
- warning
- danger
- info
- radius
- shadow/elevation where needed

Start from `ADMIN_DESIGN_FOUNDATION.md`.

Do not treat initial token values as impossible to replace later.

---

## Typography

Create centralized typography behavior.

Do not style typography independently per feature.

Choose a professional sans-serif suitable for admin interfaces and future Arabic compatibility.

---

## Next.js Routing

Use App Router.

Public:

```text
/login
```

Protected:

```text
/admin
/admin/organizations
/admin/franchises
/admin/partner-agencies
/admin/users
/admin/positions
/admin/permissions
```

AF001 may create placeholders for module routes only.

---

## Authentication Strategy

Never persist the BF010 bearer token in:

```text
localStorage
sessionStorage
```

Use the approved server-side HttpOnly cookie bridge.

Flow:

```text
Login Form
    ↓
Next.js server-side handler
    ↓
PHP POST /auth/login
    ↓
Raw Bearer Token
    ↓
HttpOnly Cookie
```

Client-side JavaScript must not require direct access to the raw backend token.

---

## Login

Implement `/login`.

Fields:

- Email
- Password

Use:

- React Hook Form
- Zod

On success:

```text
PHP /auth/login
→ HttpOnly cookie
→ /admin
```

On invalid credentials:

- show safe generic error
- do not leak backend-sensitive details

The Login page must use the Directors visual foundation and be presentation-ready.

---

## Logout

Logout:

```text
Admin UI
→ Next server-side handler
→ PHP /auth/logout
→ clear HttpOnly cookie
→ /login
```

Local cookie must be safely cleared even if backend token is already invalid.

---

## Protected Routes

Unauthenticated:

```text
/admin/*
→ /login
```

Do not rely only on client-side `useEffect` redirects.

Authenticated access should be validated server-aware.

---

## Authenticated Login Route

Where practical:

```text
authenticated /login
→ /admin
```

---

## Current User

Resolve basic authenticated User via:

```text
GET /auth/me
```

Do not derive authentication identity from frontend assumptions.

---

## Auth Context

The Admin UI needs:

- user
- organization
- position
- effective permission codes

If the backend lacks a single safe endpoint, introduce the smallest backend UI-enabling endpoint:

```text
GET /auth/context
```

This endpoint:

- requires Authentication
- requires no additional business permission
- reuses BF010/BF012 services
- exposes only safe context data
- never exposes password hash
- never exposes raw token
- never exposes token hash

Suggested response:

```text
user
organization
position
permissions
```

---

## API Client

Centralize backend communication.

Suggested:

```text
frontend/lib/api/
├── client.ts
├── auth.ts
├── organizations.ts
├── franchises.ts
├── partner-agencies.ts
├── users.ts
├── positions.ts
└── permissions.ts
```

AF001 fully implements common/auth/context infrastructure only.

Do not perform arbitrary feature `fetch()` calls inside UI components.

---

## API Configuration

Use environment configuration.

Example:

```text
PHP_API_BASE_URL=http://localhost/directors-resale-platform/public
```

Create:

```text
frontend/.env.example
```

Do not commit secrets.

---

## API Errors

Centralize behavior for:

- 401
- 403
- 404
- 422
- 500

Expected:

```text
401 → invalid session / login
403 → Forbidden UI
404 → not found
422 → validation
500 → safe generic error
```

Never show raw production exception data.

---

## TanStack Query

Configure a centralized QueryClient provider.

Do not normalize the project around repeated `useEffect + fetch` server-state patterns.

---

## Forms

Use Login as the reference implementation for:

```text
React Hook Form
+
Zod
```

Backend remains authoritative.

---

## UI Component Layer

Create source-owned reusable UI components required by AF001.

At minimum:

- Button
- Input
- Label
- Card
- Badge
- Avatar
- Dropdown Menu
- Separator
- Skeleton
- Alert
- Toast
- Sheet/mobile navigation primitive

Add Dialog/Tooltip only where genuinely needed.

Components should expose semantic variants instead of page-specific styling.

---

## Admin Layout

Implement real protected layout containing:

- Sidebar
- Header
- Main Content Area
- Page Container
- Current User
- Organization context where useful
- Logout

The layout must be polished and usable.

---

## Sidebar

Initial targets:

- Dashboard
- Organizations
- Franchises
- Partner Agencies
- Users
- Positions
- Permissions

Use permission-aware visibility.

Examples:

```text
organizations.view → Organizations
franchises.view → Franchises
partner_agencies.view → Partner Agencies
users.view → Users
positions.view → Positions
permissions.view → Permissions
```

Dashboard visible to authenticated Users.

Do NOT control navigation via Position names.

---

## Header

Include:

- current page/context area
- User identity
- Organization
- Position where useful
- User menu
- Logout

Do NOT add notifications or global search in AF001.

---

## Dashboard

Implement `/admin`.

Use real backend context only.

Allowed content:

- welcome message
- User name
- Organization
- Position
- permission count
- available module shortcuts

Do NOT fabricate:

- sales
- revenue
- listings
- deals
- charts
- KPIs

The page must still look polished without fake business data.

---

## Placeholder Module Routes

Create polished placeholder pages for:

- `/admin/organizations`
- `/admin/franchises`
- `/admin/partner-agencies`
- `/admin/users`
- `/admin/positions`
- `/admin/permissions`

They exist to prove routing/navigation/layout.

Do not build full CRUD.

---

## Permission-Aware UX

Navigation and future UI affordances use backend permission codes.

Frontend permission checks are UX only.

Backend BF012 remains authoritative.

---

## Forbidden UI

Provide reusable polished `403 Forbidden` state.

Authenticated forbidden Users must not be redirected to Login merely because access is denied.

---

## Loading / Error / Empty Foundations

Provide reusable:

- loading/skeleton state
- generic error state
- 403 state
- sensible empty-state pattern

These should follow the design foundation.

---

## Not Found

Use Next.js Not Found conventions with the Admin visual language.

---

## Types

At minimum:

- AuthUser
- Organization
- Position
- AuthContext
- PermissionCode
- ApiResponse
- ApiError

Avoid uncontrolled `any`.

---

## Server vs Client Components

Prefer Server Components where practical.

Use Client Components for:

- forms
- TanStack Query hooks
- dropdowns
- mobile navigation
- interactive state

Do not unnecessarily mark large trees `"use client"`.

---

## Session Behavior

Verify:

```text
logged out /admin → /login
logged in /login → /admin where practical
expired/revoked session → clear cookie + /login
authenticated forbidden → 403 UI
```

---

## Security Verification

Verify:

- no token in localStorage
- no token in sessionStorage
- raw bearer unavailable to ordinary client JavaScript
- password hash never reaches frontend
- token hash never reaches frontend
- backend Authorization remains authoritative

---

## CORS / Development

Prefer server-side backend communication to minimize browser-to-PHP CORS exposure.

Do not weaken backend production security globally for development convenience.

---

## Backend Change Boundary

AF001 may make only UI-enabling backend changes.

Expected:

```text
GET /auth/context
```

if required.

Do not modify existing domain business rules.

Do not add another backend business module.

---

## Responsive Foundation

Primary target:

- desktop
- laptop

Still provide:

- responsive sidebar/drawer behavior
- non-overflowing forms
- usable page padding
- reasonable smaller-screen structure

Full mobile product design is out of scope.

---

## Accessibility

Foundation should provide:

- visible focus
- semantic labels
- keyboard-compatible controls where applicable
- reasonable color contrast
- clear disabled/error states

---

## Motion

Use subtle motion only.

Avoid decorative animation.

---

## Frontend Quality

Must pass:

- TypeScript
- build
- lint if configured

For touched PHP:

- PHP syntax validation

Do not leave avoidable application warnings/errors.

---

## Git Boundaries

Do not commit:

- node_modules
- build artifacts
- local environment secrets

Use one package manager consistently.

---

## Documentation

After successful verification update only required docs.

Record:

- Admin Frontend phase
- stack
- `frontend/` architecture
- design foundation
- brand reference
- secure auth bridge
- auth context endpoint if introduced
- Admin layout
- permission-aware navigation
- verification

Do not mark AF002/AF003 implemented.

---

## Non-Goals

Do NOT implement complete:

- Organization CRUD UI
- Franchise CRUD UI
- Partner Agency CRUD UI
- User CRUD UI
- Position CRUD UI
- Permission assignment UI
- CRM
- Property
- Listing
- Deals
- Commissions
- Reports
- Analytics
- Notifications
- dark mode
- final mobile product
- fake dashboard metrics

---

## Acceptance Criteria

### Foundation

- `frontend/` exists
- Next.js + TypeScript
- App Router
- Tailwind
- TanStack Query
- React Hook Form
- Zod
- Lucide
- Bootstrap absent
- centralized design tokens
- source-owned UI primitives

### Design

- initial UI is polished enough for management presentation
- Directors Gold/Charcoal are used through tokens
- gold is restrained
- layout looks intentional, not generic/developer-made
- design is not tightly coupled to feature/business logic
- future Figma redesign can replace visual layer without business rewrite

### Authentication

- real BF010 Login works
- secure HttpOnly token bridge works
- logout works
- `/admin` protected
- invalid/expired session returns to Login
- token is not in JS storage

### Context

- real User resolved
- Organization resolved
- Position resolved
- permissions resolved
- no sensitive hashes exposed

### UX Authorization

- Sidebar is permission-aware
- Position names do not control permissions
- 403 state works
- backend security remains authoritative

### Layout

- Sidebar
- Header
- User context
- Logout
- responsive foundation

### Dashboard

- real context only
- no fake KPIs
- professional presentation

### Placeholder Routes

All approved placeholder routes render within protected layout.

### Quality

- build passes
- TypeScript passes
- lint passes if configured
- touched PHP syntax passes
- `git diff --check` passes
- no AF002/AF003 CRUD implemented

---

## Testing

Verify real:

```text
Login
→ Admin
→ Context
→ Permission-aware navigation
→ Logout
```

Where practical verify:

- broad-permission User
- restricted User
- authenticated User without business permissions

Verify design/layout at common desktop/laptop viewport sizes.

Clean temporary backend test data.

---

## Deliverables

- Next.js frontend foundation
- design-token foundation
- source-owned component foundation
- secure auth bridge
- Login
- Logout
- `/auth/context` backend enhancement if required
- protected Admin layout
- permission-aware Sidebar
- Header/User context
- Dashboard
- placeholders
- loading/error/403 foundation
- build/tooling
- docs

---

## Stop Condition

Stop after AF001 implementation and verification.

Do not implement AF002 or AF003.

Do not implement another backend/domain sprint.

Return AF001 implementation and verification only.
