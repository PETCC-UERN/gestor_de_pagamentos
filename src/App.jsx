import { BrowserRouter, Routes, Route, Link } from 'react-router-dom'
import Clientes from './pages/Clientes'
import Faturas from './pages/Faturas'

export default function App() {
  return (
      <BrowserRouter>
        <nav className="bg-blue-600 text-white p-4 flex gap-6">
          <Link to="/clientes" className="hover:underline">Clientes</Link>
          <Link to="/faturas" className="hover:underline">Faturas</Link>
        </nav>
        <Routes>
          <Route path="/clientes" element={<Clientes />} />
          <Route path="/faturas" element={<Faturas />} />
          <Route path="/" element={<Clientes />} />
        </Routes>
      </BrowserRouter>
  )
}