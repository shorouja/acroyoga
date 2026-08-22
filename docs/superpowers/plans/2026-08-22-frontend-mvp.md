# Acroyoga Frontend MVP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a React SPA where a user can register/log in (JWT) and browse the read-only exercise + skill library.

**Architecture:** React 19 + Vite + TypeScript SPA in `frontend/`, talking to the existing Symfony/API Platform backend at `/api`. A thin typed `apiClient` attaches the JWT and unwraps Hydra JSON-LD; a React Context holds auth state (token in `localStorage`); React Router gates the library behind a `ProtectedRoute`. Served same-origin (Vite dev proxy in dev; Caddy static in prod), so no CORS.

**Tech Stack:** React 19, Vite, TypeScript, Tailwind CSS v4, React Router v6, Vitest + React Testing Library + jsdom, MSW v2 (API mocking).

**Spec:** `docs/superpowers/specs/2026-08-22-frontend-mvp-design.md`

## Global Constraints

- Build runs on **Windows Node 22** (already installed) against the repo at `d:\dev\acroyoga`; all frontend code lives under `frontend/`.
- Dev API target: Vite proxies `/api` → `http://localhost:8000` (Symfony `symfony server:start` in the WSL2 env). WSL2 localhost-forwarding makes it reachable from Windows.
- **Backend contracts (verbatim, do not change the frontend to expect anything else):**
  - `POST /api/login` body `{email, password}` → `200 {"token": "<jwt>"}`; bad creds → `401 {"code":401,"message":"Invalid credentials."}`.
  - `POST /api/register` body `{email, password, displayName}` → `201 {id, email, displayName}`; validation → `422 {"errors": {"<field>": "<message>"}}` (one string per field); bad JSON → `400 {"error": "..."}`.
  - `GET /api/exercises`, `GET /api/skills` → JWT-protected, Hydra JSON-LD: `{"hydra:member": [ ...items ], "hydra:totalItems": N}`. Items carry `@id`, `@type`, fields, and relations as IRI strings.
  - `GET /api/exercises/{id}`, `GET /api/skills/{id}` → single JSON-LD item.
  - Any protected endpoint without/with-expired JWT → `401`.
- **Enum backing values (string unions, never free strings):** Difficulty `beginner|intermediate|advanced`; Role `base|flyer|both|solo`; SkillCategory `balance|strength|flexibility|flow|inversion`.
- All API reads/writes go through `apiClient` — no bare `fetch` in components.
- TDD: write the failing test first, watch it fail, implement, watch it pass, commit.

---

### Task 1: Scaffold Vite + React + TS + Tailwind + test tooling

**Files:**
- Create: `frontend/` (Vite scaffold: `package.json`, `vite.config.ts`, `tsconfig*.json`, `index.html`, `src/main.tsx`, `src/App.tsx`, `src/index.css`)
- Delete: `frontend/index.html` placeholder (`<h1>It works</h1>`) — replaced by the scaffold
- Create: `frontend/src/setupTests.ts`, `frontend/vitest.config.ts` (or merge into `vite.config.ts`)
- Test: `frontend/src/App.test.tsx`

**Interfaces:**
- Produces: a working `frontend/` app; `App` component (default export) rendering a heading "Acroyoga"; `npm run dev`, `npm run build`, `npm test` scripts.

- [ ] **Step 1: Scaffold the app**

Run from `d:\dev\acroyoga`:
```bash
rm -f frontend/index.html
npm create vite@latest frontend -- --template react-ts
cd frontend
npm install
```
If `npm create` refuses because `frontend/` is non-empty, scaffold in a temp dir and move files in, or empty the dir first (only the placeholder exists).

- [ ] **Step 2: Add Tailwind v4, Router, and test tooling**

```bash
npm install react-router-dom
npm install tailwindcss @tailwindcss/vite
npm install -D vitest @testing-library/react @testing-library/jest-dom @testing-library/user-event jsdom msw
```

- [ ] **Step 3: Wire Tailwind + Vite proxy + Vitest**

Replace `frontend/vite.config.ts`:
```ts
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    proxy: { '/api': { target: 'http://localhost:8000', changeOrigin: true } },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: './src/setupTests.ts',
  },
})
```
Replace `frontend/src/index.css` with:
```css
@import "tailwindcss";
```
Create `frontend/src/setupTests.ts`:
```ts
import '@testing-library/jest-dom'
```
Add to `frontend/package.json` `"scripts"`: `"test": "vitest run"`, `"test:watch": "vitest"`.
Add `/// <reference types="vitest" />` at the top of `vite.config.ts` if TS complains about the `test` key.

