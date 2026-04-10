/**
 * auth/user/register.js — Student Registration + OTP Flow
 * ──────────────────────────────────────────────────────────
 * Depends on: core/api.js, core/helpers.js, core/ui.js
 *
 * Flow:
 *   Step 1 — Fill form (name, email, mobile, password, reading_name, city)
 *   Step 2 — Send email OTP  → verify email OTP
 *   Step 3 — Send mobile OTP → verify mobile OTP
 *   Step 4 — Submit registration
 *
 * Expected HTML:
 *   <form id="register-form">
 *     <input name="first_name"> <input name="last_name">
 *     <input name="email">    <button id="send-email-otp-btn">Send OTP</button>
 *     <div id="email-otp-row" hidden>
 *       <input name="email_otp"> <button id="verify-email-otp-btn">Verify</button>
 *     </div>
 *     <input name="mobile">   <button id="send-mobile-otp-btn">Send OTP</button>
 *     <div id="mobile-otp-row" hidden>
 *       <input name="mobile_otp"> <button id="verify-mobile-otp-btn">Verify</button>
 *     </div>
 *     <input name="reading_name"> <input name="city">
 *     <input name="password">
 *     <button type="submit" id="register-btn" disabled>Register</button>
 *   </form>
 */

document.addEventListener('DOMContentLoaded', () => {

  Guard.redirectIfLoggedIn('admin');

  // Track verification state
  const state = { emailVerified: false, mobileVerified: false };

  // ── Send Email OTP ──────────────────────────────────────────────────────

  Dom.on('#send-email-otp-btn', 'click', async () => {
    const email = Dom.el('[name="email"]')?.value?.trim();
    if (!email) { UI.toast.warning('Enter your email first.'); return; }

    const btn = Dom.el('#send-email-otp-btn');
    Form.disable(btn, 'Sending...');

    try {
      await Api.post('/app/api/send-email-otp.php', { email }, { toast: true, successMsg: 'OTP sent to your email.' });
      Dom.show(Dom.el('#email-otp-row'));
      _startCooldown(btn, 'Send OTP', 60);
    } catch (_) {
      Form.enable(btn, 'Send OTP');
    }
  });

  // ── Verify Email OTP ────────────────────────────────────────────────────

  Dom.on('#verify-email-otp-btn', 'click', async () => {
    const email = Dom.el('[name="email"]')?.value?.trim();
    const otp   = Dom.el('[name="email_otp"]')?.value?.trim();
    if (!otp) { UI.toast.warning('Enter the OTP first.'); return; }

    const btn = Dom.el('#verify-email-otp-btn');
    Form.disable(btn, 'Verifying...');

    try {
      await Api.post('/app/api/verify-email-otp.php', { email, otp }, { toast: true, successMsg: 'Email verified ✓' });
      state.emailVerified = true;
      btn.textContent = '✓ Verified';
      btn.style.background = '#0e6b3f';
      Dom.el('[name="email"]').readOnly = true;
      Dom.el('[name="email_otp"]').readOnly = true;
      _checkAllVerified();
    } catch (_) {
      Form.enable(btn, 'Verify');
    }
  });

  // ── Send Mobile OTP ─────────────────────────────────────────────────────

  Dom.on('#send-mobile-otp-btn', 'click', async () => {
    const mobile = Dom.el('[name="mobile"]')?.value?.trim();
    if (!mobile) { UI.toast.warning('Enter your mobile number first.'); return; }

    const btn = Dom.el('#send-mobile-otp-btn');
    Form.disable(btn, 'Sending...');

    try {
      await Api.post('/app/api/send-mobile-otp.php', { mobile }, { toast: true, successMsg: 'OTP sent to your mobile.' });
      Dom.show(Dom.el('#mobile-otp-row'));
      _startCooldown(btn, 'Send OTP', 60);
    } catch (_) {
      Form.enable(btn, 'Send OTP');
    }
  });

  // ── Verify Mobile OTP ───────────────────────────────────────────────────

  Dom.on('#verify-mobile-otp-btn', 'click', async () => {
    const mobile = Dom.el('[name="mobile"]')?.value?.trim();
    const otp    = Dom.el('[name="mobile_otp"]')?.value?.trim();
    if (!otp) { UI.toast.warning('Enter the OTP first.'); return; }

    const btn = Dom.el('#verify-mobile-otp-btn');
    Form.disable(btn, 'Verifying...');

    try {
      await Api.post('/app/api/verify-mobile-otp.php', { mobile, otp }, { toast: true, successMsg: 'Mobile verified ✓' });
      state.mobileVerified = true;
      btn.textContent = '✓ Verified';
      btn.style.background = '#0e6b3f';
      Dom.el('[name="mobile"]').readOnly = true;
      Dom.el('[name="mobile_otp"]').readOnly = true;
      _checkAllVerified();
    } catch (_) {
      Form.enable(btn, 'Verify');
    }
  });

  // ── Final Registration Submit ────────────────────────────────────────────

  const form = Dom.el('#register-form');
  const registerBtn = Dom.el('#register-btn');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!state.emailVerified || !state.mobileVerified) {
        UI.toast.warning('Please verify both email and mobile before registering.');
        return;
      }

      const body = Form.serialize(form);
      Form.disable(registerBtn, 'Creating account...');

      try {
        const res = await Api.post('/app/api/admin.register.php', {
          first_name:   body.first_name,
          last_name:    body.last_name,
          email:        body.email,
          mobile:       body.mobile,
          password:     body.password,
          reading_name: body.reading_name,
          city:         body.city,
        });

        Api.saveToken(res.token);
        UI.toast.success('Account created successfully!');

        setTimeout(() => {
          window.location.href = '/public/admin/dashboard.php';
        }, 1000);

      } catch (_) {
        Form.enable(registerBtn, 'Register');
      }
    });
  }

  // ── Helpers ──────────────────────────────────────────────────────────────

  function _checkAllVerified() {
    if (state.emailVerified && state.mobileVerified) {
      if (registerBtn) registerBtn.disabled = false;
    }
  }

  /** 60-second cooldown on OTP send buttons */
  function _startCooldown(btn, originalText, seconds) {
    let remaining = seconds;
    btn.disabled = true;
    btn.textContent = `Resend in ${remaining}s`;

    const interval = setInterval(() => {
      remaining--;
      btn.textContent = `Resend in ${remaining}s`;
      if (remaining <= 0) {
        clearInterval(interval);
        Form.enable(btn, originalText);
      }
    }, 1000);
  }

});
