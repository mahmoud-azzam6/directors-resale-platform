# Changelog

All notable changes to Directors Resale Platform will be documented in this file.

---

# 2026-07-22

## Added

- Added BF001 backend foundation runtime.
- Added dependency injection container.
- Added service provider infrastructure.
- Added lazy PDO database manager.
- Added basic HTTP router.
- Added standardized JSON response object.
- Added exception handler for JSON error responses.
- Added `/api/v1/health` endpoint.

## Changed

- Refactored bootstrap flow to instantiate dependencies explicitly.
- Updated application configuration to expose the application version.

## Fixed

- None.

## Removed

- Removed `App::create()` bootstrap factory usage.
