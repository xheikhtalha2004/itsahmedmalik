(async function () {
  const session = await requireAuth();
  if (!session) return;

  let currentFilter = 'all';
  let rows = [];

  const tbody = document.getElementById('submissions-body');
  const tabs = document.querySelectorAll('.admin-tab');

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  function detailsFor(row) {
    if (row.type === 'meeting') {
      return `${escapeHtml(row.meeting_date)} at ${escapeHtml(row.meeting_time)}`;
    }
    return `<span title="${escapeHtml(row.message)}">${escapeHtml((row.message || '').slice(0, 60))}${(row.message || '').length > 60 ? '…' : ''}</span>`;
  }

  function render() {
    const filtered = currentFilter === 'all' ? rows : rows.filter((r) => r.type === currentFilter);

    if (!filtered.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="admin-empty">No submissions yet.</td></tr>';
      return;
    }

    tbody.innerHTML = filtered
      .map(
        (row) => `
        <tr data-id="${row.id}">
          <td>${new Date(row.created_at).toLocaleString()}</td>
          <td>${row.type === 'meeting' ? 'Meeting' : 'Contact'}</td>
          <td>${escapeHtml(row.full_name)}</td>
          <td>${escapeHtml(row.email)}</td>
          <td class="wrap">${detailsFor(row)}</td>
          <td><span class="admin-badge ${row.status}">${row.status}</span></td>
          <td>
            ${row.status !== 'read' ? `<button class="admin-row-btn" data-action="read">Mark Read</button>` : ''}
            ${row.status !== 'archived' ? `<button class="admin-row-btn" data-action="archive">Archive</button>` : ''}
            <button class="admin-row-btn" data-action="delete">Delete</button>
          </td>
        </tr>`
      )
      .join('');
  }

  async function load() {
    const { data, error } = await window.adminSupabase
      .from('submissions')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) {
      tbody.innerHTML = `<tr><td colspan="7" class="admin-empty">Error loading submissions: ${escapeHtml(error.message)}</td></tr>`;
      return;
    }
    rows = data || [];
    render();
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((t) => t.classList.remove('is-active'));
      tab.classList.add('is-active');
      currentFilter = tab.dataset.tab;
      render();
    });
  });

  tbody.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const id = btn.closest('tr').dataset.id;
    const action = btn.dataset.action;

    if (action === 'delete') {
      if (!confirm('Delete this submission permanently?')) return;
      await window.adminSupabase.from('submissions').delete().eq('id', id);
    } else {
      const status = action === 'read' ? 'read' : 'archived';
      await window.adminSupabase.from('submissions').update({ status }).eq('id', id);
    }
    await load();
  });

  await load();
})();
