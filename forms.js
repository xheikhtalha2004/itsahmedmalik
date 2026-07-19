/* Shared contact, meeting, and newsletter submissions. */
;(function () {
  'use strict';

  const selectors = {
    forms: '.contact-form-card, .meeting-form, .newsletter-form',
    status: '[data-form-status]',
    turnstile: '.turnstile-widget',
  };

  const turnstileWidgets = new Map();

  function uuid() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const value = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
    return `${value.slice(0, 8)}-${value.slice(8, 12)}-${value.slice(12, 16)}-${value.slice(16, 20)}-${value.slice(20)}`;
  }

  function ensureSubmissionId(form) {
    const input = form.querySelector('input[name="submission_id"]');
    if (input && !input.value) input.value = uuid();
  }

  function setStatus(form, message, state) {
    let status = form.querySelector(selectors.status);
    if (!status) {
      status = document.createElement('p');
      status.dataset.formStatus = '';
      status.className = 'form-status';
      status.setAttribute('role', 'status');
      status.setAttribute('aria-live', 'polite');
      form.appendChild(status);
    }
    status.textContent = message;
    status.dataset.state = state || '';
  }

  function clearFieldErrors(form) {
    form.querySelectorAll('[aria-invalid="true"]').forEach(field => {
      field.removeAttribute('aria-invalid');
      field.removeAttribute('aria-describedby');
    });
    form.querySelectorAll('.field-error').forEach(error => error.remove());
  }

  function showFieldErrors(form, errors) {
    let firstInvalid = null;
    Object.entries(errors || {}).forEach(([name, message]) => {
      const field = form.elements.namedItem(name);
      if (!(field instanceof HTMLElement)) return;
      const error = document.createElement('span');
      const errorId = `error-${name}-${Math.random().toString(36).slice(2, 8)}`;
      error.id = errorId;
      error.className = 'field-error';
      error.textContent = String(message);
      field.setAttribute('aria-invalid', 'true');
      field.setAttribute('aria-describedby', errorId);
      field.insertAdjacentElement('afterend', error);
      firstInvalid ||= field;
    });
    firstInvalid?.focus();
  }

  function resetTurnstile(form) {
    const container = form.querySelector(selectors.turnstile);
    const widgetId = container && turnstileWidgets.get(container);
    if (widgetId !== undefined && window.turnstile) window.turnstile.reset(widgetId);
  }

  function renderTurnstileWidgets() {
    if (!window.turnstile) return;
    document.querySelectorAll(selectors.turnstile).forEach(container => {
      if (turnstileWidgets.has(container)) return;
      const sitekey = container.dataset.sitekey || document.querySelector('meta[name="turnstile-site-key"]')?.content;
      if (!sitekey) return;
      const widgetId = window.turnstile.render(container, {
        sitekey,
        action: container.dataset.action || 'newsletter',
        appearance: 'interaction-only',
        theme: 'dark',
      });
      turnstileWidgets.set(container, widgetId);
    });
  }

  window.portfolioTurnstileReady = renderTurnstileWidgets;
  if (window.turnstile) renderTurnstileWidgets();

  async function submitForm(form) {
    clearFieldErrors(form);
    ensureSubmissionId(form);

    if (!form.reportValidity()) return;

    const source = form.querySelector('input[name="source_path"]');
    if (source) source.value = window.location.pathname;

    const submit = form.querySelector('[type="submit"]');
    const originalLabel = submit?.innerHTML;
    if (submit) {
      submit.disabled = true;
      submit.setAttribute('aria-busy', 'true');
    }
    setStatus(form, 'Sending…', 'loading');

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      const payload = await response.json().catch(() => ({}));

      if (!response.ok || payload.ok !== true) {
        showFieldErrors(form, payload.errors || payload.fields);
        setStatus(form, payload.message || 'Unable to submit right now. Please check the form and try again.', 'error');
        return;
      }

      setStatus(form, payload.message || 'Thanks — your submission was received.', 'success');
      form.reset();
      const submissionId = form.querySelector('input[name="submission_id"]');
      if (submissionId) submissionId.value = uuid();
      resetTurnstile(form);

      if (form.classList.contains('meeting-form')) {
        form.dispatchEvent(new CustomEvent('meeting:submitted', { bubbles: true }));
      }
    } catch (_) {
      setStatus(form, 'The connection failed. Your details are still here — please try again.', 'error');
    } finally {
      if (submit) {
        submit.disabled = false;
        submit.removeAttribute('aria-busy');
        if (originalLabel !== undefined) submit.innerHTML = originalLabel;
      }
      resetTurnstile(form);
    }
  }

  function initializeForms() {
    document.querySelectorAll(selectors.forms).forEach(form => {
      if (!(form instanceof HTMLFormElement) || form.dataset.asyncReady === 'true') return;
      form.dataset.asyncReady = 'true';
      ensureSubmissionId(form);
      form.addEventListener('submit', event => {
        event.preventDefault();
        submitForm(form);
      });
    });
  }

  function initializeMeetingModal() {
    const modal = document.getElementById('meeting-modal');
    const openButton = document.getElementById('open-meeting-modal');
    const closeButton = document.getElementById('close-meeting-modal');
    const form = modal?.querySelector('.meeting-form');
    if (!modal || !openButton || !closeButton || !form) return;

    let restoreFocus = null;
    const focusableSelector = 'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])';

    const setPageInert = inert => {
      document.querySelectorAll('body > header, body > main, body > footer').forEach(element => {
        if (inert) element.setAttribute('inert', '');
        else element.removeAttribute('inert');
      });
    };

    const close = () => {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
      setPageInert(false);
      restoreFocus?.focus();
    };

    const open = () => {
      restoreFocus = document.activeElement;
      const contact = document.querySelector('.contact-form-card');
      ['full_name', 'email', 'phone'].forEach(name => {
        const target = form.elements.namedItem(name);
        const source = contact?.elements.namedItem(name);
        if (target instanceof HTMLInputElement && source instanceof HTMLInputElement && !target.value) {
          target.value = source.value;
        }
      });
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
      setPageInert(true);
      form.querySelector(focusableSelector)?.focus();
    };

    // Interactive calendar setup
    const dateInput = form.elements.namedItem('date');
    const dateDisplay = document.getElementById('meeting-date-display');
    const calendarGrid = document.getElementById('calendar-days-grid');
    const calendarMonthYear = document.getElementById('calendar-month-year');
    const prevBtn = document.getElementById('calendar-prev');
    const nextBtn = document.getElementById('calendar-next');

    if (dateInput && dateDisplay && calendarGrid && calendarMonthYear && prevBtn && nextBtn) {
      let currentDate = new Date();
      currentDate.setDate(1); // Set to the 1st to prevent month roll-over normalization bugs
      let selectedDate = null;

      // Extract limit bounds from hidden input
      const minDateStr = dateInput.getAttribute('min') || new Date().toISOString().slice(0, 10);
      const maxDateStr = dateInput.getAttribute('max') || new Date(new Date().getTime() + 90 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);

      // Robust manual parser for YYYY-MM-DD to avoid Safari/mobile timezone bugs
      function parseLocalDate(str) {
        const parts = str.split('-');
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10), 0, 0, 0);
      }

      const minDate = parseLocalDate(minDateStr);
      const maxDate = parseLocalDate(maxDateStr);

      const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ];

      function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        calendarMonthYear.textContent = `${months[month]} ${year}`;

        // Determine starting weekday (0 is Sunday, 1 is Monday ...)
        const firstDayIndex = new Date(year, month, 1).getDay();
        // Shift so 0 is Monday, 6 is Sunday
        const firstDay = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

        const totalDays = new Date(year, month + 1, 0).getDate();
        const prevTotalDays = new Date(year, month, 0).getDate();

        calendarGrid.innerHTML = '';

        // Muted padding days from previous month
        for (let i = firstDay; i > 0; i--) {
          const dayNum = prevTotalDays - i + 1;
          const dayElement = document.createElement('button');
          dayElement.type = 'button';
          dayElement.className = 'meeting-day is-muted';
          dayElement.disabled = true;
          dayElement.textContent = dayNum;
          calendarGrid.appendChild(dayElement);
        }

        // Active days for current month
        for (let dayNum = 1; dayNum <= totalDays; dayNum++) {
          const checkDate = new Date(year, month, dayNum);
          const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
          
          const dayElement = document.createElement('button');
          dayElement.type = 'button';
          dayElement.className = 'meeting-day';
          dayElement.textContent = dayNum;

          // Saturdays and Sundays are disabled
          const isWeekend = checkDate.getDay() === 0 || checkDate.getDay() === 6;

          // Check past or 90 days out
          const checkTime = new Date(year, month, dayNum, 0, 0, 0).getTime();
          const isPast = checkTime < minDate.getTime();
          const isTooFar = checkTime > maxDate.getTime();

          if (isWeekend || isPast || isTooFar) {
            dayElement.classList.add('is-disabled');
            dayElement.disabled = true;
          } else {
            if (selectedDate && dateStr === selectedDate) {
              dayElement.classList.add('is-selected');
            }

            dayElement.addEventListener('click', (e) => {
              e.preventDefault();
              calendarGrid.querySelectorAll('.meeting-day').forEach(el => el.classList.remove('is-selected'));
              dayElement.classList.add('is-selected');
              selectedDate = dateStr;
              dateInput.value = dateStr;
              
              const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
              dateDisplay.value = checkDate.toLocaleDateString('en-US', options);
              dateInput.setCustomValidity('');
            });
          }

          calendarGrid.appendChild(dayElement);
        }
      }

      prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
      });

      nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
      });

      renderCalendar();
    }


    openButton.addEventListener('click', open);
    closeButton.addEventListener('click', close);
    modal.querySelectorAll('[data-close-meeting-modal]').forEach(node => node.addEventListener('click', close));
    form.addEventListener('meeting:submitted', () => setTimeout(close, 1200));
    modal.addEventListener('keydown', event => {
      if (event.key === 'Escape') return close();
      if (event.key !== 'Tab') return;
      const focusable = Array.from(modal.querySelectorAll(focusableSelector));
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initializeForms();
    initializeMeetingModal();
    renderTurnstileWidgets();
  });
})();
