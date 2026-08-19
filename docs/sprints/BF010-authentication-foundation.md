# BF010 - Authentication Foundation

## Status

**APPROVED DEVELOPMENT SPRINT**

Milestone: **Milestone 2 - Core Business**

Depends on:

* BF006 - Organization Management
* BF007 - Franchise Management
* BF008 - Partner Agency Management
* BF009 - User Management

---

## Objective

Implement the first Authentication layer of the Directors Resale Platform.

BF010 introduces secure login and authenticated API identity for existing active Users.

The purpose of this sprint is to establish authentication only.

BF010 must NOT implement Authorization, Roles, Permissions, Positions, Organization access policies, or team visibility rules.

Authentication answers:

**Who is the current User?**

Authorization will be implemented separately and answers:

**What is this User allowed to do?**

---

## Background

The platform currently provides:

* Application Bootstrap
* Dependency Injection
* Database Layer
* Query Builder
* Base Repository
* HTTP Kernel
* Request abstraction
* Router
* Standardized JSON responses
* Organization Management
* Franchise Management
* Partner Agency Management
* User Management

BF009 introduced the staged `users` table with:

* id
* organization_id
* full_name
* email
* phone
* status
* created_at
* updated_at

BF009 intentionally did NOT implement:

* passwords
* authentication
* tokens
* sessions
* roles
* permissions

BF010 now adds only the minimum authentication infrastructure required to securely identify Users.

---

## Scope

Implement:

* User authentication credentials
* Login
* Logout
* Token-based authenticated identity
* Current authenticated User endpoint
* Authentication middleware/integration
* Token revocation
* Active-user authentication enforcement

Do not implement Authorization.

---

## Authentication Model

Authentication must use the existing internal User entity.

A login identity is based on:

```text
email
```

The User email established in BF009 remains globally unique.

Credentials must not duplicate User identity data.

---

## Password Storage

Passwords must never be stored in plain text.

Use PHP's secure password hashing facilities:

```php
password_hash()
password_verify()
```

Do not implement custom cryptography.

Do not store reversible passwords.

---

## Database

BF010 must inspect the current schema before creating migrations.

### User Credential Field

Add the minimum credential field required for authentication:

```text
password_hash
```

to the existing:

```text
users
```

table.

Do NOT add a plain:

```text
password
```

column.

Create a new BF010 migration for this schema change.

Do not modify the BF009 migration.

---

## Authentication Tokens

Create a dedicated authentication token table.

Suggested table:

```text
auth_tokens
```

The table must support:

* token ownership
* secure token storage
* expiration
* revocation/lifecycle
* creation timestamp

At minimum:

* id
* user_id
* token_hash
* expires_at
* revoked_at
* created_at

Use proper foreign keys.

`user_id` references:

```text
users.id
```

Index User and token lookup fields appropriately.

---

## Token Security

Never store raw bearer tokens in the database.

Generate a cryptographically secure random token.

Return the raw token to the client only when it is created.

Store only a secure hash of the token in the database.

When authenticating:

1. Receive Bearer token.
2. Hash/derive the lookup representation according to the approved implementation.
3. Find the token record.
4. Validate token lifecycle.
5. Resolve the User.
6. Validate User status.

Do not expose token hashes through APIs.

---

## Token Lifecycle

A token is valid only when:

* it exists
* it belongs to a valid User
* it is not revoked
* it is not expired
* the User is active

Inactive Users must not authenticate successfully.

Logout must revoke the current authentication token.

Do not physically delete token history merely to logout.

---

## Token Expiration

Tokens must have a defined expiration period.

Use a clear application-level configuration rather than scattering expiration values through controllers/services.

Do not implement refresh tokens in BF010.

---

## User Status

Only Users where:

```text
status = active
```

may login or use authenticated endpoints.

If an authenticated User later becomes inactive, existing tokens must no longer grant authenticated access.

---

## Login Validation

Validate at minimum:

* email is required
* email format is valid
* password is required
* User exists
* User is active
* password matches stored hash

Invalid credentials must return a generic authentication error.

