import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Login from './pages/Login';
import Register from './pages/Register';
import FolioList from './pages/FolioList';
import FundFolio from './pages/FundFolio';
import CreateRemittance from './pages/CreateRemittance';
import RemittanceDetail from './pages/RemittanceDetail';
import FolioLedger from './pages/FolioLedger';
import { useAuthStore } from './store/authStore';
import type { ReactNode } from 'react';

function ProtectedRoute({ children }: { children: ReactNode }) {
  const token = useAuthStore((state) => state.token);
  if (!token) return <Navigate to="/login" replace />;
  return <>{children}</>;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route
          path="/folios"
          element={
            <ProtectedRoute>
              <FolioList />
            </ProtectedRoute>
          }
        />
        <Route
          path="/folios/:folioId/fund"
          element={
            <ProtectedRoute>
              <FundFolio />
            </ProtectedRoute>
          }
        />
        <Route
          path="/folios/:folioId/ledger"
          element={
            <ProtectedRoute>
              <FolioLedger />
            </ProtectedRoute>
          }
        />
        <Route
          path="/remittances/new"
          element={
            <ProtectedRoute>
              <CreateRemittance />
            </ProtectedRoute>
          }
        />
        <Route
          path="/remittances/:remittanceId"
          element={
            <ProtectedRoute>
              <RemittanceDetail />
            </ProtectedRoute>
          }
        />
        <Route path="*" element={<Navigate to="/folios" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
