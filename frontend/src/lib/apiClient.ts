import { getToken, clearToken } from './tokenStore'
import type { HydraCollection } from '../types'

export class ApiError extends Error {
  status: number
  fieldErrors?: Record<string, string>
  constructor(status: number, message: string, fieldErrors?: Record<string, string>, cause?: unknown) {
    super(message, cause !== undefined ? { cause } : undefined)
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
  } catch (cause) {
    throw new ApiError(0, 'Network error — is the API reachable?', undefined, cause)
  }

  if (res.status === 401) {
    clearToken()
    window.dispatchEvent(new Event('auth:unauthorized'))
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
