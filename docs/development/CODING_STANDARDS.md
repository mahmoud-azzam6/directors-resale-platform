# Coding Standards

**Document:** CODING_STANDARDS.md

**Version:** 1.0

**Status:** Approved

---

# Purpose

This document defines the mandatory engineering standards for the Directors Resale Platform.

All contributors, whether human developers or AI assistants, must follow these standards.

These standards are mandatory unless superseded by an approved ADR.

---

# Engineering Principles

The project follows the following engineering principles:

- Architecture First
- Readability Over Cleverness
- Consistency Over Preference
- Reuse Before Create
- Single Responsibility Principle
- Explicit Over Implicit
- Scalability Before Convenience
- Security By Design

Every implementation must respect these principles.

---

# Architecture Principles

The platform follows a layered architecture.

```
Client
    ↓
Routes
    ↓
Controllers
    ↓
Services
    ↓
Repositories
    ↓
Database
```

Rules:

- Controllers coordinate requests only.
- Services contain business logic.
- Repositories access the database only.
- Models represent entities only.
- Controllers must never access the database directly.
- Business logic must never exist inside Controllers.
- Repositories must never call other repositories directly.
- Services may orchestrate multiple repositories.

---

# Project Structure

The project structure must remain consistent.

Example:

```
app/

Controllers/

Services/

Repositories/

Models/

Validation/

Middleware/

Helpers/

Config/

database/

migrations/

seeders/

routes/

tests/

storage/

public/
```

---

# Naming Conventions

Classes

- PascalCase

Example

CustomerService

PropertyRepository

Controllers

End with Controller

Repositories

End with Repository

Services

End with Service

Interfaces

Start with I

Example

IUserRepository

Database

snake_case

Tables

Plural

Example

organizations

users

properties

Columns

snake_case

Methods

camelCase

Variables

camelCase

Constants

UPPER_CASE

---

# Coding Style

- Follow PSR-12.
- Keep methods focused.
- Avoid deeply nested logic.
- Avoid duplicated code.
- Prefer composition over inheritance.
- Use strict typing where applicable.

---

# Dependency Injection

All services and repositories should be injected.

Avoid creating dependencies manually inside classes.

Example:

Good

```
UserService(UserRepository repository)
```

Bad

```
new UserRepository()
```

---

# Repository Pattern

Repositories are responsible only for data access.

Allowed:

- SELECT
- INSERT
- UPDATE
- DELETE
- Transactions (when necessary)

Repositories must never contain business rules.

---

# Service Layer

Services contain business rules.

Services may:

- call multiple repositories
- validate workflows
- coordinate transactions
- dispatch events

Services must never generate HTTP responses.

---

# Controller Layer

Controllers should:

- receive request
- validate request
- call service
- return response

Controllers must remain thin.

---

# Validation

Validation must be centralized.

Never duplicate validation rules.

Validation must occur before business logic execution.

---

# API Standards

All APIs must use:

```
/api/v1/
```

Response format must remain consistent.

Content type:

```
application/json
```

---

# API Response Format

Success

```json
{
    "success": true,
    "message": "",
    "data": {},
    "meta": {}
}
```

Error

```json
{
    "success": false,
    "error": {
        "code": "",
        "message": ""
    }
}
```

---

# Error Handling

Never expose internal exceptions.

Unexpected exceptions must be logged.

Clients receive standardized errors only.

---

# Database Rules

Database implementation must follow:

DATABASE_STANDARDS.md

Additional rules:

- Never use ENUM.
- Always use Foreign Keys where appropriate.
- Use BIGINT for internal identifiers.
- Use ULID for public identifiers.
- Use archive strategy instead of hard delete unless explicitly approved.

---

# Transactions

Transactions are required when:

- multiple tables are modified
- financial operations occur
- workflow consistency is required

Never leave partial updates.

---

# Security

Never trust client input.

Always validate.

Always authorize.

Escape output when necessary.

Never expose internal IDs publicly.

Public APIs should use ULIDs whenever possible.

---

# Logging

Log:

- unexpected exceptions
- failed authentication
- permission violations
- financial operations
- important business events

Never log passwords.

Never log sensitive tokens.

---

# Performance

Avoid N+1 queries.

Index searchable fields.

Paginate large datasets.

Cache reference data when appropriate.

Optimize only after measuring.

---

# AI Development Rules

AI assistants must follow all project documentation.

AI must NOT:

- change architecture
- modify database contracts
- introduce new dependencies
- duplicate code
- bypass service layer
- bypass repository layer
- skip validation
- skip authorization
- generate undocumented APIs

AI SHOULD:

- reuse existing code
- keep functions cohesive
- follow project naming
- preserve backward compatibility
- generate maintainable code
- update documentation when required

When documentation conflicts with implementation, documentation is the source of truth until an approved ADR updates it.

---

# Git Workflow

Feature branches:

feature/<feature-name>

Bug fixes:

fix/<issue>

Documentation:

docs/<topic>

Commits should be descriptive.

Examples:

feat:

fix:

docs:

refactor:

test:

---

# Documentation

Implementation affecting architecture must update documentation.

Major architectural decisions require an ADR.

Database changes require database documentation updates.

---

# Definition of Done

A feature is considered complete only if:

✓ Business rules implemented

✓ Validation completed

✓ Authorization completed

✓ Repository completed

✓ Service completed

✓ Controller completed

✓ Routes completed

✓ API documented

✓ Error handling completed

✓ Logging implemented

✓ Tests added (when testing phase begins)

✓ Documentation updated when necessary

Only then is a feature considered Done.