- [ ] **Step 4: Write the failing smoke test**

Replace `frontend/src/App.tsx`:
```tsx
export default function App() {
  return <h1>Acroyoga</h1>
}
```
Create `frontend/src/App.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react'
import App from './App'

test('renders the app heading', () => {
  render(<App />)
  expect(screen.getByRole('heading', { name: 'Acroyoga' })).toBeInTheDocument()
})
```

- [ ] **Step 5: Run tests + build**

Run: `npm test`
Expected: 1 passing test.
Run: `npm run build`
Expected: build succeeds, `dist/` produced.

- [ ] **Step 6: Commit**

```bash
cd d:/dev/acroyoga
git add frontend .gitignore
git commit -m "feat(frontend): scaffold React+Vite+TS+Tailwind with Vitest"
```
Ensure `frontend/node_modules` and `frontend/dist` are gitignored (the Vite template adds a `frontend/.gitignore`; if not, add those two lines).

---

### Task 2: Types + apiClient (typed fetch, JWT, Hydra unwrap, error mapping)

**Files:**
- Create: `frontend/src/types.ts`
- Create: `frontend/src/lib/tokenStore.ts`
- Create: `frontend/src/lib/apiClient.ts`
- Create: `frontend/src/lib/apiClient.test.ts`
- Create: `frontend/src/test/server.ts` (MSW server helper)

**Interfaces:**
- Produces:
  - `types.ts`: `Difficulty`, `Role`, `SkillCategory` (string unions), `Exercise`, `Skill`, `HydraCollection<T>`.
  - `tokenStore.ts`: `getToken(): string | null`, `setToken(t: string): void`, `clearToken(): void`.
  - `apiClient.ts`: `class ApiError extends Error { status: number; fieldErrors?: Record<string,string> }`; `apiGet<T>(path: string): Promise<T>`; `apiPost<T>(path: string, body: unknown): Promise<T>`. Both prepend `/api`, send/accept JSON-LD, attach `Authorization: Bearer` when a token exists, and throw `ApiError` on non-2xx (mapping 422 `errors` → `fieldErrors`, clearing the token on 401).
  - `unwrapHydra<T>(c: HydraCollection<T>): T[]`.

- [ ] **Step 1: Write types**

Create `frontend/src/types.ts`:
```ts
export type Difficulty = 'beginner' | 'intermediate' | 'advanced'
export type Role = 'base' | 'flyer' | 'both' | 'solo'
export type SkillCategory = 'balance' | 'strength' | 'flexibility' | 'flow' | 'inversion'

export interface Exercise {
  '@id': string
  id: number
  name: string
  abbreviation: string | null
  difficulty: Difficulty
  role: Role
  description: string | null
  skills: string[] // IRIs, e.g. "/api/skills/3"
}

export interface Skill {
  '@id': string
  id: number
  name: string
  abbreviation: string | null
  category: SkillCategory
  description: string | null
  exercises: string[] // IRIs
}

export interface HydraCollection<T> {
  'hydra:member': T[]
  'hydra:totalItems': number
}
```

- [ ] **Step 2: Write tokenStore**

Create `frontend/src/lib/tokenStore.ts`:
```ts
const KEY = 'acro_jwt'
export const getToken = (): string | null => localStorage.getItem(KEY)
export const setToken = (t: string): void => localStorage.setItem(KEY, t)
export const clearToken = (): void => localStorage.removeItem(KEY)
```

- [ ] **Step 3: Write the failing apiClient tests**

