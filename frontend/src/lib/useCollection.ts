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
