// Supabase Edge Function: send-notification
// Deploy with: supabase functions deploy send-notification
//
// Receives contact-form / schedule-a-meeting submissions from the
// public website, stores them in `public.submissions`, and emails a
// notification to the site owner via Resend.

import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';

// ============================================================
// >>> SET THESE AS SUPABASE SECRETS (never hard-code keys) <<<
//   supabase secrets set SUPABASE_SERVICE_ROLE_KEY=...
//   supabase secrets set RESEND_API_KEY=...
// SUPABASE_URL is automatically provided to Edge Functions at runtime.
// ============================================================
const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!;
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!;
const RESEND_API_KEY = Deno.env.get('RESEND_API_KEY')!;

const NOTIFY_TO = 'ahmedhabdulla0@gmail.com';
// Sender must use a domain verified in your Resend account (resend.com → Domains).
const NOTIFY_FROM = 'Portfolio Website <notifications@itsahmedmalik.com>';

const corsHeaders = {
  'Access-Control-Allow-Origin': '*', // TODO: lock this down to your Hostinger domain in production
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
  'Access-Control-Allow-Methods': 'POST, OPTIONS',
};

function buildEmailHtml(payload: Record<string, unknown>) {
  if (payload.type === 'meeting') {
    return `
      <h2>New Meeting Request</h2>
      <p><strong>Name:</strong> ${payload.full_name}</p>
      <p><strong>Email:</strong> ${payload.email}</p>
      <p><strong>Date:</strong> ${payload.meeting_date}</p>
      <p><strong>Time:</strong> ${payload.meeting_time}</p>
    `;
  }
  return `
    <h2>New Contact Form Submission</h2>
    <p><strong>Name:</strong> ${payload.full_name}</p>
    <p><strong>Email:</strong> ${payload.email}</p>
    <p><strong>Phone:</strong> ${payload.phone ?? '—'}</p>
    <p><strong>Service:</strong> ${payload.service ?? '—'}</p>
    <p><strong>Message:</strong><br/>${String(payload.message ?? '').replace(/\n/g, '<br/>')}</p>
  `;
}

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: corsHeaders });
  }

  try {
    const payload = await req.json();

    if (!payload.type || !payload.full_name || !payload.email) {
      return new Response(JSON.stringify({ error: 'Missing required fields' }), {
        status: 400,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' },
      });
    }

    const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY);

    const { data: inserted, error: insertError } = await supabase
      .from('submissions')
      .insert({
        type: payload.type,
        full_name: payload.full_name,
        email: payload.email,
        phone: payload.phone ?? null,
        service: payload.service ?? null,
        message: payload.message ?? null,
        meeting_date: payload.meeting_date ?? null,
        meeting_time: payload.meeting_time ?? null,
      })
      .select()
      .single();

    if (insertError) throw insertError;

    const subject =
      payload.type === 'meeting'
        ? `New meeting request from ${payload.full_name}`
        : `New contact form message from ${payload.full_name}`;

    const resendResponse = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${RESEND_API_KEY}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        from: NOTIFY_FROM,
        to: [NOTIFY_TO],
        reply_to: payload.email,
        subject,
        html: buildEmailHtml(payload),
      }),
    });

    const emailOk = resendResponse.ok;

    if (emailOk) {
      await supabase.from('submissions').update({ email_sent: true }).eq('id', inserted.id);
    } else {
      console.error('Resend error:', await resendResponse.text());
    }

    return new Response(JSON.stringify({ success: true, email_sent: emailOk }), {
      status: 200,
      headers: { ...corsHeaders, 'Content-Type': 'application/json' },
    });
  } catch (err) {
    console.error(err);
    return new Response(JSON.stringify({ error: (err as Error).message }), {
      status: 500,
      headers: { ...corsHeaders, 'Content-Type': 'application/json' },
    });
  }
});
