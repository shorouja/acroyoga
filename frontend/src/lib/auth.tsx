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
