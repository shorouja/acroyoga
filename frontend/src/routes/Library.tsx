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
