# BF003 - Base Repository

## Objective

Implement a reusable `BaseRepository` that provides generic CRUD functionality for all repositories in the system.

The BaseRepository will become the parent class for every future repository, eliminating duplicated database access logic and enforcing a consistent repository pattern across the application.

---

## Background

The following infrastructure has already been completed:

- Contracts Layer (BF002.1)
- Database Foundation (BF002.2)

This sprint builds on that foundation.

---

## Scope

Create the following structure:

app/Core/Repository/

    BaseRepository.php

---

## Dependencies

The BaseRepository must use the existing infrastructure.

Required dependencies:

- CrudRepositoryInterface
- DatabaseConnectionInterface
- QueryBuilderInterface
- BaseQueryBuilder

No direct SQL should be written inside BaseRepository.

---

## Responsibilities

Implement generic CRUD operations:

- all()
- find()
- create()
- update()
- delete()

The implementation must work for any table.

Child repositories should only provide repository metadata.

The BaseRepository must not know anything about business modules.

The table name, primary key and any module-specific behavior will be supplied by child repositories in future sprints.

---

## Constructor

Inject through Dependency Injection:

- DatabaseConnectionInterface
- QueryBuilderInterface

Never instantiate dependencies manually.

---

## Repository Hooks

Provide extension points for child repositories.

Default implementations should perform no action.

Required hooks:

protected function beforeCreate(array $data): array;

protected function beforeUpdate(array $data): array;

protected function afterCreate(array $record): void;

protected function afterUpdate(array $record): void;

protected function afterDelete(int|string $id): void;

---

## Design Principles

The BaseRepository:

- implements CrudRepositoryInterface
- delegates database work to QueryBuilder
- contains no business rules
- contains no validation
- contains no HTTP logic
- remains completely reusable

---

## Error Handling

The repository should:

- Throw meaningful exceptions.
- Never silently ignore database failures.
- Never return inconsistent results.

---

## Coding Standards

- PHP 8+
- declare(strict_types=1);
- PSR-12
- PHPDoc
- Typed properties
- Typed parameters
- Typed return values

---

## Out of Scope

Do NOT create:

- OrganizationRepository
- UserRepository
- PropertyRepository
- Controllers
- Services
- Validators
- Business Rules

---

## Acceptance Criteria

- BaseRepository compiles successfully.
- CRUD methods are generic.
- QueryBuilder is used for every operation.
- No duplicated CRUD logic exists.
- Child repositories only need to define table metadata.
- Ready for BF004.

---

## Deliverables

Create:

app/Core/Repository/BaseRepository.php

No additional modules should be created.

Stop after completing this sprint.