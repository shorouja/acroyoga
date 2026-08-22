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
