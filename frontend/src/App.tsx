import { Navigate, Outlet, Route, Routes, NavLink, useNavigate } from 'react-router-dom';
import { clearToken, getToken } from './lib/api';
import { AuthProvider, useAuth } from './lib/auth';
import { LoginPage } from './pages/LoginPage';
import { DashboardPage } from './pages/DashboardPage';
import { PermitsPage } from './pages/PermitsPage';
import { PermitDetailPage } from './pages/PermitDetailPage';
import { MocPage } from './pages/MocPage';
import { MocDetailPage } from './pages/MocDetailPage';
import { IncidentsPage } from './pages/IncidentsPage';
import { IncidentDetailPage } from './pages/IncidentDetailPage';
import { ContractorsPage } from './pages/ContractorsPage';
import { AiPage } from './pages/AiPage';

function Private({ children }: { children: React.ReactNode }) {
  if (!getToken()) return <Navigate to="/login" replace />;
  return <>{children}</>;
}

function Shell() {
  const navigate = useNavigate();
  const { me } = useAuth();
  const links = [
    { to: '/', label: 'داشبورد' },
    { to: '/permits', label: 'مجوز کار' },
    { to: '/moc', label: 'مدیریت تغییر' },
    { to: '/incidents', label: 'حوادث' },
    { to: '/contractors', label: 'پیمانکاران' },
    { to: '/ai', label: 'دستیار دانش' },
  ];
  return (
    <div className="min-h-screen grid grid-cols-[16rem_1fr]">
      <aside className="border-l border-line bg-panel px-5 py-6 flex flex-col gap-8">
        <div>
          <p className="text-xs tracking-[0.25em] text-ember">ARIA SAFEOPS</p>
          <h1 className="text-xl mt-1">ایمن‌کار</h1>
          <p className="text-xs text-muted mt-2">{me?.tenantName ?? 'سایت عملیاتی'}</p>
        </div>
        <nav className="flex flex-col gap-1">
          {links.map((l) => (
            <NavLink
              key={l.to}
              to={l.to}
              end={l.to === '/'}
              className={({ isActive }) =>
                `rounded-lg px-3 py-2 text-sm ${isActive ? 'bg-ember text-ink' : 'hover:bg-panel-2'}`
              }
            >
              {l.label}
            </NavLink>
          ))}
        </nav>
        <div className="mt-auto space-y-3">
          <p className="text-xs text-muted">{me?.fullName ?? me?.username}</p>
          <button className="text-sm text-muted" onClick={() => { clearToken(); navigate('/login'); }}>
            خروج
          </button>
        </div>
      </aside>
      <main className="p-8">
        <Outlet />
      </main>
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/" element={<Private><Shell /></Private>}>
          <Route index element={<DashboardPage />} />
          <Route path="permits" element={<PermitsPage />} />
          <Route path="permits/:id" element={<PermitDetailPage />} />
          <Route path="moc" element={<MocPage />} />
          <Route path="moc/:id" element={<MocDetailPage />} />
          <Route path="incidents" element={<IncidentsPage />} />
          <Route path="incidents/:id" element={<IncidentDetailPage />} />
          <Route path="contractors" element={<ContractorsPage />} />
          <Route path="ai" element={<AiPage />} />
        </Route>
      </Routes>
    </AuthProvider>
  );
}
