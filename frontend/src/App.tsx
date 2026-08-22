import { Route, Routes } from 'react-router-dom'
import { AuthProvider } from './lib/auth'
import ProtectedRoute from './components/ProtectedRoute'
import Login from './routes/Login'
import Register from './routes/Register'

export default function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<h1 className="p-8 text-2xl font-bold">Acroyoga — Library (coming in Task 5)</h1>} />
        </Route>
      </Routes>
    </AuthProvider>
  )
}
