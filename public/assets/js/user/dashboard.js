/**
 * user/dashboard.js — Student Dashboard
 * ────────────────────────────────────────
 * Shows student's active seat, shift, expiry, and fee status.
 *
 * Expected HTML:
 *   [data-stat="seat_number"]  [data-stat="shift_name"]
 *   [data-stat="expiry_date"]  [data-stat="hall_name"]
 *   [data-stat="fee_status"]   #logout-btn
 */

document.addEventListener('DOMContentLoaded', async () => {

  Guard.requireAuth('user');

  try {
    const res = await Api.post('/app/api/user.dashboard.data.php', {}, {
      loader: true,
      toast:  false,
    });

    const s = res.data || res;

    const stats = {
      seat_number: s.seat_number  || '—',
      shift_name:  s.shift_name   || '—',
      expiry_date: Format.date(s.expiry_date),
      hall_name:   s.hall_name    || '—',
      fee_status:  Format.feeStatusBadge(s.fee_status),
    };

    Object.entries(stats).forEach(([key, val]) => {
      Dom.els(`[data-stat="${key}"]`).forEach(el => Dom.html(el, val));
    });

  } catch (_) {}

  Dom.on('#logout-btn', 'click', () => Api.logout('user'));

});
