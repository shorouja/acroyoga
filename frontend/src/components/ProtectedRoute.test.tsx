import { render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { test, expect } from 'vitest'
import { AuthProvider } from '../lib/auth'
import ProtectedRoute from './ProtectedRoute'
import { setToken } from '../lib/tokenStore'

function renderAt(path: string) {
  return render(
    <AuthProvider>
      <MemoryRouter initialEntries={[path]}>
        <Routes>
          <Route path="/login" element={<div>Login Page</div>} />
          <Route element={<ProtectedRoute />}>
            <Route path="/" element={<div>Secret Library</div>} />
          </Route>
        </Routes>
      </MemoryRouter>
    </AuthProvider>,
  )
}

test('redirects to /login when unauthenticated', () => {
  renderAt('/')
  expect(screen.getByText('Login Page')).toBeInTheDocument()
})

test('renders children when authenticated', () => {
  setToken('jwt')
  renderAt('/')
  expect(screen.getByText('Secret Library')).toBeInTheDocument()
})
