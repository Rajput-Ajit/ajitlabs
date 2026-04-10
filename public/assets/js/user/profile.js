/**
 * user/profile.js — Student Profile Page
 * ─────────────────────────────────────────
 * Loads student profile and allocation history.
 */

document.addEventListener('DOMContentLoaded', async () => {

  Guard.requireAuth('user');

  try {
    const res = await Api.post('/app/api/user.profile.data.php', {}, {
      loader: true,
      toast:  false,
    });

    const p = res.profile || res;

    // Fill profile fields
    Dom.els('[data-profile]').forEach(el => {
      const key = el.dataset.profile;
      if (p[key] !== undefined) Dom.text(el, p[key]);
    });

    // Format phone
    Dom.els('[data-profile="contact"]').forEach(el => {
      Dom.text(el, Format.phone(p.contact));
    });

    // QR token display
    const qrEl = Dom.el('#qr-token');
    if (qrEl && p.qr_token) Dom.text(qrEl, p.qr_token);

    // Initials avatar
    const avatarEl = Dom.el('#initials-avatar');
    if (avatarEl) {
      Dom.text(avatarEl, Format.initials(`${p.first_name} ${p.last_name || ''}`));
    }

  } catch (_) {}

  Dom.on('#logout-btn', 'click', () => Api.logout('user'));

});
