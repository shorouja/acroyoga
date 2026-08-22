import { http, HttpResponse } from 'msw'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { test, expect } from 'vitest'
import { server } from '../test/server'
import { AuthProvider } from '../lib/auth'
import Login from './Login'

function renderLogin() {
  return render(
    <AuthProvider>
      <MemoryRouter initialEntries={['/login']}>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/" element={<div>Library Home</div>} />
        </Routes>
      </MemoryRouter>
    </AuthProvider>,
  )
}

test('successful login navigates to the library', async () => {
  server.use(http.post('/api/login', () => HttpResponse.json({ token: 'jwt' })))
  renderLogin()
  await userEvent.type(screen.getByLabelText(/email/i), 'a@b.com')
  await userEvent.type(screen.getByLabelText(/password/i), 'secret123')
  await userEvent.click(screen.getByRole('button', { name: /log in/i }))
  expect(await screen.findByText('Library Home')).toBeInTheDocument()
})

test('failed login shows an error message', async () => {
  server.use(http.post('/api/login', () => new HttpResponse(null, { status: 401 })))
  renderLogin()
  await userEvent.type(screen.getByLabelText(/email/i), 'a@b.com')
  await userEvent.type(screen.getByLabelText(/password/i), 'wrong')
  await userEvent.click(screen.getByRole('button', { name: /log in/i }))
  expect(await screen.findByText(/invalid credentials/i)).toBeInTheDocument()
})
