# Setup Guide — Dynamic Backend

This site was converted from fully static to dynamic with **zero visual changes**.
The public pages, CSS, and animations are untouched. What was added:

```
itsahmedmalik/
├── index.html, about.html, contact.html, work.html, blog*.html, ...   ← unchanged public pages
├── style.css, script.js                                                ← unchanged (script.js only got the
│                                                                          meeting-confirm handler rewired)
├── assets/js/
│   ├── supabase-config.js        ← public Supabase URL + anon key (placeholders)
│   └── contact-handler.js        ← wires the Contact form + Meeting modal to the backend
├── admin/                        ← NEW: password-protected admin dashboard
│   ├── login.html
│   ├── forgot-password.html
│   ├── reset-password.html
│   ├── dashboard.html
│   ├── css/admin.css
│   └── js/ (supabase-client.js, auth.js, dashboard.js)
├── supabase/                     ← NEW: backend infrastructure (deployed to Supabase, not Hostinger)
│   ├── migrations/0001_init.sql  ← database schema + Row Level Security policies
│   └── functions/send-notification/index.ts   ← Edge Function: stores submission + sends email via Resend
└── docs/                         ← this documentation
```

## 1. Create the Supabase project

1. Go to https://supabase.com → New Project.
2. Open the SQL Editor and run `supabase/migrations/0001_init.sql`.
3. Go to **Authentication → Users → Add user** and create **one** admin account
   (email + password). Disable public sign-ups in **Authentication → Settings**.

## 2. Get your Resend API key

1. Sign up at https://resend.com and verify a sending domain (or use their test domain while developing).
2. Create an API key.

## 3. Set Supabase secrets and deploy the Edge Function

```bash
supabase login
supabase link --project-ref YOUR_PROJECT_REF
supabase secrets set SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
supabase secrets set RESEND_API_KEY=your-resend-api-key
supabase functions deploy send-notification
```

The service role key and project URL are found in **Project Settings → API**.
Edit `supabase/functions/send-notification/index.ts` and replace `NOTIFY_FROM`
with an address on your verified Resend domain.

## 4. Add your public keys to the two placeholder files

- `assets/js/supabase-config.js` → set `url` and `anonKey`
- `admin/js/supabase-client.js` → set `ADMIN_SUPABASE_URL` and `ADMIN_SUPABASE_ANON_KEY`
  (same project — anon key is safe to expose, RLS protects the data)

## 5. Deploy to Hostinger

Upload the entire folder (including `admin/` and `assets/`) to `public_html` via
Hostinger's File Manager or FTP. The `supabase/` folder is **not** deployed to
Hostinger — it only contains the code you deploy to Supabase itself in step 3.

## 6. Using the site

- Visitors submitting the Contact form or Schedule-a-Meeting modal trigger the
  Edge Function, which stores the entry in Supabase and emails
  **ahmedhabdulla0@gmail.com** via Resend.
- Log in at `https://yourdomain.com/admin/login.html` with the admin account
  created in step 1 to view, mark read/archived, or delete submissions.
- Forgot your password? Use the "Forgot password?" link on the login page.

## Notes on performance

- All `<img>` tags across the public pages already using lazy behavior should
  keep `loading="lazy"` (verify per page); the admin dashboard and Supabase
  client scripts are loaded only on `contact.html` and inside `/admin/`, so they
  never affect load time on the rest of the site.
- Because RLS restricts reads to authenticated admins only, no dashboard data
  is ever exposed publicly, and the anon key can be safely committed to the
  client bundle.
