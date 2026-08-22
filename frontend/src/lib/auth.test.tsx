import { http, HttpResponse } from 'msw'
import { act, renderHook } from '@testing-library/react'
import { describe, expect, test } from 'vitest'
import { server } from '../test/server'
import { AuthProvider, useAuth } from './auth'
import { getToken } from './tokenStore'
import { apiGet, ApiError } from './apiClient'
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

  test('a 401 from any API call flips isAuthenticated to false without the caller invoking logout', async () => {
    server.use(http.post('/api/login', () => HttpResponse.json({ token: 'jwt-abc' })))
    const { result } = renderHook(() => useAuth(), { wrapper })
    await act(async () => { await result.current.login('a@b.com', 'secret123') })
    expect(result.current.isAuthenticated).toBe(true)

    server.use(http.get('/api/exercises', () => new HttpResponse(null, { status: 401 })))
    await act(async () => {
      await expect(apiGet('/exercises')).rejects.toMatchObject({ status: 401 })
    })

    expect(result.current.isAuthenticated).toBe(false)
  })
})
