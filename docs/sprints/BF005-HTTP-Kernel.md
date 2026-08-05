# BF005 - HTTP Kernel

## Objective

Build the application's HTTP Kernel infrastructure.

This sprint establishes the core HTTP layer that will serve as the foundation for all future request processing.

It introduces the application's Request abstraction and the infrastructure required for future middleware, routing, controller execution and request lifecycle management.

Only the foundational infrastructure is implemented in this sprint.

---

## Background

The following infrastructure has already been completed:

- BF001 - Backend Foundation
- BF002 - Infrastructure Foundation
- BF002.1 - Contracts Layer
- BF002.2 - Database Foundation
- BF003 - Base Repository
- BF004 - Service Container & Dependency Injection

This sprint completes the HTTP abstraction layer before implementing the first business module.

---

## Scope

Build the foundational infrastructure of the HTTP Kernel.

Suggested structure:

app/

    Http/

        Kernel.php

        Request.php

        JsonRequest.php

        HeaderBag.php

        InputBag.php

        UploadedFile.php

Only create classes that are required by the implementation.

Avoid unnecessary abstractions.

The Kernel may initially act as a lightweight composition point and will be expanded in future sprints.

---

## Kernel Responsibilities

The HTTP Kernel will become responsible for:

- Creating Request objects.
- Preparing HTTP infrastructure.
- Serving as the future entry point for middleware execution.
- Serving as the future entry point for controller dispatching.
- Coordinating the request lifecycle.

Only the infrastructure required for these responsibilities should be implemented in this sprint.

---

## Responsibilities

The HTTP layer should provide reusable access to:

- Query Parameters
- Form Parameters
- JSON Request Body
- Request Headers
- Uploaded Files
- Cookies
- Server Variables
- HTTP Method
- Request URI
- Content Type

---

## Design Principles

The HTTP Kernel must:

- Hide PHP superglobals.
- Be framework-independent.
- Be reusable.
- Be testable.
- Prefer immutable objects whenever practical.
- Keep HTTP concerns isolated from business logic.

---

## Request API

The Request object should expose methods similar to:

- input()
- query()
- json()
- header()
- cookie()
- file()
- method()
- uri()
- has()
- all()

Only expose methods that provide practical value.

Avoid unnecessary complexity.

---

## Service Container

Register the Request (and Kernel if required) through the existing Service Container.

Future controllers will receive Request through constructor injection.

---

## Superglobal Rule

After this sprint, direct usage of the following is prohibited outside the HTTP Kernel:

- $_GET
- $_POST
- $_REQUEST
- $_FILES
- $_COOKIE
- $_SERVER

Only the HTTP Kernel may access native PHP HTTP globals.

---

## Coding Standards

- PHP 8+
- declare(strict_types=1);
- PSR-12
- Constructor Injection
- Typed Properties
- Typed Parameters
- Typed Return Types
- SOLID Principles

---

## Non-Goals

This sprint must NOT:

- Implement Middleware execution
- Implement Controller dispatching
- Implement the complete Request Lifecycle
- Modify the Router
- Implement Validation
- Implement Authentication
- Implement Authorization
- Implement Business Logic
- Implement Organization Module
- Implement User Module

---

## Acceptance Criteria

- No application code accesses PHP superglobals directly.
- HTTP data is accessible through Request.
- JSON request bodies are supported.
- Headers are supported.
- Uploaded files are supported.
- Request is registered through the Service Container.
- The HTTP Kernel is prepared for future middleware integration.
- Infrastructure remains framework-independent.
- No business functionality is introduced.

---

## Deliverables

Only implement the HTTP Kernel infrastructure.

Do not implement business modules.

Stop immediately after BF005.