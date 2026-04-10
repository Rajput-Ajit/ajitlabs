/**
 * auth/user/login.js — Student Login Page
 * ─────────────────────────────────────────
 * Students login with mobile + OTP (no password).
 *
 * Expected HTML:
 *   <form id="login-form">
 *     <input name="mobile" type="tel">
 *     <div id="otp-row" hidden>
 *       <input name="otp">
 *     </div>
 *     <button id="send-otp-btn" type="button">Send OTP</button>
 *     <button id="login-btn"   type="submit"  hidden>Login</button>
 *   </form>
 */

document.addEventListener('DOMContentLoaded', () => {

  Guard.redirectIfLoggedIn('user', '/public/user/dashboard.php');

  const sendBtn = Dom.el('#send-otp-btn');
  const loginBtn = Dom.el('#login-btn');
  const form    = Dom.el('#login-form');
  let   otpSent = false;

  // ── Step 1: Send OTP ────────────────────────────────────────────────────

  Dom.on(sendBtn, 'click', async () => {
    const mobile = Dom.el('[name="mobile"]')?.value?.trim();
    if (!mobile) { UI.toast.warning('Enter your mobile number.'); return; }

    Form.disable(sendBtn, 'Sending...');

    try {
      // Students use mobile OTP for login — reuse the same endpoint
      await Api.post('/app/api/send-mobile-otp.php', { mobile }, {
        toast: true, successMsg: 'OTP sent to your mobile number.'
      });

      otpSent = true;
      Dom.show(Dom.el('#otp-row'));
      Dom.show(loginBtn);
      Dom.hide(sendBtn);

    } catch (_) {
      Form.enable(sendBtn, 'Send OTP');
    }
  });

  // ── Step 2: Verify OTP + Login ──────────────────────────────────────────

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!otpSent) { UI.toast.warning('Please request an OTP first.'); return; }

      const body = Form.serialize(form);
      Form.disable(loginBtn, 'Logging in...');

      try {
        const res = await Api.post('/app/api/verify-mobile-otp.php', {
          mobile: body.mobile,
          otp:    body.otp,
        });

        // On success, server should return token + student data
        if (res.token) Api.saveToken(res.token);
        UI.toast.success('Login successful!');

        setTimeout(() => {
          window.location.href = '/public/user/dashboard.php';
        }, 800);

      } catch (_) {
        Form.enable(loginBtn, 'Login');
      }
    });
  }

});
