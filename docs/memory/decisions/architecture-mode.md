# Architecture Mode: MODE_A

## Decision
- The project is configured with **MODE_A** (Full Repository Pattern).
- Flow: `Controller → FormRequest → DTO → Service → Repository → Model`.
- Decided on: 2026-10-27 by developer choice.
- Binding service provider: `App\Providers\RepositoryServiceProvider`.

