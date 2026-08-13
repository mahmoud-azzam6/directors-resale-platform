# BF006 - Organization Module

## Objective

Implement the first business module of the Directors Resale Platform.

This sprint introduces the Organization module as the highest business entity within the platform.

The purpose of this sprint is to validate that the completed foundation (BF001–BF005) can support real business modules without requiring architectural changes.

---

## Background

Milestone 1 has been completed.

The platform already provides:

- Application Bootstrap
- Service Container
- Dependency Injection
- Database Layer
- Query Builder
- Base Repository
- HTTP Kernel

This sprint begins Milestone 2 by introducing the first business domain.

---

## Scope

Implement the complete Organization module.

Suggested structure:

app/

    Modules/

        Organization/

            Controllers/

            Services/

            Repositories/

            Models/

            Validators/

Only create classes required by the implementation.

Avoid unnecessary abstractions.

---

## Organization

An Organization represents the highest business entity within the platform.

Examples include:

- Coldwell Banker Egypt
- Future Corporate Organizations

Organizations own and supervise all business entities beneath them.

The Organization module must remain generic and must not contain franchise-specific business rules.

---

## Business Hierarchy

The platform follows the business hierarchy below:

Organization

↓

Franchise

↓

Partner Agency

↓

Users

↓

Listings

↓

Deals

↓

Commissions

↓

Reports

Only the Organization level is implemented in this sprint.

The remaining levels will be introduced in future sprints.

---

## Organization Responsibilities

The Organization is responsible for:

- Managing its Franchises
- Supervising all Partner Companies
- Providing system-wide visibility
- Acting as the highest reporting level
- Becoming the parent of all future business entities

Future reporting will aggregate information from:

- Franchises
- Partner Companies
- Users
- Listings
- Deals
- Financial Data

Reporting is outside the scope of BF006.

---

## Responsibilities

The Organization module should provide:

- Organization CRUD
- Organization Validation
- Organization Persistence
- Organization Business Rules
- Organization API Endpoints

---

## Organization Model

The initial Organization entity should contain only:

- id
- name
- code
- organization_type
- status
- created_at
- updated_at

No additional fields should be introduced in this sprint.

---

## Validation Rules

Validate at minimum:

- name is required
- code is required
- code must be unique
- organization_type is required
- status is required

Validation should remain reusable.

---

## Business Rules

The Organization is the root business entity.

Only one Organization may own a Franchise.

Only one Franchise may own a Partner Agency.

Every future business entity must belong directly or indirectly to exactly one Organization.

Cross-organization data access is prohibited.

Detailed permission and ownership rules are outside the scope of BF006.

---

## API Endpoints

Implement:

GET    /organizations

GET    /organizations/{id}

POST   /organizations

PUT    /organizations/{id}

DELETE /organizations/{id}

Return standardized JSON responses using the existing infrastructure.

---

## Repository

Create:

OrganizationRepository

The repository must extend BaseRepository.

No custom SQL unless absolutely required.

---

## Service

Create:

OrganizationService

Responsibilities:

- Business validation
- CRUD operations
- Repository coordination

No infrastructure logic.

---

## Controller

Create:

OrganizationController

Responsibilities:

- Receive HTTP requests
- Call OrganizationService
- Return standardized JSON responses

Controllers must remain thin.

No business logic inside controllers.

---

## Routing

Register Organization routes using the existing Router.

Do not modify router architecture.

---

## Database

Create the organizations table.

Suggested columns:

- id
- name
- code
- organization_type
- status
- created_at
- updated_at

Use the existing database conventions.

---

## Design Principles

The module must:

- Use Dependency Injection
- Use Constructor Injection
- Extend BaseRepository
- Use the Service Layer
- Keep Controllers thin
- Keep Business Logic inside Services
- Remain framework-independent

---

## Coding Standards

- PHP 8+
- declare(strict_types=1);
- PSR-12
- SOLID Principles
- Typed Properties
- Typed Parameters
- Typed Return Types

---

## Non-Goals

This sprint must NOT implement:

- Franchises
- Partner Companies
- Users
- Authentication
- Authorization
- Roles
- Permissions
- Branches
- Properties
- Listings
- Deals
- Commissions
- Reporting
- Dashboards
- Notifications
- File Uploads

---

## Acceptance Criteria

- Organization CRUD works.
- Repository extends BaseRepository.
- Service contains business logic.
- Controller contains no business logic.
- Routes are registered.
- Database table exists.
- Standardized JSON responses are returned.
- No architectural changes are required.

---

## Deliverables

Implement only the Organization module.

Do not implement any other business modules.

Stop immediately after BF006.
