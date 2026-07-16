/**
 * Supabase public (anon) configuration.
 * Safe to expose in client-side code — the anon key only allows what
 * Row Level Security policies permit (see /supabase/migrations).
 *
 * ============================================================
 *  >>> INSERT YOUR SUPABASE PROJECT KEYS BELOW <<<
 *  Get these from: Supabase Dashboard → Project Settings → API
 * ============================================================
 */
window.SUPABASE_CONFIG = {
  // TODO: Replace with your Supabase Project URL, e.g. "https://xxxxxxxx.supabase.co"
  url: 'https://tdmowcyhdbhqyhqysacc.supabase.co',

  // TODO: Replace with your Supabase "anon / public" API key (NOT the service_role key)
  anonKey: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRkbW93Y3loZGJocXlocXlzYWNjIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODQwMTkyMDksImV4cCI6MjA5OTU5NTIwOX0.rtF6HUDysztC-bCJW9eLJMnQehx_Pz5_gDE6tt-MW8Y',

  // Edge Function endpoint that validates + stores the submission and
  // triggers the Resend email notification. Deployed from /supabase/functions/send-notification.
  // Format: `${url}/functions/v1/send-notification`
  get functionUrl() {
    return `${this.url}/functions/v1/send-notification`;
  },
};