Create `frontend/src/test/server.ts`:
```ts
import { setupServer } from 'msw/node'
export const server = setupServer()
```
Add to `frontend/src/setupTests.ts`:
```ts
import '@testing-library/jest-dom'
import { afterAll, afterEach, beforeAll } from 'vitest'
import { server } from './test/server'

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }))
afterEach(() => { server.resetHandlers(); localStorage.clear() })
afterAll(() => server.close())
```
Create `frontend/src/lib/apiClient.test.ts`:
```ts
import { http, HttpResponse } from 'msw'
import { describe, expect, test } from 'vitest'
import { server } from '../test/server'
import { apiGet, apiPost, ApiError, unwrapHydra } from './apiClient'
import { setToken, getToken } from './tokenStore'
import type { HydraCollection, Exercise } from '../types'

describe('apiClient', () => {
  test('apiGet unwraps a Hydra collection and sends the JWT', async () => {
    setToken('jwt-123')
    let sentAuth: string | null = null
    server.use(
      http.get('/api/exercises', ({ request }) => {
        sentAuth = request.headers.get('authorization')
        return HttpResponse.json({
          'hydra:member': [{ '@id': '/api/exercises/1', id: 1, name: 'Bird', abbreviation: null, difficulty: 'beginner', role: 'both', description: null, skills: [] }],
          'hydra:totalItems': 1,
        })
      }),
    )
    const data = await apiGet<HydraCollection<Exercise>>('/exercises')
    expect(sentAuth).toBe('Bearer jwt-123')
    expect(unwrapHydra(data)).toHaveLength(1)
    expect(unwrapHydra(data)[0].name).toBe('Bird')
  })

  test('apiPost returns the parsed body', async () => {
    server.use(http.post('/api/login', () => HttpResponse.json({ token: 'jwt-xyz' })))
    const data = await apiPost<{ token: string }>('/login', { email: 'a@b.com', password: 'secret123' })
    expect(data.token).toBe('jwt-xyz')
  })

  test('401 clears the token and throws ApiError(401)', async () => {
    setToken('stale')
    server.use(http.get('/api/exercises', () => new HttpResponse(null, { status: 401 })))
    await expect(apiGet('/exercises')).rejects.toMatchObject({ status: 401 })
    expect(getToken()).toBeNull()
  })

  test('422 maps errors to fieldErrors', async () => {
    server.use(http.post('/api/register', () => HttpResponse.json({ errors: { email: 'Already used.' } }, { status: 422 })))
    try {
      await apiPost('/register', {})
      throw new Error('should have thrown')
    } catch (e) {
      expect(e).toBeInstanceOf(ApiError)
      expect((e as ApiError).status).toBe(422)
      expect((e as ApiError).fieldErrors).toEqual({ email: 'Already used.' })
    }
  })
})
```

- [ ] **Step 4: Run tests to verify they fail**

Run: `npm test`
Expected: FAIL — `apiClient` module/exports not found.

- [ ] **Step 5: Implement apiClient**

Create `frontend/src/lib/apiClient.ts`:
```ts
import { getToken, clearToken } from './tokenStore'
import type { HydraCollection } from '../types'

export class ApiError extends Error {
  status: number
  fieldErrors?: Record<string, string>
  constructor(status: number, message: string, fieldErrors?: Record<string, string>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fieldErrors = fieldErrors
  }
}

export function unwrapHydra<T>(c: HydraCollection<T>): T[] {
  return c['hydra:member']
}

async function request<T>(method: 'GET' | 'POST', path: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/ld+json' }
  const token = getToken()
  if (token) headers.Authorization = `Bearer ${token}`
  if (body !== undefined) headers['Content-Type'] = 'application/json'

  let res: Response
  try {
    res = await fetch(`/api${path}`, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined })
  } catch {
    throw new ApiError(0, 'Network error — is the API reachable?')
  }

  if (res.status === 401) {
    clearToken()
    throw new ApiError(401, 'Unauthorized')
  }

  let payload: unknown = null
  const text = await res.text()
  if (text) { try { payload = JSON.parse(text) } catch { payload = null } }

  if (!res.ok) {
    const p = payload as { errors?: Record<string, string>; message?: string; error?: string } | null
    if (res.status === 422 && p?.errors) throw new ApiError(422, 'Validation failed', p.errors)
    throw new ApiError(res.status, p?.message ?? p?.error ?? `Request failed (${res.status})`)
  }
  return payload as T
}

export const apiGet = <T>(path: string): Promise<T> => request<T>('GET', path)
export const apiPost = <T>(path: string, body: unknown): Promise<T> => request<T>('POST', path, body)
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `npm test`
Expected: all apiClient tests PASS (plus Task 1's smoke test).

- [ ] **Step 7: Commit**

```bash
cd d:/dev/acroyoga
git add frontend/src
git commit -m "feat(frontend): typed apiClient with JWT, Hydra unwrap, error mapping"
```

---

### Task 3: Auth context (login / register / logout)

**Files:**
- Create: `frontend/src/lib/auth.tsx`
- Create: `frontend/src/lib/auth.test.tsx`

**Interfaces:**
- Consumes: `apiPost`, `ApiError` (Task 2); `getToken`, `setToken`, `clearToken` (Task 2).
- Produces:
  - `AuthProvider` (React component wrapping children).
  - `useAuth(): { isAuthenticated: boolean; login(email: string, password: string): Promise<void>; register(email: string, password: string, displayName: string): Promise<{ id: number; email: string; displayName: string }>; logout(): void }`.
  - `login` throws `ApiError` on failure (401 bad creds); `register` throws `ApiError` (422 → `fieldErrors`) on failure.

- [ ] **Step 1: Write the failing auth tests**

Create `frontend/src/lib/auth.test.tsx`:
```tsx
import { http, HttpResponse } from 'msw'
import { act, renderHook } from '@testing-library/react'
import { describe, expect, test } from 'vitest'
import { server } from '../test/server'
import { AuthProvider, useAuth } from './auth'
import { getToken } from './tokenStore'
import { ApiError } from './apiClient'
import type { ReactNode } from 'react'

