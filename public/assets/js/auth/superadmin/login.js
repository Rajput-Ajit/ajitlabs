/**
 * auth/superadmin/login.js — Super Admin Login
 * ──────────────────────────────────────────────
 * Depends on: core/api.js, core/helpers.js, core/ui.js
 *
 * Expected HTML:
 *   <form id="login-form">
 *     <input name="email"    type="email">
 *     <input name="password" type="password">
 *     <button type="submit" id="login-btn">Login</button>
 *   </form>
 */

document.addEventListener('DOMContentLoaded', () => {

  Guard.redirectIfLoggedIn('superadmin', '/public/superadmin/system.php');

  const form = Dom.el('#login-form');
  const btn  = Dom.el('#login-btn');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    Form.clearErrors(form);

    const body = Form.serialize(form);

    if (!body.email || !body.password) {
      UI.toast.warning('Email and password are required.');
      return;
    }

    Form.disable(btn, 'Logging in...');

    try {
      // Super admin login uses its own endpoint (future)
      // For now point to admin login — swap when superadmin login API is built
      const res = await Api.post('/app/api/admin.login.php', body);

      Api.saveToken(res.token);
      UI.toast.success('Welcome, Super Admin!');

      setTimeout(() => {
        window.location.href = '/public/superadmin/system.php';
      }, 800);

    } catch (_) {
      Form.enable(btn, 'Login');
    }
  });

});
