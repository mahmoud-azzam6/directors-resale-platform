# Admin Design Foundation

## Status

**APPROVED FOUNDATION**

This document defines the initial visual and implementation foundation for the Directors Resale Platform Admin UI.

It is intentionally strong enough to produce a polished, presentation-ready interface now, while remaining flexible enough to adopt a future custom Figma design without rewriting application logic.

---

## Brand Reference

Company logo:

https://directorsoman.com/wp-content/uploads/2025/12/logo.svg

The supplied SVG uses two core brand colors:

- Brand Gold: `#CE9821`
- Brand Charcoal: `#4D4D4D`

These colors should inform the initial Admin UI, but the interface must not become visually dependent on hard-coded color values throughout components.

All brand values must be centralized through design tokens.

---

## Design Goal

The initial Admin UI must be:

- professional
- premium
- modern
- restrained
- trustworthy
- clean
- spacious
- suitable for a real-estate/franchise management platform
- acceptable for client presentation and internal daily use

It must NOT look like:

- a generic Bootstrap admin template
- a developer dashboard
- a crypto dashboard
- a gaming interface
- an excessively dark luxury theme
- a gold-heavy visual treatment
- a placeholder/prototype

The goal is a polished neutral-premium enterprise interface.

---

## Design Philosophy

Use the Directors brand as an accent, not as decoration.

The Admin UI should primarily use neutral surfaces and typography, with the Directors gold used deliberately for:

- primary actions
- selected navigation states
- active indicators
- subtle highlights
- key focus states

The charcoal should be used for:

- primary text
- navigation
- strong interface anchors

Avoid large areas of saturated gold.

---

## Initial Visual Direction

Recommended visual direction:

- warm white / very light neutral application background
- white elevated surfaces
- charcoal text and navigation
- Directors gold accent
- restrained borders
- subtle shadows
- medium-radius components
- generous spacing
- strong information hierarchy

The UI should feel modern and premium without becoming ornamental.

---

## Design Tokens

All visual values must be tokenized.

Suggested starting tokens:

```css
:root {
  --brand-gold: #CE9821;
  --brand-charcoal: #4D4D4D;

  --background: #F7F7F5;
  --surface: #FFFFFF;
  --surface-muted: #F1F1EE;

  --text-primary: #292929;
  --text-secondary: #666666;
  --text-muted: #8A8A8A;

  --border: #E5E5E1;
  --border-strong: #D4D4CF;

  --success: #16815D;
  --warning: #B7791F;
  --danger: #B42318;
  --info: #2563EB;

  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;
}
```

These are starting values, not permanent brand-law constants.

If a future approved design changes them, the frontend architecture must allow replacement without changing feature logic.

---

## Typography

Use a clean professional sans-serif family compatible with the selected Next.js setup.

Requirements:

- strong readability
- clear table readability
- clear form readability
- good Arabic compatibility must remain possible later
- typography must be controlled centrally

Do not couple feature components to a specific font implementation.

Typography hierarchy should include:

- page title
- section title
- card title
- body
- small/meta text
- table text
- labels
- helper/error text

---

## Spacing

Use a consistent spacing scale.

Avoid one-off arbitrary margins in feature pages.

Prefer reusable layout primitives for:

- page padding
- card padding
- section gaps
- field spacing
- table spacing
- action groups

---

## Component Philosophy

The project owns the visual component source.

Use shadcn-style or equivalent source-owned primitives where useful.

The application must not depend on an opaque visual theme that is difficult to restyle later.

Foundation components should include:

- Button
- Input
- Label
- Select
- Checkbox
- Textarea
- Card
- Badge
- Avatar
- Dropdown Menu
- Dialog
- Alert Dialog
- Sheet
- Separator
- Skeleton
- Alert
- Toast
- Tooltip
- Table/DataTable foundation

---

## Component Variants

Variants must express semantic purpose, not brand implementation details.

Examples:

```text
Button:
primary
secondary
outline
ghost
destructive

Badge:
default
success
warning
danger
muted
```

Feature code should use:

