# ADR-018

## Title

Universal Audit Columns

---

## Status

Approved

---

## Context

The Directors Resale Platform requires complete accountability for all business data.

Every important business record should preserve information about:

- Who created it
- Who modified it
- Who approved it (if applicable)
- Who archived it (if applicable)

This supports auditing, reporting, security investigations, compliance and future AI analysis.

---

## Decision

Every persistent business table must include:

- created_at
- updated_at
- created_by
- updated_by

When applicable, tables should also include:

- approved_at
- approved_by
- archived_at
- archived_by

All *_by columns reference:

users.id

Reference Data tables follow the same rule.

No exceptions.

---

## Consequences

Advantages:

- Complete auditability
- Better reporting
- Better security
- AI can understand ownership history
- Consistent database design

Disadvantages:

- Additional foreign keys
- Slightly larger tables

The benefits outweigh the costs.

---

## Approved By

Architecture Team

---

## Date

YYYY-MM-DD