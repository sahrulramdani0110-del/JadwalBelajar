/**
 * api.js — Shared API helper & auth utilities
 * Konsumsi Laravel JWT API
 */

// ══════════════════════════════════════════════════════
//  CONFIG — Sesuaikan BASE_URL dengan alamat backend
// ══════════════════════════════════════════════════════
const BASE_URL = 'http://localhost:8000/api';

// ══════════════════════════════════════════════════════
//  TOKEN MANAGEMENT
// ══════════════════════════════════════════════════════
const Auth = {
  getToken:  ()     => localStorage.getItem('jwt_token'),
  getUser:   ()     => JSON.parse(localStorage.getItem('jwt_user') || 'null'),
  setToken:  (t)    => localStorage.setItem('jwt_token', t),
  setUser:   (u)    => localStorage.setItem('jwt_user', JSON.stringify(u)),
  clear:     ()     => { localStorage.removeItem('jwt_token'); localStorage.removeItem('jwt_user'); },
  isLogged:  ()     => !!localStorage.getItem('jwt_token'),
};

// ══════════════════════════════════════════════════════
//  CORE API FETCH WRAPPER
// ══════════════════════════════════════════════════════
async function apiFetch(method, endpoint, body = null, withAuth = true) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
  };

  if (withAuth && Auth.getToken()) {
    headers['Authorization'] = 'Bearer ' + Auth.getToken();
  }

  const options = { method, headers };
  if (body) options.body = JSON.stringify(body);

  try {
    const res  = await fetch(BASE_URL + endpoint, options);
    const data = await res.json();

    // Token expired → coba refresh otomatis
    if (res.status === 401 && withAuth && endpoint !== '/auth/login') {
      const refreshed = await tryRefresh();
      if (refreshed) return apiFetch(method, endpoint, body, withAuth);
      Auth.clear();
      window.location.href = '../pages/login.html';
      return null;
    }

    return { ok: res.ok, status: res.status, data };
  } catch (err) {
    console.error('[API Error]', err);
    return { ok: false, status: 0, data: { message: 'Tidak bisa terhubung ke server API. Pastikan backend aktif.' } };
  }
}

// ── Refresh token ──────────────────────────────────────
async function tryRefresh() {
  try {
    const res = await fetch(BASE_URL + '/auth/refresh', {
      method:  'POST',
      headers: { 'Authorization': 'Bearer ' + Auth.getToken(), 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (res.ok && data.token?.access_token) {
      Auth.setToken(data.token.access_token);
      return true;
    }
  } catch (_) {}
  return false;
}

// ── Shorthand methods ──────────────────────────────────
const api = {
  get:    (ep)        => apiFetch('GET',    ep),
  post:   (ep, body)  => apiFetch('POST',   ep, body),
  put:    (ep, body)  => apiFetch('PUT',    ep, body),
  delete: (ep)        => apiFetch('DELETE', ep),
  public: {
    post: (ep, body)  => apiFetch('POST', ep, body, false),
  },
};

// ══════════════════════════════════════════════════════
//  GUARD — Redirect ke login jika belum login
// ══════════════════════════════════════════════════════
function requireAuth() {
  if (!Auth.isLogged()) {
    window.location.href = '../pages/login.html';
    return false;
  }
  return true;
}

// ══════════════════════════════════════════════════════
//  UI HELPERS
// ══════════════════════════════════════════════════════

// Toast notification
function toast(msg, type = 'inf') {
  const icons = { ok: '✓', err: '✕', inf: 'ℹ' };
  let wrap = document.getElementById('toast-wrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.id = 'toast-wrap';
    wrap.className = 'toast-wrap';
    document.body.appendChild(wrap);
  }
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span class="t-ico">${icons[type]||'ℹ'}</span><span>${msg}</span>`;
  wrap.appendChild(el);
  setTimeout(() => el.style.opacity = '0', 3000);
  setTimeout(() => el.remove(), 3400);
}

// Button loading state
function btnLoad(id, loading, label = 'Simpan') {
  const btn = document.getElementById(id);
  if (!btn) return;
  btn.disabled = loading;
  btn.innerHTML = loading ? '<span class="spinner"></span> Loading...' : label;
}

// Show/hide error alert
function showErr(id, msg) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.classList.toggle('show', !!msg);
}

// Eye toggle password
function toggleEye(inputId, eyeEl) {
  const inp = document.getElementById(inputId);
  inp.type   = inp.type === 'password' ? 'text' : 'password';
  eyeEl.textContent = inp.type === 'password' ? '👁' : '🙈';
}

// Modal open/close
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close modal on overlay click
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
  });
});

// Deadline color helper
function deadlineClass(dl) {
  if (!dl) return '';
  const today = new Date(); today.setHours(0,0,0,0);
  const d     = new Date(dl); d.setHours(0,0,0,0);
  const diff  = Math.round((d - today) / 86400000);
  if (diff < 0)  return 'dl-past';
  if (diff === 0) return 'dl-urgent';
  if (diff <= 2)  return 'dl-urgent';
  if (diff <= 5)  return 'dl-soon';
  return 'dl-ok';
}

function deadlineLabel(dl) {
  if (!dl) return '<span class="dl-ok">—</span>';
  const today = new Date(); today.setHours(0,0,0,0);
  const d     = new Date(dl); d.setHours(0,0,0,0);
  const diff  = Math.round((d - today) / 86400000);
  const fmt   = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  const cls   = deadlineClass(dl);
  const label = diff < 0 ? fmt
    : diff === 0 ? '⚠ Hari ini!'
    : diff <= 2  ? `🔥 ${diff}h lagi`
    : diff <= 5  ? `⏰ ${diff}h lagi`
    : fmt;
  return `<span class="${cls}">${label}</span>`;
}

// Render sidebar user info
function renderSidebar() {
  const user = Auth.getUser();
  const el   = document.getElementById('sidebar-user');
  if (el && user) el.textContent = `${user.name} · ${user.role}`;

  const urlEl = document.getElementById('sidebar-url');
  if (urlEl) urlEl.textContent = BASE_URL.replace('/api', '');
}

// Logout action
async function doLogout() {
  if (!confirm('Yakin ingin keluar?')) return;
  await api.post('/auth/logout');
  Auth.clear();
  toast('Logout berhasil', 'ok');
  setTimeout(() => { window.location.href = '../pages/login.html'; }, 600);
}

// Check API connectivity
async function checkApi(pillId) {
  const el = document.getElementById(pillId);
  if (!el) return;
  el.className = 'api-pill wait';
  el.innerHTML = '<span class="api-dot"></span> Mengecek...';
  const res = await apiFetch('POST', '/auth/login', { email: 'x', password: 'x' }, false);
  if (res && res.status !== 0) {
    el.className = 'api-pill on';
    el.innerHTML = '<span class="api-dot"></span> API Online';
  } else {
    el.className = 'api-pill off';
    el.innerHTML = '<span class="api-dot"></span> API Offline';
  }
}