const wrapper = ({ children }: { children: ReactNode }) => <AuthProvider>{children}</AuthProvider>

describe('auth', () => {
  test('login stores the token and flips isAuthenticated', async () => {
    server.use(http.post('/api/login', () => HttpResponse.json({ token: 'jwt-abc' })))
    const { result } = renderHook(() => useAuth(), { wrapper })
    expect(result.current.isAuthenticated).toBe(false)
    await act(async () => { await result.current.login('a@b.com', 'secret123') })
    expect(getToken()).toBe('jwt-abc')
    expect(result.current.isAuthenticated).toBe(true)
  })

  test('login failure throws ApiError and leaves unauthenticated', async () => {
    server.use(http.post('/api/login', () => new HttpResponse(null, { status: 401 })))
    const { result } = renderHook(() => useAuth(), { wrapper })
    await expect(
      act(async () => { await result.current.login('a@b.com', 'wrong') }),
    ).rejects.toBeInstanceOf(ApiError)
    expect(result.current.isAuthenticated).toBe(false)
  })

  test('register returns the created user', async () => {
    server.use(http.post('/api/register', () => HttpResponse.json({ id: 1, email: 'a@b.com', displayName: 'Al' }, { status: 201 })))
    const { result } = renderHook(() => useAuth(), { wrapper })
    let created: { id: number } | undefined
    await act(async () => { created = await result.current.register('a@b.com', 'secret123', 'Al') })
    expect(created?.id).toBe(1)
  })

  test('logout clears the token', async () => {
    server.use(http.post('/api/login', () => HttpResponse.json({ token: 'jwt-abc' })))
    const { result } = renderHook(() => useAuth(), { wrapper })
    await act(async () => { await result.current.login('a@b.com', 'secret123') })
    act(() => { result.current.logout() })
    expect(getToken()).toBeNull()
    expect(result.current.isAuthenticated).toBe(false)
  })
})
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npm test src/lib/auth.test.tsx`
Expected: FAIL — `auth` module not found.

- [ ] **Step 3: Implement auth**

Create `frontend/src/lib/auth.tsx`:
```tsx
import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react'
import { apiPost } from './apiClient'
import { clearToken, getToken, setToken } from './tokenStore'

interface RegisteredUser { id: number; email: string; displayName: string }

interface AuthValue {
  isAuthenticated: boolean
  login(email: string, password: string): Promise<void>
  register(email: string, password: string, displayName: string): Promise<RegisteredUser>
  logout(): void
}

const AuthContext = createContext<AuthValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setTok] = useState<string | null>(() => getToken())

  const login = useCallback(async (email: string, password: string) => {
    const { token } = await apiPost<{ token: string }>('/login', { email, password })
    setToken(token)
    setTok(token)
  }, [])

  const register = useCallback(
    (email: string, password: string, displayName: string) =>
      apiPost<RegisteredUser>('/register', { email, password, displayName }),
    [],
  )

  const logout = useCallback(() => { clearToken(); setTok(null) }, [])

  const value = useMemo<AuthValue>(
    () => ({ isAuthenticated: token !== null, login, register, logout }),
    [token, login, register, logout],
  )
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `npm test src/lib/auth.test.tsx`
Expected: 4 tests PASS.

- [ ] **Step 5: Commit**

```bash
cd d:/dev/acroyoga
git add frontend/src/lib/auth.tsx frontend/src/lib/auth.test.tsx
git commit -m "feat(frontend): auth context (login/register/logout)"
```

---

### Task 4: Router, ProtectedRoute, Login + Register pages

**Files:**
- Create: `frontend/src/components/ProtectedRoute.tsx`
- Create: `frontend/src/routes/Login.tsx`
- Create: `frontend/src/routes/Register.tsx`
- Modify: `frontend/src/App.tsx` (router + providers)
- Modify: `frontend/src/main.tsx` (wrap in `<BrowserRouter>`)
- Create: `frontend/src/routes/Login.test.tsx`
- Create: `frontend/src/components/ProtectedRoute.test.tsx`