```tsx
<Button variant="primary" />
```

not hard-coded visual classes that duplicate the current design.

---

## Designer Handoff Requirement

The frontend must support a future dedicated product designer.

A future designer may provide:

- Figma files
- revised color system
- revised typography
- revised spacing
- revised radius/shadows
- redesigned sidebar/header
- redesigned tables/forms/cards
- component states
- responsive behavior

Implementing that design must NOT require rewriting:

- authentication
- authorization
- API clients
- TanStack Query hooks
- business logic
- form submission logic
- route protection
- backend integration
- TypeScript domain models

Visual concerns must remain separated from feature/business concerns.

---

## Figma Mapping

Future Figma components should map cleanly to the project component system.

Example:

```text
Figma Primary Button
        ↓
components/ui/Button.tsx
        ↓
all feature screens
```

Not:

```text
Figma design
        ↓
custom page-specific CSS
        ↓
duplicated components
```

---

## Admin Layout Direction

Initial layout:

```text
┌─────────────────────────────────────────────┐
│ Header                                      │
├──────────────┬──────────────────────────────┤
│ Sidebar      │ Page Content                 │
│              │                              │
│ Dashboard    │ Page Header                  │
│ Organizations│ Breadcrumb / Actions         │
│ Franchises   │                              │
│ Agencies     │ Main Content                 │
│ Users        │                              │
│ Positions    │                              │
│ Permissions  │                              │
└──────────────┴──────────────────────────────┘
```

Sidebar should feel premium and calm.

Do not create visually noisy navigation.

---

## Sidebar

The sidebar should:

- use Directors branding subtly
- clearly show selected state
- support icons
- support permission-aware items
- support future collapsible groups
- support future responsive/mobile drawer mode

Brand gold may be used for selected indicators or restrained active states.

---

## Header

The header should support:

- page/context area
- current Organization
- current User
- Position
- User menu
- Logout

Do not introduce notifications, global search, or unnecessary controls in AF001.

---

## Tables

Tables will become a major interface pattern.

The table foundation should be prepared for:

- clear row density
- sticky/clear headers where useful
- status badges
- row actions
- loading skeletons
- empty states
- future filtering
- future sorting
- future pagination

Do not build heavy table infrastructure in AF001 beyond the reusable foundation.

---

## Forms

Forms must use consistent:

- labels
- field heights
- help text
- validation states
- error states
- disabled states
- focus states

React Hook Form + Zod is the frontend UX validation layer.

Backend validation remains authoritative.

---

## Status Language

Use consistent visual semantics.

Examples:

- Active -> success
- Inactive -> muted
- Warning -> warning
- Error/destructive -> danger

Do not use brand gold as an error or success color.

---

## Accessibility

Initial components should support:

- visible focus states
- keyboard navigation where applicable
- semantic labels
- reasonable contrast
- disabled state clarity

Do not sacrifice accessibility for visual minimalism.

---

## Responsive Foundation

AF001 should provide a sensible responsive layout foundation.

Desktop/laptop is the primary administration environment.

Still ensure:

- sidebar can become drawer/sheet
- header remains usable
- forms do not overflow
- cards stack appropriately

Do not attempt a complete mobile product redesign in AF001.

---

## Motion

Keep motion subtle.

Allowed:

- small menu transitions
- drawer transitions
- dialog transitions
- gentle hover/focus feedback

Avoid:

- decorative animation
- excessive page transitions
- attention-seeking effects

---

## Neutrality Rule

The initial visual direction is a real usable design, not an unstyled placeholder.

However, it remains a **replaceable visual skin over stable frontend architecture**.

This is the central design requirement.

---

## Initial Design Acceptance

The AF001 interface should be acceptable to show to management without apology.

It should appear intentional, polished, and branded.

At the same time, no implementation decision may prevent a future designer from replacing the visual language cleanly.

---

## Non-Goals

This foundation does not define the final:

- marketing website
- CRM visual design
- property card language
- analytics chart system
- mobile app design
- public listing design
- complete design-system documentation

Those evolve with later product domains or an approved dedicated design phase.
