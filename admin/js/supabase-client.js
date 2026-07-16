/**
 * Shared Supabase client for the admin dashboard.
 * Uses the SAME public anon key as the main site (safe — access is
 * governed entirely by Row Level Security + Supabase Auth sessions).
 *
 * ============================================================
 *  >>> INSERT YOUR SUPABASE PROJECT KEYS BELOW <<<
 *  Get these from: Supabase Dashboard → Project Settings → API
 * ============================================================
 */
const ADMIN_SUPABASE_URL = 'https://tdmowcyhdbhqyhqysacc.supabase.co'; // TODO: e.g. https://xxxxxxxx.supabase.co
const ADMIN_SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRkbW93Y3loZGJocXlocXlzYWNjIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODQwMTkyMDksImV4cCI6MjA5OTU5NTIwOX0.rtF6HUDysztC-bCJW9eLJMnQehx_Pz5_gDE6tt-MW8Y'; // TODO: anon/public key (NOT service_role)

window.adminSupabase = supabase.createClient(ADMIN_SUPABASE_URL, ADMIN_SUPABASE_ANON_KEY);