**Interfaces:**
- Consumes: `useAuth` (Task 3); `ApiError` (Task 2).
- Produces: routes `/login`, `/register`, and a placeholder `/` (Library replaced in Task 5); `ProtectedRoute` wrapper that renders children when `isAuthenticated`, else `<Navigate to="/login" />`.

- [ ] **Step 1: Write the failing tests**

Create `frontend/src/components/ProtectedRoute.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { test, expect } from 'vitest'
import { AuthProvider } from '../lib/auth'
import ProtectedRoute from './ProtectedRoute'
import { setToken } from '../lib/tokenStore'

function renderAt(path: string) {
  return render(
    <AuthProvider>
      <MemoryRouter initialEntries={[path]}>
        <Routes>
          <Route path="/login" element={<div>Login Page</div>} />
          <Route element={<ProtectedRoute />}>
            <Route path="/" element={<div>Secret Library</div>} />
          </Route>
        </Routes>
      </MemoryRouter>
    </AuthProvider>,
  )
}

test('redirects to /login when unauthenticated', () => {
  renderAt('/')
  expect(screen.getByText('Login Page')).toBeInTheDocument()
})

test('renders children when authenticated', () => {
  setToken('jwt')
  renderAt('/')
  expect(screen.getByText('Secret Library')).toBeInTheDocument()
})
```
Create `frontend/src/routes/Login.test.tsx`:
```tsx
import { http, HttpResponse } from 'msw'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { test, expect } from 'vitest'
import { server } from '../test/server'
import { AuthProvider } from '../lib/auth'
import Login from './Login'

function renderLogin() {
  return render(
    <AuthProvider>
      <MemoryRouter initialEntries={['/login']}>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/" element={<div>Library Home</div>} />
        </Routes>
      </MemoryRouter>
    </AuthProvider>,
  )
}

test('successful login navigates to the library', async () => {
  server.use(http.post('/api/login', () => HttpResponse.json({ token: 'jwt' })))
  renderLogin()
  await userEvent.type(screen.getByLabelText(/email/i), 'a@b.com')
  await userEvent.type(screen.getByLabelText(/password/i), 'secret123')
  await userEvent.click(screen.getByRole('button', { name: /log in/i }))
  expect(await screen.findByText('Library Home')).toBeInTheDocument()
})

test('failed login shows an error message', async () => {
  server.use(http.post('/api/login', () => new HttpResponse(null, { status: 401 })))
  renderLogin()
  await userEvent.type(screen.getByLabelText(/email/i), 'a@b.com')
  await userEvent.type(screen.getByLabelText(/password/i), 'wrong')
  await userEvent.click(screen.getByRole('button', { name: /log in/i }))
  expect(await screen.findByText(/invalid credentials/i)).toBeInTheDocument()
})
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npm test src/components/ProtectedRoute.test.tsx src/routes/Login.test.tsx`
Expected: FAIL — modules not found.

- [ ] **Step 3: Implement ProtectedRoute**

Create `frontend/src/components/ProtectedRoute.tsx`:
```tsx
import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../lib/auth'

export default function ProtectedRoute() {
  const { isAuthenticated } = useAuth()
  return isAuthenticated ? <Outlet /> : <Navigate to="/login" replace />
}
```

- [ ] **Step 4: Implement Login and Register**

