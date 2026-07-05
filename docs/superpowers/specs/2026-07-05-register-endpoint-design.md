# Register Endpoint — Design Spec

**Date:** 2026-07-05
**Status:** Approved (pending spec review)
**Roadmap item:** Immediate → `POST /api/register` — create user with hashed password

## Overview

A plain `RegistrationController` exposing `POST /api/register`, mirroring the
existing `SecurityController` login stub pattern. The route is anonymous-accessible.
It validates the request, hashes the password, persists a `User`, and returns
`201 Created` with a minimal user representation (no JWT — the frontend calls
`/api/login` separately).

## Request / Response

**Request body (JSON):**

```json
{ "email": "a@b.com", "password": "min8chars", "displayName": "Some Name" }
```

**Success — `201 Created`:**

```json
{ "id": 1, "email": "a@b.com", "displayName": "Some Name" }
```

The response body is a **hand-built array** (`['id' => …, 'email' => …, 'displayName' => …]`)
returned via `JsonResponse`. It is NOT the serialized `User` object — this guarantees
the `password` hash and `roles` are never leaked.

**Errors:**

- `400 Bad Request` — malformed / missing / non-JSON body.
- `422 Unprocessable Entity` — validation failures, returned as a structured list of
  field errors. Covers: invalid email, password too short, blank/oversized displayName,
  and duplicate email.

## Route & Security

- New controller method with `#[Route('/api/register', name: 'api_register', methods: ['POST'])]`.
- Add to `config/packages/security.yaml` `access_control`, **before** the
  `^/api → IS_AUTHENTICATED_FULLY` catch-all:

  ```yaml
  - { path: ^/api/register$, roles: PUBLIC_ACCESS }
  ```

- **Firewall verification (checkpoint, not assumption):** register has no dedicated
  firewall; it rides the `api` firewall (`jwt: ~`, stateless). Login got its own
  firewall so `json_login` could intercept — register does not. During implementation,
  verify a **tokenless** `POST /api/register` reaches the controller (returns 201/422,
  not 401). If the `api` JWT firewall rejects the anonymous request, the fix is to
  extend the `login` firewall's `pattern` to also match `^/api/register` (or add a
  dedicated public firewall). The tokenless functional test is the automated guard for this.

## Validation

- **Email:** reuse existing `#[Assert\Email]` / `#[Assert\NotBlank]` on `User::$email`.
- **Duplicate email — two layers:**
  1. `#[UniqueEntity(fields: ['email'])]` on the `User` entity → clean `422`.
  2. DB unique index on `email` (already declared `unique: true` on the column; confirm
     the migration created it). To close the concurrent-signup race, wrap `flush()` and
     catch `Doctrine\DBAL\Exception\UniqueConstraintViolationException`, returning a
     `422` (duplicate email) rather than letting it surface as a `500`.
- **Password:** validated on the **raw plaintext input BEFORE hashing** — never on
  `User::$password` (that field holds the hash, which is always long, so a length rule
  there would silently never fire). Constraints: `NotBlank` + `Length(min: 8)`.
  Password rules are intentionally minimal for v1 and can be tightened later
  (character classes, `NotCompromisedPassword`, etc.) in this one place.
- **displayName:** `NotBlank`, `Length(max: 100)` (matches the column). Required at signup.
  Not unique (two users may share a display name — accepted product decision).

## Controller Flow

1. Decode JSON body → `400` if invalid/empty.
2. Validate the raw input fields (email, password, displayName) → collect violations → `422` if any.
3. Instantiate `User`; set email + displayName; hash password via
   `UserPasswordHasherInterface::hashPassword($user, $plainPassword)`; `setPassword(...)`.
4. Validate the `User` entity (fires `UniqueEntity` on email) → `422` if any.
5. `persist` + `flush` via `EntityManagerInterface`, inside a try/catch for
   `UniqueConstraintViolationException` → `422` fallback.
6. Return `201` with the hand-built `{ id, email, displayName }` array.

## Files Touched

- **New:** `api/src/Controller/RegistrationController.php`
- **Edit:** `api/src/Entity/User.php` — add `#[UniqueEntity(fields: ['email'])]`
- **Edit:** `api/config/packages/security.yaml` — add register `access_control` rule
- **New (test harness):** install `symfony/test-pack` (dev), `api/phpunit.dist.xml` (recipe),
  `.env.test` `DATABASE_URL` wiring
- **New (tests):** `api/tests/Functional/RegistrationControllerTest.php`

## Testing

Functional tests using `WebTestCase` against a **migrated test Postgres** (Dockerized
local DB). Test-harness setup must confirm the test DB exists and is migrated —
one test asserts a row is actually persisted, so a suite that never touched Postgres
cannot pass silently.

Test cases:

- **Success (tokenless):** `POST /api/register` with NO `Authorization` header →
  `201`; response body has exactly `{id, email, displayName}` (no `password`, no `roles`);
  a `User` row exists with a `password` that is **hashed, not plaintext**.
- **Duplicate email** → `422`.
- **Password too short** (e.g. `"x"`) → `422`. *(Mandatory — guards the "rule validates
  the hash" silent failure.)*
- **Invalid email** → `422`.
- **Blank displayName** → `422`.

## Acceptance Criteria (from premortem)

1. Plaintext password validated **pre-hash**, with a failing short-password test.
2. Response is a **hand-built array**, asserted key-by-key (no leaked fields).
3. The success test runs **tokenless**, proving the firewall / access_control path.
4. Duplicate email caught by **both** `UniqueEntity` and a DB-constraint fallback;
   test DB confirmed migrated.

## Out of Scope

- JWT issuance on register (frontend calls `/api/login`).
- Email verification / confirmation flow.
- Rate limiting (tracked separately under Security & Compliance in the roadmap).
- The paired server-migration step (JWT keypair, `JWT_PASSPHRASE`) — separate roadmap item.
