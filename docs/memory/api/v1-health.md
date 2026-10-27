# API: Health Check

## Summary
Returns the API runtime health status and version.

## Routes / entry
- `GET /api/v1/health` → Public (`guest`)

## Flow
1. Evaluates server availability and timestamp.
2. Returns JSON status payload.

## Keys / response
- success → `key=success`, `status=ok`, `version=v1`

## Files
- route: `routes/api.php`
- test: `tests/Feature/ApiBootTest.php`