Create `frontend/src/routes/Login.tsx`:
```tsx
import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import { ApiError } from '../lib/apiClient'

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function onSubmit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    setBusy(true)
    try {
      await login(email, password)
      navigate('/')
    } catch (err) {
      setError(err instanceof ApiError && err.status === 401 ? 'Invalid credentials.' : 'Something went wrong. Try again.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <form onSubmit={onSubmit} className="mx-auto mt-16 flex max-w-sm flex-col gap-3">
      <h1 className="text-2xl font-bold">Log in</h1>
      {error && <p role="alert" className="text-red-600">{error}</p>}
      <label className="flex flex-col gap-1">Email
        <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} className="rounded border p-2" />
      </label>
      <label className="flex flex-col gap-1">Password
        <input type="password" required value={password} onChange={(e) => setPassword(e.target.value)} className="rounded border p-2" />
      </label>
      <button type="submit" disabled={busy} className="rounded bg-black p-2 text-white disabled:opacity-50">Log in</button>
      <p className="text-sm">No account? <Link to="/register" className="underline">Register</Link></p>
    </form>
  )
}
```
Create `frontend/src/routes/Register.tsx`:
```tsx
import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import { ApiError } from '../lib/apiClient'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [displayName, setDisplayName] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function onSubmit(e: FormEvent) {
    e.preventDefault()
    setError(null); setFieldErrors({}); setBusy(true)
    try {
      await register(email, password, displayName)
      navigate('/login')
    } catch (err) {
      if (err instanceof ApiError && err.status === 422 && err.fieldErrors) setFieldErrors(err.fieldErrors)
      else setError('Something went wrong. Try again.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <form onSubmit={onSubmit} className="mx-auto mt-16 flex max-w-sm flex-col gap-3">
      <h1 className="text-2xl font-bold">Register</h1>
      {error && <p role="alert" className="text-red-600">{error}</p>}
      <label className="flex flex-col gap-1">Email
        <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} className="rounded border p-2" />
        {fieldErrors.email && <span className="text-sm text-red-600">{fieldErrors.email}</span>}
      </label>
      <label className="flex flex-col gap-1">Display name
        <input required value={displayName} onChange={(e) => setDisplayName(e.target.value)} className="rounded border p-2" />
        {fieldErrors.displayName && <span className="text-sm text-red-600">{fieldErrors.displayName}</span>}
      </label>
      <label className="flex flex-col gap-1">Password
        <input type="password" required value={password} onChange={(e) => setPassword(e.target.value)} className="rounded border p-2" />
        {fieldErrors.password && <span className="text-sm text-red-600">{fieldErrors.password}</span>}
      </label>
      <button type="submit" disabled={busy} className="rounded bg-black p-2 text-white disabled:opacity-50">Create account</button>
      <p className="text-sm">Have an account? <Link to="/login" className="underline">Log in</Link></p>
    </form>
  )
}
```

- [ ] **Step 5: Wire the router**

Replace `frontend/src/App.tsx`:
```tsx
import { Route, Routes } from 'react-router-dom'
import { AuthProvider } from './lib/auth'
import ProtectedRoute from './components/ProtectedRoute'
import Login from './routes/Login'
import Register from './routes/Register'

export default function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<h1 className="p-8 text-2xl font-bold">Acroyoga — Library (coming in Task 5)</h1>} />
        </Route>
      </Routes>
    </AuthProvider>
  )
}
```
Replace `frontend/src/main.tsx`:
```tsx
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import App from './App'
import './index.css'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </StrictMode>,
)
```
Update `frontend/src/App.test.tsx` (App now needs a router + no longer renders a bare "Acroyoga" heading): replace its body with a smoke test that renders within `MemoryRouter` and asserts the login page shows at `/login`:
```tsx
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import App from './App'

test('unauthenticated user lands on login', () => {
  render(<MemoryRouter initialEntries={['/']}><App /></MemoryRouter>)
  expect(screen.getByRole('heading', { name: /log in/i })).toBeInTheDocument()
})
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `npm test`
Expected: all tests PASS (App smoke, ProtectedRoute ×2, Login ×2, apiClient, auth).

- [ ] **Step 7: Commit**

```bash
cd d:/dev/acroyoga
git add frontend/src
git commit -m "feat(frontend): routing, ProtectedRoute, login + register pages"
```

---

### Task 5: Library list + Exercise/Skill detail

**Files:**
- Create: `frontend/src/lib/useCollection.ts` (small data hook)
- Create: `frontend/src/routes/Library.tsx`
- Create: `frontend/src/routes/ExerciseDetail.tsx`
- Create: `frontend/src/routes/SkillDetail.tsx`
- Modify: `frontend/src/App.tsx` (mount library + detail routes)
- Create: `frontend/src/routes/Library.test.tsx`

**Interfaces:**
- Consumes: `apiGet`, `unwrapHydra`, `ApiError` (Task 2); `useAuth` (Task 3); `Exercise`, `Skill`, `HydraCollection` (Task 2 types).
- Produces: `useCollection<T>(path)` → `{ data: T[] | null; error: string | null; loading: boolean }`, which on a 401 `ApiError` calls `logout()`; `Library` route at `/`; detail routes `/exercises/:id`, `/skills/:id`.

- [ ] **Step 1: Write the failing Library test**

Create `frontend/src/routes/Library.test.tsx`:
```tsx
import { http, HttpResponse } from 'msw'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { test, expect } from 'vitest'
import { server } from '../test/server'
import { AuthProvider } from '../lib/auth'
import { setToken } from '../lib/tokenStore'
import Library from './Library'

function renderLibrary() {
  setToken('jwt')
  return render(
    <AuthProvider>
      <MemoryRouter><Library /></MemoryRouter>
    </AuthProvider>,
  )
}

