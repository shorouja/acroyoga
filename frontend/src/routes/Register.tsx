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
