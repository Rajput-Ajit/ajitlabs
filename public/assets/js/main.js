/**
 * main.js — Global Initialization
 * ─────────────────────────────────
 * This file is optional — most pages only need the core files
 * plus their own page-specific JS.
 *
 * REQUIRED LOAD ORDER (add to every HTML page <head> or before </body>):
 * ───────────────────────────────────────────────────────────────────────
 *   1.  core/ui.js       ← loader + toast (no deps)
 *   2.  core/api.js      ← HTTP handler   (needs ui.js)
 *   3.  core/helpers.js  ← Form/Dom/Format/Guard (needs api.js for Guard)
 *   4.  [page].js        ← page-specific file (needs all 3 above)
 *
 * HTML example:
 * ─────────────────────────────────
 *   <script src="/assets/js/core/ui.js"></script>
 *   <script src="/assets/js/core/api.js"></script>
 *   <script src="/assets/js/core/helpers.js"></script>
 *   <script src="/assets/js/admin/dashboard.js"></script>
 *
 * CONFIG (override in main.js or inline before loading api.js):
 * ─────────────────────────────────────────────────────────────
 *   Api.CONFIG.baseUrl      = '';          // prefix for all API calls
 *   Api.CONFIG.tokenCookie  = 'mrh_token'; // cookie name
 *   Api.CONFIG.timeout      = 30000;       // ms
 *   Api.CONFIG.loginPages.admin      = '/public/admin/login.php';
 *   Api.CONFIG.loginPages.user       = '/public/user/login.php';
 *   Api.CONFIG.loginPages.superadmin = '/public/superadmin/login.php';
 *
 * GLOBAL OBJECTS (available on window after core files load):
 * ────────────────────────────────────────────────────────────
 *   window.UI      → { loader, toast }
 *   window.Api     → { post, get, put, delete, call, saveToken, logout, isLoggedIn }
 *   window.Form    → { serialize, setErrors, clearErrors, disable, enable, reset }
 *   window.Dom     → { el, els, on, show, hide, toggle, html, text, fill, empty }
 *   window.Format  → { currency, date, dateTime, phone, initials, relativeTime, ... }
 *   window.Guard   → { requireAuth, redirectIfLoggedIn }
 *
 * USAGE PATTERNS:
 * ────────────────
 *
 * // 1. Simple POST with loader + success toast
 * const res = await Api.post('/app/api/admin.login.php', { email, password }, {
 *   toast: true, successMsg: 'Login successful!'
 * });
 *
 * // 2. Suppress error toast (handle manually)
 * const res = await Api.post('/app/api/...', body, { errorToast: false });
 *
 * // 3. GET without loader
 * const res = await Api.get('/app/api/...', { loader: false });
 *
 * // 4. Show toast manually
 * UI.toast.success('Seat assigned!');
 * UI.toast.error('Something went wrong.');
 * UI.toast.warning('Plan limit reached.');
 * UI.toast.info('Loading data...');
 *
 * // 5. Serialize a form
 * const body = Form.serialize(document.getElementById('my-form'));
 *
 * // 6. Disable button during request
 * const origText = Form.disable(btn, 'Saving...');
 * // ... await Api.post(...)
 * Form.enable(btn, origText);
 *
 * // 7. Protect a page
 * Guard.requireAuth('admin');         // at top of any protected page JS
 * Guard.redirectIfLoggedIn('admin');  // at top of login page JS
 *
 * // 8. Force logout manually
 * Api.logout('admin');
 *
 * // 9. Format helpers
 * Format.currency(1200)         → "₹1,200.00"
 * Format.date('2026-04-06')     → "06 Apr 2026"
 * Format.phone('9876543210')    → "+91 98765 43210"
 * Format.relativeTime(dateStr)  → "2 hours ago"
 */

// Optionally override config here after api.js is loaded:
// Api.CONFIG.baseUrl = 'https://yourdomain.com';

Api.CONFIG.baseUrl = '/ajitlabs';

Api.CONFIG.loginPages = {
  admin: '/ajitlabs/public/admin/admin-login.html',
  user: '/ajitlabs/public/user/login.html',
  superadmin: '/ajitlabs/public/superadmin/login.html',
};