test('renders exercises and skills from hydra:member', async () => {
  server.use(
    http.get('/api/exercises', () => HttpResponse.json({
      'hydra:member': [{ '@id': '/api/exercises/1', id: 1, name: 'Bird', abbreviation: null, difficulty: 'beginner', role: 'both', description: null, skills: [] }],
      'hydra:totalItems': 1,
    })),
    http.get('/api/skills', () => HttpResponse.json({
      'hydra:member': [{ '@id': '/api/skills/1', id: 1, name: 'Plank', abbreviation: null, category: 'strength', description: null, exercises: [] }],
      'hydra:totalItems': 1,
    })),
  )
  renderLibrary()
  expect(await screen.findByText('Bird')).toBeInTheDocument()
  expect(await screen.findByText('Plank')).toBeInTheDocument()
})

test('shows an error state when the API fails', async () => {
  server.use(
    http.get('/api/exercises', () => new HttpResponse(null, { status: 500 })),
    http.get('/api/skills', () => HttpResponse.json({ 'hydra:member': [], 'hydra:totalItems': 0 })),
  )
  renderLibrary()
  expect(await screen.findByText(/couldn't load/i)).toBeInTheDocument()
})
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npm test src/routes/Library.test.tsx`
Expected: FAIL — modules not found.

- [ ] **Step 3: Implement the data hook**

Create `frontend/src/lib/useCollection.ts`:
```ts
import { useEffect, useState } from 'react'
import { apiGet, ApiError, unwrapHydra } from './apiClient'
import { useAuth } from './auth'
import type { HydraCollection } from '../types'

export function useCollection<T>(path: string) {
  const { logout } = useAuth()
  const [data, setData] = useState<T[] | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true
    setLoading(true); setError(null)
    apiGet<HydraCollection<T>>(path)
      .then((c) => { if (active) setData(unwrapHydra(c)) })
      .catch((e) => {
        if (!active) return
        if (e instanceof ApiError && e.status === 401) { logout(); return }
        setError("Couldn't load data. Please try again.")
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [path, logout])

  return { data, error, loading }
}
```

- [ ] **Step 4: Implement Library + detail routes**

Create `frontend/src/routes/Library.tsx`:
```tsx
import { Link } from 'react-router-dom'
import { useCollection } from '../lib/useCollection'
import { useAuth } from '../lib/auth'
import type { Exercise, Skill } from '../types'

export default function Library() {
  const { logout } = useAuth()
  const exercises = useCollection<Exercise>('/exercises')
  const skills = useCollection<Skill>('/skills')
  const error = exercises.error ?? skills.error

  return (
    <div className="mx-auto max-w-3xl p-8">
      <header className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">Library</h1>
        <button onClick={logout} className="text-sm underline">Log out</button>
      </header>
      {error && <p role="alert" className="text-red-600">{error}</p>}

      <section className="mb-8">
        <h2 className="mb-2 text-xl font-semibold">Exercises</h2>
        {exercises.loading && <p>Loading…</p>}
        <ul className="grid gap-2">
          {exercises.data?.map((e) => (
            <li key={e['@id']} className="rounded border p-3">
              <Link to={`/exercises/${e.id}`} className="font-medium underline">{e.name}</Link>
              <span className="ml-2 text-sm text-gray-500">{e.difficulty} · {e.role}</span>
            </li>
          ))}
        </ul>
      </section>

      <section>
        <h2 className="mb-2 text-xl font-semibold">Skills</h2>
        {skills.loading && <p>Loading…</p>}
        <ul className="grid gap-2">
          {skills.data?.map((s) => (
            <li key={s['@id']} className="rounded border p-3">
              <Link to={`/skills/${s.id}`} className="font-medium underline">{s.name}</Link>
              <span className="ml-2 text-sm text-gray-500">{s.category}</span>
            </li>
          ))}
        </ul>
      </section>
    </div>
  )
}
```
Create `frontend/src/routes/ExerciseDetail.tsx`:
```tsx
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { apiGet, ApiError } from '../lib/apiClient'
import { useAuth } from '../lib/auth'
import type { Exercise } from '../types'

export default function ExerciseDetail() {
  const { id } = useParams()
  const { logout } = useAuth()
  const [item, setItem] = useState<Exercise | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    apiGet<Exercise>(`/exercises/${id}`)
      .then((e) => { if (active) setItem(e) })
      .catch((e) => {
        if (!active) return
        if (e instanceof ApiError && e.status === 401) { logout(); return }
        setError("Couldn't load this exercise.")
      })
    return () => { active = false }
  }, [id, logout])

  if (error) return <p role="alert" className="p-8 text-red-600">{error}</p>
  if (!item) return <p className="p-8">Loading…</p>
  return (
    <div className="mx-auto max-w-2xl p-8">
      <Link to="/" className="text-sm underline">← Library</Link>
      <h1 className="mt-2 text-2xl font-bold">{item.name}</h1>
      <p className="text-gray-500">{item.difficulty} · {item.role}</p>
      {item.description && <p className="mt-4 whitespace-pre-line">{item.description}</p>}
    </div>
  )
}
```
Create `frontend/src/routes/SkillDetail.tsx` (same shape, Skill type, `/skills/${id}`, shows `category`):
```tsx
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { apiGet, ApiError } from '../lib/apiClient'
import { useAuth } from '../lib/auth'
import type { Skill } from '../types'

export default function SkillDetail() {
  const { id } = useParams()
  const { logout } = useAuth()
  const [item, setItem] = useState<Skill | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    apiGet<Skill>(`/skills/${id}`)
      .then((s) => { if (active) setItem(s) })
      .catch((e) => {
        if (!active) return
        if (e instanceof ApiError && e.status === 401) { logout(); return }
        setError("Couldn't load this skill.")
      })
    return () => { active = false }
  }, [id, logout])

  if (error) return <p role="alert" className="p-8 text-red-600">{error}</p>
  if (!item) return <p className="p-8">Loading…</p>
  return (
    <div className="mx-auto max-w-2xl p-8">
      <Link to="/" className="text-sm underline">← Library</Link>
      <h1 className="mt-2 text-2xl font-bold">{item.name}</h1>
      <p className="text-gray-500">{item.category}</p>
      {item.description && <p className="mt-4 whitespace-pre-line">{item.description}</p>}
    </div>
  )
}
```

- [ ] **Step 5: Mount the routes**

In `frontend/src/App.tsx`, replace the placeholder `/` element and add detail routes inside the `ProtectedRoute` block:
```tsx
import Library from './routes/Library'
import ExerciseDetail from './routes/ExerciseDetail'
import SkillDetail from './routes/SkillDetail'
// ...
        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<Library />} />
          <Route path="/exercises/:id" element={<ExerciseDetail />} />
          <Route path="/skills/:id" element={<SkillDetail />} />
        </Route>
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `npm test`
Expected: all tests PASS.

