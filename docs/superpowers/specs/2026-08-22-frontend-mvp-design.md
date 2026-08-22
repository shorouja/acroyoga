# Acroyoga Frontend MVP — Design

**Date:** 2026-08-22
**Status:** Approved (design), pending implementation plan
**Roadmap item:** Mid-Term → "Frontend: choose framework, scaffold into `frontend/`" (+ related SPA-routing and CORS items)

## Goal

Ship the thinnest end-to-end slice of a web frontend for the acroyoga platform: a user can **register / log in (JWT)** and **browse the exercise + skill library** (read-only list + detail). This proves auth, the API client, client-side routing, and static serving before any richer features are built on top.

## Tech stack

- **React 19 + Vite + TypeScript** — fast SPA build Caddy can serve statically; best API Platform tooling and ecosystem coverage.
- **Tailwind CSS** — utility-first styling, fast iteration.
- **React Router** — client-side routing.
- No state-management library (React Context is sufficient for MVP). No SSR.

## Architecture

### Serving strategy — same-origin, no CORS

The backend already serves `/api/*` via Caddy → PHP-FPM. The built SPA is served at `/` on the **same host**, so browser requests to `/api/...` are same-origin. **Consequence:** the roadmap's `nelmio/cors-bundle` item is unnecessary for this topology and is dropped from MVP scope. CORS only returns if the frontend is later split onto its own domain.

- **Dev:** Vite dev server proxies `/api` → the local WSL2 Symfony API (`symfony server:start`, backed by the Dockerized Postgres). Dev is therefore same-origin too.
- **Prod:** Caddy serves the SPA `dist/` at `/`, with a `try_files {path} /index.html` fallback so refreshing a deep link (e.g. `/exercises/5`) returns the SPA instead of 404. API continues at `/api/*`.

### Project structure

```
frontend/
  src/
    main.tsx
    App.tsx                 # router + AuthProvider
    lib/
      apiClient.ts          # typed fetch wrapper: base URL, JWT header, Hydra unwrap, error mapping
      auth.tsx              # AuthContext: token state (localStorage), login/register/logout, useAuth()
    routes/
      Login.tsx
      Register.tsx
      Library.tsx           # list of exercises + skills
      ExerciseDetail.tsx
      SkillDetail.tsx
    components/
      ProtectedRoute.tsx    # redirects to /login when unauthenticated
      Nav.tsx
      Card.tsx
    types.ts                # Exercise, Skill (hand-written for the two read resources)
  index.html
  vite.config.ts            # dev proxy for /api
  tailwind.config.js
  package.json
```
The existing placeholder `frontend/index.html` (`<h1>It works</h1>`) is replaced by the Vite scaffold.

## Components / units

- **`apiClient`** — single point for all HTTP. Prepends base path (`/api`), attaches `Authorization: Bearer <token>` when present, parses API Platform Hydra JSON-LD (`hydra:member` collections) into typed arrays, and maps errors (see Error handling). Consumers get typed data, never raw responses.
- **`auth` (AuthContext + useAuth)** — holds the JWT (persisted in `localStorage`), exposes `login(email, pw)`, `register(...)`, `logout()`, and `isAuthenticated`. `login` calls `POST /api/login`, stores the returned token.
- **`ProtectedRoute`** — wraps authenticated routes; redirects to `/login` when there is no valid token.
- **Route components** — thin: call `apiClient`, render loading/error/data states.

## Data flow

1. User submits Login → `auth.login` → `POST /api/login` → JWT stored in context + `localStorage`.
2. Navigating to `/` (Library) → `apiClient.get('/exercises')` and `/skills` → Hydra `hydra:member` unwrapped → typed `Exercise[]` / `Skill[]` → rendered as cards.
3. Detail route → `apiClient.get('/exercises/:id')` (or skills) → typed object → rendered.
4. All API reads carry the JWT; the library is behind `ProtectedRoute`.

## Auth details

- **Register:** `POST /api/register` with `{ email, password, displayName }` → 201 or 422 with field errors.
- **Login:** `POST /api/login` (json_login) → `{ token }`.
- **Token storage:** `localStorage` (MVP-appropriate; documented tradeoff — XSS exposure, acceptable for v1, revisit with refresh-token/HttpOnly cookie later).
- **Expiry / 401:** any `401` from `apiClient` clears the token and redirects to `/login`.

## Error handling

Centralized in `apiClient`:
- Network / 5xx → thrown as an error the route renders as a user-visible message (retry affordance).
- `401` → clear token, redirect to `/login`.
- `422` (register validation) → structured field errors surfaced inline on the form, read from the API error body.

## Testing

- **Vitest + React Testing Library**, **MSW** to mock the API (no real backend in unit tests).
- Cover: login success stores token + redirects; login failure shows error; `ProtectedRoute` redirects when unauthenticated; Library renders `hydra:member`; Register surfaces 422 field errors.

## Out of scope (YAGNI — deferred)

- Progress tracking, partnerships, session logs, content admin/CRUD.
- Redux or other state libraries; SSR/Next.
- **Deploy job** — building `frontend/` and shipping `dist/` to the server via GitHub Actions is a **separate follow-up task** (build + verify locally first, then wire deploy so two new things aren't coupled). The Caddyfile `try_files` change lands with that task.
- `nelmio/cors-bundle` — unnecessary under the same-origin topology above.

## Open items to confirm at plan time

- Exact shape of the `POST /api/login` success body (confirm the token field name against the lexik config).
- Whether the library lists exercises and skills on one page or two tabs (cosmetic; decide in the plan).
