/**
 * api.js — Central API Request Handler
 * ──────────────────────────────────────
 * All HTTP calls go through Api.call() or shorthand methods.
 *
 * Features:
 *   • Reads JWT token from cookies automatically on every request
 *   • Shows global loader before request, hides after
 *   • Shows toast on success (optional) and on every error
 *   • Auto force-logout on 401 (invalid/expired token)
 *   • Promise-based — works with async/await
 *   • Zero dependencies — pure vanilla JS
 *
 * Usage:
 *   const data = await Api.post('/app/api/admin.login.php', { email, password });
 *   const data = await Api.get('/app/api/admin.dashboard.php');
 *   const data = await Api.call({ method:'POST', url:'...', body:{}, toast:false });
 *
 * Depends on:
 *   core/ui.js  (must be loaded before this file)
 */

const Api = (() => {

  // ─── Config ──────────────────────────────────────────────────────────────

  const CONFIG = {
    // Base URL prefix — change to '' if APIs are at root
    baseUrl: '',

    // Cookie name where JWT is stored
    tokenCookie: 'mrh_token',

    // Where to redirect on 401 force logout
    // Determined at runtime from current path (admin/user/superadmin)
    loginPages: {
      admin:      '/public/admin/login.php',
      user:       '/public/user/login.php',
      superadmin: '/public/superadmin/login.php',
    },

    // Default timeout in ms
    timeout: 30000,
  };

  // ─── Cookie helpers ───────────────────────────────────────────────────────

  const Cookie = {
    get(name) {
      const match = document.cookie
        .split('; ')
        .find(row => row.startsWith(name + '='));
      return match ? decodeURIComponent(match.split('=')[1]) : null;
    },

    set(name, value, days = 30, sameSite = 'Lax') {
      const expires = new Date(Date.now() + days * 864e5).toUTCString();
      document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=${sameSite}`;
    },

    remove(name) {
      // Expire immediately across all possible paths
      document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
      document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/app`;
      document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/public`;
    }
  };

  // ─── Token helpers ────────────────────────────────────────────────────────

  const Token = {
    get()         { return Cookie.get(CONFIG.tokenCookie); },
    set(token)    { Cookie.set(CONFIG.tokenCookie, token, 30); },
    remove()      { Cookie.remove(CONFIG.tokenCookie); },
    exists()      { return !!Cookie.get(CONFIG.tokenCookie); },
  };

  // ─── Force logout ─────────────────────────────────────────────────────────

  /**
   * Remove token and redirect to the correct login page
   * based on current URL path segment (/admin/, /user/, /superadmin/).
   */
  function _forceLogout(message = 'Session expired. Please login again.') {
    Token.remove();
    UI.loader.forceHide();
    UI.toast.error(message, 0); // sticky toast — 0 = no auto dismiss

    // Detect role from current URL
    const path = window.location.pathname.toLowerCase();
    let loginUrl = CONFIG.loginPages.admin; // default

    if (path.includes('/superadmin/')) {
      loginUrl = CONFIG.loginPages.superadmin;
    } else if (path.includes('/user/')) {
      loginUrl = CONFIG.loginPages.user;
    }

    // Small delay so user reads the toast before redirect
    setTimeout(() => {
      window.location.href = loginUrl + '?reason=session_expired';
    }, 1800);
  }

  // ─── Core request ─────────────────────────────────────────────────────────

  async function _refreshAccessToken() {

    try {

      const response = await fetch(
        CONFIG.baseUrl + '/auth/refresh-token.php',
        {
          method: 'POST',

          credentials: 'include',

          headers: {
            'Accept': 'application/json'
          }
        }
      );

      const json = await response.json();

      if (!response.ok) {
        throw json;
      }

      // save new access token
      if (json.token) {
        Token.set(json.token);
      }

      return json;

    } catch (err) {

      throw err;
    }
  }

  function _isSessionExpired(message = '') {

    const msg = message.toLowerCase();

    return (
      msg.includes('Access token expired')
    );
  }

  async function _retryRequest(options) {

    return await call({
      ...options,

      // prevent infinite loop
      _retry: true,

      loader: false,
      toast: false,
      errorToast: false
    });
  }
  /**
   * Make an API request.
   *
   * @param {object} options
   * @param {string}  options.url         - API endpoint (relative or absolute)
   * @param {string}  [options.method]    - HTTP method (default: 'POST')
   * @param {object}  [options.body]      - Request payload (JSON)
   * @param {boolean} [options.auth]      - Attach Bearer token? (default: true)
   * @param {boolean} [options.loader]    - Show global loader? (default: true)
   * @param {boolean} [options.toast]     - Show success toast? (default: false)
   * @param {string}  [options.successMsg]- Override success toast message
   * @param {boolean} [options.errorToast]- Show error toast? (default: true)
   *
   * @returns {Promise<any>}  Resolves with response `data` field on success.
   *                          Rejects with error object on failure.
   *
   * @throws  Never — all errors are caught, toast shown, and rejection returned.
   */
  async function call(options = {}) {
    const {
      url,
      method      = 'POST',
      body        = null,
      auth        = true,
      loader      = true,
      toast       = false,
      successMsg  = null,
      errorToast  = true,

      // NEW
      _retry      = false,
    } = options;

    if (!url) {
      console.error('[Api] url is required');
      return Promise.reject({ message: 'No URL provided' });
    }

    // ── Show loader ──
    if (loader) UI.loader.show();

    // ── Build headers ──
    const headers = {
      'Content-Type': 'application/json',
      'Accept':       'application/json',
    };

    if (auth) {
      const token = Token.get();
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
    }

    // ── Build fetch options ──
    const fetchOptions = { method: method.toUpperCase(), headers, credentials: "include" };

    if (body && !['GET', 'HEAD'].includes(fetchOptions.method)) {
      fetchOptions.body = JSON.stringify(body);
    }

    // ── Timeout via AbortController ──
    const controller  = new AbortController();
    const timeoutId   = setTimeout(() => controller.abort(), CONFIG.timeout);
    fetchOptions.signal = controller.signal;

    try {
      const response = await fetch(CONFIG.baseUrl + url, fetchOptions);
      clearTimeout(timeoutId);

      // ── Parse JSON ──
      let json;
      try {
        json = await response.json();
      } catch (_) {
        throw { message: 'Server returned invalid response. Please try again.' };
      }

      // ── 401 → force logout ──
      // ── 401 Unauthorized ──
      if (response.status === 401) {

        const msg = json.message || 'Unauthorized';

        // stop infinite retry loop
        if (_retry) {

          if (loader) UI.loader.hide();

          _forceLogout(msg);

          return Promise.reject(json);
        }

        const isSessionIssue =
          auth && _isSessionExpired(msg);

        // token/session issue
        if (isSessionIssue) {

          try {

            // try refresh token
            await _refreshAccessToken();

            // retry original request
            return await _retryRequest(options);

          } catch (refreshError) {

            if (loader) UI.loader.hide();

            _forceLogout(
              refreshError.message ||
              'Session expired'
            );

            return Promise.reject(refreshError);
          }
        }

        // normal unauthorized
        if (loader) UI.loader.hide();

        if (errorToast) {
          UI.toast.error(msg);
        }

        return Promise.reject(json);
      }


      // ── 403 read-only ──
      if (response.status === 403) {
        const msg = json.message || 'Access denied.';
        if (errorToast) UI.toast.error(msg);
        if (loader) UI.loader.hide();
        return Promise.reject(json);
      }

      // ── Non-2xx errors ──
      if (!response.ok || json.status === 'error') {
        const msg = json.message || `Request failed (${response.status})`;
        if (errorToast) UI.toast.error(msg);
        if (loader) UI.loader.hide();
        return Promise.reject(json);
      }

      // ── Success ──
      if (loader) UI.loader.hide();
      if (toast) {
        const msg = successMsg || json.message || 'Done!';
        UI.toast.success(msg);
      }

      // Return the full json (caller can destructure what they need)
      return json;

    } catch (err) {
      clearTimeout(timeoutId);
      if (loader) UI.loader.hide();

      // Network error or AbortError (timeout)
      if (err.name === 'AbortError') {
        if (errorToast) UI.toast.error('Request timed out. Check your connection.');
        return Promise.reject({ message: 'Request timed out' });
      }

      // Already-handled 401/403 errors — don't double toast
      if (err.status === 401 || err.status === 403) {
        return Promise.reject(err);
      }

      const msg = err.message || 'Network error. Please check your connection.';
      if (errorToast) UI.toast.error(msg);
      return Promise.reject(err);
    }
  }

  // ─── Shorthand methods ────────────────────────────────────────────────────

  /**
   * POST request (most common — used for all data APIs)
   * @param {string} url
   * @param {object} body
   * @param {object} [opts] - extra options merged into call()
   */
  function post(url, body = {}, opts = {}) {
    return call({ method: 'POST', url, body, ...opts });
  }

  /**
   * GET request
   * @param {string} url
   * @param {object} [opts]
   */
  function get(url, opts = {}) {
    return call({ method: 'GET', url, body: null, ...opts });
  }

  /**
   * PUT request
   */
  function put(url, body = {}, opts = {}) {
    return call({ method: 'PUT', url, body, ...opts });
  }

  /**
   * DELETE request
   */
  function del(url, body = {}, opts = {}) {
    return call({ method: 'DELETE', url, body, ...opts });
  }

  // ─── Auth helpers  ────────────────────────────────────────────────────────

  /**
   * Save JWT token to cookie after successful login.
   * Call this in your login.js after Api.post('/login') succeeds.
   */
  function saveToken(token) {
    Token.set(token);
  }

  /**
   * Manually logout — clear token + redirect.
   */
  function logout(role = 'admin') {
    Token.remove();
    const loginUrl = CONFIG.loginPages[role] || CONFIG.loginPages.admin;
    window.location.href = loginUrl;
  }

  /**
   * Check if user is logged in (token exists in cookie).
   * Does NOT validate the token — server validates on each request.
   */
  function isLoggedIn() {
    return Token.exists();
  }

  // ─── Public API ──────────────────────────────────────────────────────────

  return {
    call,
    post,
    get,
    put,
    delete: del,
    saveToken,
    logout,
    isLoggedIn,
    Cookie,   // exported for edge cases
    Token,    // exported so login.js can call Api.Token.set()
    CONFIG,   // exported so app can override baseUrl etc.
  };

})();

// Global export
window.Api = Api;
