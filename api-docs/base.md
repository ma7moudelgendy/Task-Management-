# API Base Documentation & Envelope Specification

All API endpoints follow a standardized response envelope.

## Transport & Status Codes

- **HTTP Status:** Always `200 OK` for business outcomes.
- **Client Flow Routing:** Clients branch exclusively on the body `key`.
- **Envelope Code:** `code` is an integer hint (e.g., 200, 400, 401, 403, 404, 422, 500).

## Envelope Structure

```json
{
  "key": "success",
  "code": 200,
  "msg": "Operation successful.",
  "data": {},
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

### Response Keys
- `success`: Request processed successfully.
- `fail`: Validation error or business operation failure.
- `unauthenticated`: Bearer token missing or invalid.
- `unauthorized`: Authenticated user lacks permission.
- `not_found`: Requested resource or endpoint does not exist.
- `blocked`: User account is blocked/suspended.
- `needActive`: Account requires activation.
- `exception`: Unexpected server error.

## Base URL & Versioning
- Base Path: `/api/v1`
- Authentication: Bearer Token via Laravel Sanctum

