/**
 * superadmin/system.js — Super Admin System Dashboard
 * ──────────────────────────────────────────────────────
 * Loads: all admins list, platform stats, subscription overview.
 *
 * Expected HTML:
 *   [data-stat="total_admins"]  [data-stat="active_admins"]
 *   [data-stat="total_students"] [data-stat="monthly_revenue"]
 *   #admins-table tbody
 *   #logout-btn
 */

document.addEventListener('DOMContentLoaded', async () => {

  Guard.requireAuth('superadmin');

  await loadSystemData();

  Dom.on('#logout-btn', 'click', () => Api.logout('superadmin'));

});

async function loadSystemData() {
  try {
    const res = await Api.post('/app/api/superadmin.system.data.php', {}, {
      loader: true,
      toast:  false,
    });

    // Stats
    const stats = res.stats || {};
    Dom.els('[data-stat]').forEach(el => {
      const key = el.dataset.stat;
      if (stats[key] !== undefined) {
        const val = key.includes('revenue')
          ? Format.currency(stats[key])
          : stats[key];
        Dom.text(el, val);
      }
    });

    // Admins table
    _renderAdminsTable(res.admins || []);

  } catch (_) {}
}

function _renderAdminsTable(admins) {
  const tbody = Dom.el('#admins-table tbody') || Dom.el('#admins-table');
  if (!tbody) return;

  if (!admins.length) {
    Dom.html(tbody, `<tr><td colspan="6" style="text-align:center;padding:24px;color:#888">No admins found.</td></tr>`);
    return;
  }

  Dom.html(tbody, admins.map(a => `
    <tr>
      <td>${Dom._escape(a.first_name + ' ' + (a.last_name || ''))}</td>
      <td>${Dom._escape(a.email)}</td>
      <td>${Dom._escape(a.plan_name || '—')}</td>
      <td>${Format.date(a.plan_expires_at)}</td>
      <td>
        <span style="
          padding: 3px 10px;
          border-radius: 20px;
          font-size: 11px;
          font-weight: 600;
          background: ${a.status === 'active' ? '#0e6b3f44' : '#7f1d1d44'};
          color: ${a.status === 'active' ? '#34d399' : '#f87171'};
        ">${a.status}</span>
      </td>
      <td>
        <button onclick="toggleAdminBlock(${a.id}, '${a.status}')"
          style="font-size:12px;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;
                 background:${a.status === 'active' ? '#7f1d1d' : '#0e6b3f'};color:#fff;">
          ${a.status === 'active' ? 'Block' : 'Unblock'}
        </button>
      </td>
    </tr>
  `).join(''));
}

window.toggleAdminBlock = async function(adminId, currentStatus) {
  const action  = currentStatus === 'active' ? 'block' : 'unblock';
  const confirm = window.confirm(`Are you sure you want to ${action} this admin?`);
  if (!confirm) return;

  try {
    await Api.post('/app/api/superadmin.admin.action.php', {
      admin_id: adminId,
      action,
    }, { toast: true, successMsg: `Admin ${action}ed successfully.` });

    await loadSystemData(); // reload list
  } catch (_) {}
};

Dom._escape = Dom._escape || function(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str || '')));
  return d.innerHTML;
};
