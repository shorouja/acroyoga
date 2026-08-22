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
