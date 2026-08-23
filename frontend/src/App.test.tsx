import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, vi } from 'vitest'
import App from './App'

afterEach(() => vi.unstubAllEnvs())

test('unauthenticated user lands on login', () => {
  render(<MemoryRouter initialEntries={['/']}><App /></MemoryRouter>)
  expect(screen.getByRole('heading', { name: /log in/i })).toBeInTheDocument()
})

test('maintenance mode replaces the app with a maintenance page', () => {
  vi.stubEnv('VITE_MAINTENANCE', 'true')
  render(<MemoryRouter initialEntries={['/']}><App /></MemoryRouter>)
  expect(screen.getByRole('heading', { name: /wartung/i })).toBeInTheDocument()
  expect(screen.queryByRole('heading', { name: /log in/i })).not.toBeInTheDocument()
})

test('maintenance mode still serves the Impressum at /impressum', () => {
  vi.stubEnv('VITE_MAINTENANCE', 'true')
  render(<MemoryRouter initialEntries={['/impressum']}><App /></MemoryRouter>)
  expect(screen.getByRole('heading', { name: /impressum/i })).toBeInTheDocument()
})

test('maintenance mode still serves the Datenschutzerklärung', () => {
  vi.stubEnv('VITE_MAINTENANCE', 'true')
  render(<MemoryRouter initialEntries={['/datenschutz']}><App /></MemoryRouter>)
  expect(screen.getByRole('heading', { name: /datenschutzerklärung/i })).toBeInTheDocument()
})
