# Project Memory Index

## Decisions
- [`decisions/architecture-mode.md`](decisions/architecture-mode.md) — MODE_A locked for all features.

## Features
- [`features/01-project-setup-and-sanctum.md`](features/01-project-setup-and-sanctum.md) — Phase 1: Project setup, database, Sanctum, base API layer.
- [`features/02-authentication-and-profile.md`](features/02-authentication-and-profile.md) — Phase 2: Auth and user profile endpoints.
- [`features/03-database-schema-and-models.md`](features/03-database-schema-and-models.md) — Phase 3: Relational schema, 12 migrations, models, seeders.

## Domain
- [`domain/models-and-relations.md`](domain/models-and-relations.md) — Core models, relationships, and invariants.

## API Endpoints
- [`api/v1-health.md`](api/v1-health.md) — GET `/api/v1/health`
- [`api/v1-user-auth.md`](api/v1-user-auth.md) — `/api/v1/auth/*` (register, login, logout, me, updateProfile, forgotPassword, resetPassword)