Do not expose whether:

* the email exists
* the password was incorrect

through distinguishable credential-error messages.

---

## Password Setup

BF009 Users may already exist without authentication credentials.

BF010 must provide a controlled staged mechanism for setting the initial password required for testing and current development.

Do NOT create public self-registration.

Do NOT create password reset.

Do NOT create email verification.

The implementation must not silently assign default passwords.

If an existing User has no `password_hash`, login must fail until a credential is explicitly established through the BF010-approved development/admin mechanism.

Keep this mechanism narrow and document it clearly.

---

## API Endpoints

Implement:

```text
POST /auth/login
POST /auth/logout
GET  /auth/me
```

### POST /auth/login

Request:

* email
* password

Successful response returns:

* standardized JSON response
* authentication token
* basic authenticated User information required by the current API convention

Do not return:

* password hash
* token hash

### POST /auth/logout

Requires authentication.

Revokes the current token.

Logout must be idempotent according to existing API conventions where practical.

### GET /auth/me

Requires authentication.

Returns the current authenticated active User using the existing standardized response format.

Do not introduce authorization or permission data yet.

---

## Authentication Middleware

Implement authentication as reusable infrastructure.

Authentication must not be manually duplicated inside every controller.

Introduce the smallest reusable middleware / routing integration compatible with the existing custom PHP architecture.

Responsibilities:

* extract Bearer token
* validate token
* resolve authenticated User
* attach authenticated identity to the request/context
* reject unauthenticated access

Do not redesign the Router.

Do not introduce a third-party framework.

---

## Public Routes

The following must remain public in BF010:

```text
GET /api/v1/health
POST /auth/login
```

Other existing business routes should be protected by the authentication layer where practical and compatible with current architecture.

At minimum, BF010 must establish a clear protected-route mechanism that future endpoints can reuse.

Do not implement Authorization rules during route protection.

---

## Existing Business Routes

The project security principle requires business APIs to be authenticated.

BF010 should migrate existing business endpoints to authentication protection without changing their business logic:

* Organizations
* Franchises
* Partner Agencies
* Users

The authentication layer must only establish identity.

It must NOT decide whether the authenticated User has permission to access a specific Organization or record.

Those decisions belong to Authorization.

---

## Authentication Context

The authenticated User should be accessible through the existing Request/application context in a reusable manner.

Avoid:

* global variables
* static mutable authentication state
* controller-specific token parsing

The design must remain framework-independent.

---

## Repository

Create only the repositories required by authentication.

Expected responsibility:

* authentication token persistence
* token lookup
* token revocation
* User credential lookup where existing UserRepository cannot safely provide it

Do not duplicate the entire UserRepository.

---

## Service

Create an Authentication Service.

Responsibilities:

* credential verification
* login orchestration
* token generation
* token validation coordination
* logout/revocation
* current User resolution

Authentication business logic belongs in the Service layer.

No HTTP-specific behavior should live inside the Service.

---

## Controller

Create an Authentication Controller.

Responsibilities:

* receive authentication HTTP requests
* call Authentication Service
* return standardized JSON responses

Controllers must remain thin.

---

## Security Rules

BF010 must follow:

* secure password hashing
* cryptographically secure token generation
* hashed token persistence
* no credentials in logs
* no password hashes in responses
* no token hashes in responses
* generic invalid-login errors
* active User enforcement

Do not expose sensitive authentication information.

---

## No Hard Delete

Authentication records should preserve history where appropriate.

Logout/revocation should use lifecycle fields such as:

```text
revoked_at
```

rather than hard deleting tokens.

---

## Authentication vs Authorization Boundary

BF010 implements Authentication only.

Do NOT implement:

* Roles
* Permissions
* Dynamic Positions
* User Permissions
* Organization access policy
* Team Leader visibility
* Manager visibility
* Sales visibility
* Super Admin authorization
* action permissions
* permission middleware

Authentication proves User identity only.

---

## Password Reset Boundary

BF010 must NOT implement:

* forgot password
* reset password
* reset tokens
* reset email
* password history

