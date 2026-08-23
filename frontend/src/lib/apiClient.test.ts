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
