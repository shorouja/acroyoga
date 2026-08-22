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
