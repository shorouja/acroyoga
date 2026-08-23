import { Route, Routes } from 'react-router-dom'
import { AuthProvider } from './lib/auth'
import ProtectedRoute from './components/ProtectedRoute'
import Login from './routes/Login'
import Register from './routes/Register'
import Library from './routes/Library'
import ExerciseDetail from './routes/ExerciseDetail'
import SkillDetail from './routes/SkillDetail'
import Maintenance from './routes/Maintenance'
import Impressum from './routes/Impressum'
import Datenschutz from './routes/Datenschutz'

export default function App() {
  // Maintenance mode (VITE_MAINTENANCE=true, set in .env.production) hides the
  // whole app behind a static page so no accounts are offered and no personal
  // data is collected. Only the legally required Impressum / Datenschutz pages
  // stay reachable. Flip the env flag off and redeploy to restore the app.
  if (import.meta.env.VITE_MAINTENANCE === 'true') {
    return (
      <Routes>
        <Route path="/impressum" element={<Impressum />} />
        <Route path="/datenschutz" element={<Datenschutz />} />
        <Route path="*" element={<Maintenance />} />
      </Routes>
    )
  }

  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/impressum" element={<Impressum />} />
        <Route path="/datenschutz" element={<Datenschutz />} />
        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<Library />} />
          <Route path="/exercises/:id" element={<ExerciseDetail />} />
          <Route path="/skills/:id" element={<SkillDetail />} />
        </Route>
      </Routes>
    </AuthProvider>
  )
}
