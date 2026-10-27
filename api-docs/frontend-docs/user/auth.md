# Authentication & Profile API Documentation

All endpoints return HTTP status `200` with the outcome identified in `key`.

## 1. Register
- **URL:** `/api/v1/auth/register`
- **Method:** `POST`
- **Auth:** Public (`guest`)

### Request Body
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

### Responses
- `key=success` (`code=201`): Returns `data.user` and `data.token`.
- `key=fail` (`code=422`): Validation error (e.g. duplicate email).

---

## 2. Login
- **URL:** `/api/v1/auth/login`
- **Method:** `POST`
- **Auth:** Public (`guest`, rate limited `throttle:6,1`)

### Request Body
```json
{
  "email": "jane@example.com",
  "password": "Password123!",
  "device_name": "Web Dashboard"
}
```

### Responses
- `key=success` (`code=200`): Returns `data.user` and `data.token`.
- `key=fail` (`code=401`): Invalid credentials.
- `key=needActive` (`code=403`): Account deactivated.

---

## 3. Logout
- **URL:** `/api/v1/auth/logout`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)

### Responses
- `key=success` (`code=200`): Token invalidated.

---

## 4. Get Current User Profile (`me`)
- **URL:** `/api/v1/auth/me`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)

### Responses
- `key=success` (`code=200`): Returns `data.user`.
- `key=unauthenticated` (`code=401`): Missing or expired token.

---

## 5. Update Profile
- **URL:** `/api/v1/auth/me`
- **Method:** `PATCH`
- **Auth:** Bearer Token (`auth:sanctum`)

### Request Body
```json
{
  "name": "Jane Smith",
  "current_password": "Password123!",
  "password": "NewPassword456!"
}
```

---

## 6. Forgot Password
- **URL:** `/api/v1/auth/forgot-password`
- **Method:** `POST`
- **Auth:** Public (`guest`, `throttle:6,1`)

### Request Body
```json
{
  "email": "jane@example.com"
}
```

---

## 7. Reset Password
- **URL:** `/api/v1/auth/reset-password`
- **Method:** `POST`
- **Auth:** Public (`guest`, `throttle:6,1`)

### Request Body
```json
{
  "email": "jane@example.com",
  "token": "reset-token-value",
  "password": "BrandNewPassword123!",
  "password_confirmation": "BrandNewPassword123!"
}
```