These may be introduced later if approved.

---

## Registration Boundary

BF010 must NOT implement public registration.

Users remain created through the existing User Management API/process.

---

## Refresh Token Boundary

BF010 must NOT implement refresh tokens.

Use the staged access-token model only.

Refresh-token architecture requires a separate future decision if needed.

---

## Sessions Boundary

Do not introduce server-side browser sessions.

The backend remains REST API oriented.

---

## Coding Standards

* PHP 8+
* `declare(strict_types=1);`
* PSR-12
* SOLID Principles
* Typed Properties
* Typed Parameters
* Typed Return Types

Follow established BF001-BF009 architecture patterns.

---

## Non-Goals

BF010 must NOT implement:

* Authorization
* Roles
* Permissions
* Dynamic Positions
* Position assignment
* Team hierarchy
* User Profiles
* User Transfers
* Public registration
* Password reset
* Email verification
* OTP
* MFA
* Refresh tokens
* Social login
* OAuth providers
* API keys
* CRM
* Properties
* Listings
* Deals
* Commissions
* Frontend

---

## Acceptance Criteria

### Database

* `users.password_hash` exists.
* No plain password field exists.
* authentication token table exists.
* token table references `users.id`.
* raw tokens are not persisted.
* token expiration is persisted.
* revocation lifecycle is persisted.

### Login

* valid active User login works
* wrong password rejected
* unknown email rejected
* invalid email rejected
* missing email rejected
* missing password rejected
* inactive User rejected
* User without credential rejected
* raw token returned only on successful login

### Token Security

* persisted token differs from raw token
* valid token resolves User
* invalid token rejected
* expired token rejected
* revoked token rejected
* token belonging to inactive User rejected

### Current User

* `/auth/me` works with valid token
* `/auth/me` rejects missing token
* `/auth/me` rejects invalid token
* `/auth/me` does not expose password/token hashes

### Logout

* valid logout works
* current token becomes revoked
* revoked token cannot access protected endpoint
* database token row remains after logout

### Route Protection

* health remains public
* login remains public
* protected business endpoint rejects missing authentication
* protected business endpoint accepts a valid authenticated identity

### Regression

After authentication setup, verify existing functionality using authenticated requests where required:

* Organization
* Franchise
* Partner Agency
* User Management
* health endpoint
* Router
* standardized JSON responses

### Scope

* no Authorization implemented
* no Permissions implemented
* no Positions implemented
* no public registration
* no password reset
* no refresh tokens
* no future business module introduced

---

## Testing

Run database-backed BF010 acceptance testing.

Create temporary User authentication data where required.

Clean all temporary test data after verification.

Verify database token lifecycle directly.

No automated test framework should be introduced solely for BF010 if none currently exists.

Report unavailable automated coverage accurately.

---

## Documentation

After successful implementation and verification, update only the documentation required by the established project workflow.

Where applicable update:

* `AI_CONTEXT.md`
* `PROJECT_STATUS.md`
* `README.md`
* `CHANGELOG.md`
* `docs/MASTER_INDEX.md`
* `docs/database/DATABASE_CHANGELOG.md`
* `docs/database/schema/core.md`
* `docs/development/CURRENT_SYSTEM_STATE.md`
* `docs/development/IMPLEMENTATION_PLAN.md`
* `docs/development/SPRINT_LOG.md`
* relevant API/security documentation

Record:

* BF010 completion
* Authentication implementation
* credential storage strategy
* token strategy
* protected-route mechanism
* Authorization deferral
* test results

Do not select or design BF011 during BF010.

---

## Deliverables

BF010 deliverables are limited to:

* Authentication module/infrastructure
* User credential schema change
* authentication token migration
* login endpoint
* logout endpoint
* current User endpoint
* reusable authentication protection
* existing business-route authentication integration
* BF010 acceptance verification
* required documentation updates

---

## Stop Condition

Stop after BF010 is implemented and verified.

Do not implement:

* Authorization
* Permissions
* Positions
* Roles
* User Profiles
* Transfers
* CRM
* any future sprint

Do not select BF011.
