import { Route, Routes } from 'react-router-dom'
import { AuthProvider } from './lib/auth'
import ProtectedRoute from './components/ProtectedRoute'
import Login from './routes/Login'
import Register from './routes/Register'
import Library from './routes/Library'
import ExerciseDetail from './routes/ExerciseDetail'
import SkillDetail from './routes/SkillDetail'

export default function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<Library />} />
          <Route path="/exercises/:id" element={<ExerciseDetail />} />
          <Route path="/skills/:id" element={<SkillDetail />} />
        </Route>
      </Routes>
    </AuthProvider>
  )
}
