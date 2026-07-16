/**
 * Handles submission of the Contact form and the Schedule-a-Meeting
 * modal on contact.html. Sends data to a Supabase Edge Function, which
 * stores the submission in the database and emails a notification to
 * the site owner via Resend. No visual/DOM structure is changed —
 * only behaviour is attached to the existing elements.
 */

(function () {
  function setStatus(message, isError) {
    const el = document.getElementById('contact-form-status');
    if (!el) return;
    el.textContent = message;
    el.style.color = isError ? '#e0555c' : 'var(--accent-color, #4ade80)';
  }

  async function postToBackend(payload) {
    const cfg = window.SUPABASE_CONFIG;
    if (!cfg || !cfg.url || cfg.url === 'YOUR_SUPABASE_PROJECT_URL') {
      console.warn('Supabase is not configured yet. Add your project URL/anon key in assets/js/supabase-config.js');
      throw new Error('Backend not configured');
    }

    const response = await fetch(cfg.functionUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${cfg.anonKey}`,
        apikey: cfg.anonKey,
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const text = await response.text().catch(() => '');
      throw new Error(text || `Request failed with status ${response.status}`);
    }

    return response.json().catch(() => ({}));
  }

  // ===== Contact form ("Contact Now") =====
  window.handleContactSubmit = function handleContactSubmit(event) {
    event.preventDefault();

    const submitBtn = document.getElementById('contact-submit-btn');
    const fullName = document.getElementById('fullName')?.value.trim();
    const email = document.getElementById('emailAddr')?.value.trim();
    const phone = document.getElementById('phoneNum')?.value.trim();
    const service = document.getElementById('serviceReq')?.value;
    const message = document.getElementById('messageText')?.value.trim();

    if (!fullName || !email || !service || !message) {
      setStatus('Please fill in all required fields.', true);
      return false;
    }

    const originalLabel = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Sending…';
    }
    setStatus('Sending your message…', false);

    postToBackend({
      type: 'contact',
      full_name: fullName,
      email,
      phone,
      service,
      message,
    })
      .then(() => {
        setStatus('Message sent successfully! I will get back to you soon.', false);
        event.target.reset();
      })
      .catch((err) => {
        console.error(err);
        setStatus('Something went wrong sending your message. Please try again or email directly.', true);
      })
      .finally(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalLabel;
        }
      });

    return false;
  };

  // ===== Schedule a meeting modal =====
  // Returns a Promise<boolean> so script.js knows whether to close the modal.
  window.submitMeetingRequest = async function submitMeetingRequest(selectedDate, selectedTime) {
    const fullName = document.getElementById('fullName')?.value.trim() || 'Website visitor';
    const email = document.getElementById('emailAddr')?.value.trim() || '';

    try {
      await postToBackend({
        type: 'meeting',
        full_name: fullName,
        email,
        meeting_date: selectedDate.toISOString().slice(0, 10),
        meeting_time: selectedTime,
      });

      const summary = document.getElementById('meeting-summary');
      if (summary) {
        summary.textContent = `Your meeting is scheduled for ${selectedDate.toLocaleDateString('en-US', {
          weekday: 'long',
          day: 'numeric',
          month: 'long',
        })} at ${selectedTime}. A confirmation has been sent.`;
      }
      return true;
    } catch (err) {
      console.error(err);
      alert('Could not schedule the meeting right now. Please try again or use the contact form.');
      return false;
    }
  };
})();
