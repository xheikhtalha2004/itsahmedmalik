-- ============================================================
-- Ahmed Malik Portfolio — Supabase schema
-- Run in Supabase SQL Editor, or via `supabase db push`.
-- ============================================================

create extension if not exists "pgcrypto";

-- Single source of truth for all website form submissions
create table if not exists public.submissions (
  id uuid primary key default gen_random_uuid(),
  type text not null check (type in ('contact', 'meeting')),
  full_name text not null,
  email text not null,
  phone text,
  service text,
  message text,
  meeting_date date,
  meeting_time text,
  status text not null default 'new' check (status in ('new', 'read', 'archived')),
  email_sent boolean not null default false,
  created_at timestamptz not null default now()
);

create index if not exists submissions_created_at_idx on public.submissions (created_at desc);

alter table public.submissions enable row level security;

-- Public website visitors may INSERT (submit the form) but never read/update/delete.
drop policy if exists "public can submit" on public.submissions;
create policy "public can submit"
  on public.submissions
  for insert
  to anon
  with check (true);

-- Only the authenticated admin (logged in via the admin dashboard) can read/manage.
drop policy if exists "admin can read" on public.submissions;
create policy "admin can read"
  on public.submissions
  for select
  to authenticated
  using (true);

drop policy if exists "admin can update" on public.submissions;
create policy "admin can update"
  on public.submissions
  for update
  to authenticated
  using (true)
  with check (true);

drop policy if exists "admin can delete" on public.submissions;
create policy "admin can delete"
  on public.submissions
  for delete
  to authenticated
  using (true);

-- ============================================================
-- Admin account:
-- Create the single admin user from Supabase Dashboard →
-- Authentication → Users → "Add user" (do NOT allow public sign-ups).
-- The policies above intentionally grant access to ANY authenticated
-- user because this project uses a single admin account only.
-- ============================================================