- [ ] **Step 7: Manual smoke against the real API (optional but recommended)**

In WSL: `cd ~/dev/acroyoga && docker compose up -d db && cd api && symfony server:start` (or `php -S 127.0.0.1:8000 -t public`). Seed at least one exercise + skill (via `/api` or a fixture). In Windows: `cd frontend && npm run dev`, open the shown URL, register → log in → confirm the library lists your rows.

- [ ] **Step 8: Commit**

```bash
cd d:/dev/acroyoga
git add frontend/src
git commit -m "feat(frontend): library list + exercise/skill detail"
```

---

## Self-Review

**Spec coverage:**
- Stack (React+Vite+TS+Tailwind, Router, no state lib) → Task 1. ✓
- Same-origin/no-CORS + dev proxy → Task 1 (vite proxy), Global Constraints. ✓
- apiClient (JWT, Hydra unwrap, error mapping) → Task 2. ✓
- Auth flow (register/login/localStorage/401) → Tasks 2–3, useCollection 401→logout in Task 5. ✓
- Routing + ProtectedRoute + deep-link routes → Task 4–5. (Prod Caddy `try_files` is explicitly deferred to the deploy follow-up per spec "Out of scope".) ✓
- Error handling (network/5xx page state, 401 redirect, 422 field errors) → Task 2 (mapping), Task 4 (register 422), Task 5 (list error + 401). ✓
- Testing (Vitest+RTL+MSW; login success/failure, ProtectedRoute redirect, library renders, register 422) → Tasks 1–5. ✓
- Out of scope (progress, partnerships, admin, deploy, CORS) → not built. ✓

**Placeholder scan:** No TBD/TODO; every code/test step has real code; enum unions and response shapes are concrete.

**Type consistency:** `apiGet`/`apiPost`/`unwrapHydra`/`ApiError` signatures match across Tasks 2–5; `useAuth` shape matches between Task 3 definition and Tasks 4–5 usage; `Exercise`/`Skill`/`HydraCollection` fields match the backend and are used consistently; token key `acro_jwt` centralized in `tokenStore`.

**Note on Task 4 Step 5:** it updates `App.test.tsx` from Task 1 because App gains a router — called out explicitly so the smoke test isn't left asserting a heading that no longer exists.
