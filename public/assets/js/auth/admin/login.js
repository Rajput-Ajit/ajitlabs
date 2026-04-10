/**
 * auth/admin/login.js
 * Clean login using PHP cookie (no JS cookie handling)
 */

document.addEventListener('DOMContentLoaded', () => {

  // Redirect if already logged in (optional for now)
  
  //Guard.redirectIfLoggedIn('admin');

  const form = Dom.el('#login-form');
  const btn  = Dom.el('#login-btn');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    Form.clearErrors(form);

    const body = Form.serialize(form);

    // Basic validation
    if (!body.email || !body.password) {
      UI.toast.warning('Please fill in all fields.');
      return;
    }

    const originalText = Form.disable(btn, 'Logging in...');

    try {
      // 🔥 IMPORTANT: auth:false (no token attached)
      const res = await Api.post('/app/api/admin.login.php', body, {
        auth: false,     // don't send token
        loader: true,    // show loader
        toast: false     // we handle toast manually
      });

      // ✅ No token handling here (PHP already sets cookie)

      UI.toast.success(res.message || 'Login successful!');
      // save token
      Api.saveToken(res.token);
      // Small delay so user sees toast
      setTimeout(() => {
        window.location.href = Api.CONFIG.baseUrl + '/public/admin/admin-dashboard.php';;
      }, 800);

    } catch (err) {
      // Error toast already handled by Api
      Form.enable(btn, originalText);
    }
  });

});