# Health Check API

Checks application and API health status.

## Endpoint
- **URL:** `/api/v1/health`
- **Method:** `GET`
- **Auth:** Public (`guest`)

## Response Example

### Success
```json
{
  "key": "success",
  "status": "ok",
  "version": "v1",
  "timestamp": "2026-10-27T18:00:00+00:00",
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

