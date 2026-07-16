/**
 * Session guard for admin pages. Include after supabase-client.js.
 * Call requireAuth() on protected pages (dashboard) and
 * redirectIfAuthed() on public pages (login/forgot-password).
 */

async function requireAuth() {
  const { data: { session } } = await window.adminSupabase.auth.getSession();
  if (!session) {
    window.location.href = 'login.html';
    return null;
  }
  return session;
}

async function redirectIfAuthed() {
  const { data: { session } } = await window.adminSupabase.auth.getSession();
  if (session) {
    window.location.href = 'dashboard.html';
  }
}

window.adminSupabase?.auth.onAuthStateChange((_event, session) => {
  if (!session && !window.location.pathname.includes('login') && !window.location.pathname.includes('forgot') && !window.location.pathname.includes('reset')) {
    window.location.href = 'login.html';
  }
});

async function logoutAdmin() {
  await window.adminSupabase.auth.signOut();
  window.location.href = 'login.html';
}
