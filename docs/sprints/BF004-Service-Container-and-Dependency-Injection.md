# BF004 - Service Container & Dependency Injection

## Objective

Complete the application's Dependency Injection infrastructure by turning the Service Container into the central composition root of the application.

After this sprint, all infrastructure services should be resolved through the container instead of being manually instantiated.

---

## Background

The following foundation has already been completed:

- BF001 - Backend Foundation
- BF002 - Infrastructure Foundation
- BF002.1 - Contracts Layer
- BF002.2 - Database Foundation
- BF003 - Base Repository

This sprint completes the infrastructure required before implementing the first business module.

---

## Scope

Implement the dependency registration layer for the application.

The Service Container must become responsible for creating and resolving infrastructure services.

---

## Required Registrations

Register the following services through the container:

- Logger
- DatabaseManager
- DatabaseConnectionInterface
- QueryBuilderInterface
- BaseQueryBuilder
- ExceptionHandler
- Router

Use singleton registration where appropriate.

---

## Dependency Injection

The container must support:

- Constructor Injection
- Nested Dependency Resolution
- Interface-to-Implementation Binding
- Singleton Resolution

No manual dependency construction should exist outside the bootstrap process.

---

## Bootstrap

Use the existing bootstrap architecture.

Only make the minimum changes required to register and resolve infrastructure services through the Service Container.

Do not redesign the bootstrap process.

The bootstrap file should focus on:

- Loading configuration
- Creating the container
- Registering providers
- Booting the application

The bootstrap should not contain business logic.

---

## Design Principles

The Service Container should become the application's composition root.

Infrastructure services must be resolved from the container.

Future business modules must rely on constructor injection.

---

## Coding Standards

- PHP 8+
- strict_types
- PSR-12
- Constructor Injection
- Typed Properties
- Typed Parameters
- Typed Return Types
- SOLID Principles

---

## Out of Scope

Do NOT implement:

- Organization Module
- User Module
- Property Module
- Controllers
- Services
- Business Logic
- Authentication
- Authorization

---

## Acceptance Criteria

- Infrastructure services are registered through the container.
- Interface bindings work correctly.
- Singleton services resolve correctly.
- Constructor injection works automatically.
- Nested dependencies resolve correctly.
- Bootstrap remains clean and focused.
- No business functionality is introduced.

---

## Deliverables

Only modify infrastructure files where necessary.

Possible files include:

- app/Core/Container.php
- app/Providers/AppServiceProvider.php
- bootstrap/app.php

No business modules should be created.

Stop after completing BF004.

---

## Non-Goals

This sprint must NOT:

- Redesign the Container.
- Replace the existing bootstrap architecture.
- Introduce new architectural patterns.
- Modify business architecture.
- Create business modules.
- Add application features.