# ADR-001: Organization Hierarchy

Status: Approved

## Context

The platform requires one consistent business hierarchy for the implemented Organization and
Franchise scope and the planned Partner Agency scope.

## Decision

- The System Organization is the single highest business entity.
- A Franchise belongs to the System Organization.
- A Partner Agency belongs to one Franchise.
- The approved hierarchy is Organization -> Franchise -> Partner Agency.
- Future Users belong to the appropriate business organization within this approved hierarchy.

## Scope

This decision records the approved hierarchy only. It does not approve BF008 implementation details,
new database fields, API contracts, permissions, authentication behavior, or additional hierarchy levels.
