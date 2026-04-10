/**
 * helpers.js — Form + DOM + Formatting Utilities
 * ────────────────────────────────────────────────
 * Provides:
 *   Form.serialize(formEl)           → plain object from form fields
 *   Form.setErrors(fields)           → show inline validation errors
 *   Form.clearErrors(formEl)         → clear all inline errors
 *   Form.disable(btnEl, text)        → disable button + show loading text
 *   Form.enable(btnEl, originalText) → re-enable button
 *   Form.reset(formEl)               → reset + clear errors
 *
 *   Dom.el(selector)                 → querySelector shorthand
 *   Dom.els(selector)                → querySelectorAll shorthand
 *   Dom.on(el, event, fn)            → addEventListener shorthand
 *   Dom.show(el)                     → remove hidden class / set display
 *   Dom.hide(el)                     → add hidden / set display:none
 *   Dom.toggle(el, condition)        → show/hide based on boolean
 *   Dom.html(el, html)               → set innerHTML safely
 *   Dom.text(el, text)               → set textContent
 *
 *   Format.currency(n)               → ₹1,200.00
 *   Format.date(str)                 → 06 Apr 2026
 *   Format.dateTime(str)             → 06 Apr 2026 10:30 AM
 *   Format.phone(str)                → +91 98765 43210
 *   Format.initials(name)            → "Ajit Rajput" → "AR"
 *   Format.relativeTime(dateStr)     → "2 hours ago"
 *   Format.capitalize(str)           → "hello world" → "Hello World"
 *   Format.truncate(str, n)          → "Long tex..." 
 *
 *   Guard.requireAuth(role)          → redirect if no token
 *   Guard.redirectIfLoggedIn(role)   → redirect away from login page if already logged in
 *
 * No dependencies — pure vanilla JS.
 * Depends on: core/api.js (for Guard helpers only)
 */

// ─── Form ────────────────────────────────────────────────────────────────────

const Form = (() => {

  /**
   * Serialize a <form> element into a plain object.
   * Handles text, email, number, select, textarea, checkbox.
   * Ignores disabled fields and submit buttons.
   */
  function serialize(formEl) {
    if (!formEl) return {};
    const data = {};
    const elements = formEl.querySelectorAll(
      'input:not([disabled]):not([type="submit"]):not([type="button"]):not([type="reset"]), select:not([disabled]), textarea:not([disabled])'
    );

    elements.forEach(el => {
      const name = el.name || el.dataset.name;
      if (!name) return;

      if (el.type === 'checkbox') {
        data[name] = el.checked;
      } else if (el.type === 'number') {
        data[name] = el.value !== '' ? Number(el.value) : null;
      } else {
        data[name] = el.value.trim();
      }
    });

    return data;
  }

  /**
   * Show inline validation errors under specific fields.
   * @param {object} fields  - { fieldName: 'error message', ... }
   *                           OR pass a single string as the first arg to show a global form error
   * Expects: <input name="email"> followed by <span class="field-error" data-field="email">
   * OR wraps the input and adds a span automatically.
   */
  function setErrors(fields) {
    if (typeof fields === 'string') {
      // Single string → show as a generic form error
      const existing = document.querySelector('.mrh-form-error');
      if (existing) existing.textContent = fields;
      return;
    }

    Object.entries(fields).forEach(([fieldName, message]) => {
      // Look for explicit error span
      let errorEl = document.querySelector(`[data-field="${fieldName}"].field-error`);

      if (!errorEl) {
        // Find the input and create error span after it
        const input = document.querySelector(`[name="${fieldName}"]`);
        if (!input) return;

        errorEl = document.createElement('span');
        errorEl.className = 'field-error';
        errorEl.dataset.field = fieldName;
        errorEl.style.cssText = `
          display: block;
          font-size: 12px;
          color: #f87171;
          margin-top: 4px;
          font-weight: 500;
        `;
        input.parentNode.insertBefore(errorEl, input.nextSibling);

        // Add red border to input
        input.style.borderColor = '#f87171';
        input.addEventListener('input', () => {
          input.style.borderColor = '';
          errorEl.textContent = '';
        }, { once: true });
      }

      errorEl.textContent = message;
    });
  }

  /**
   * Clear all field-error spans inside a form.
   */
  function clearErrors(formEl) {
    const scope = formEl || document;
    scope.querySelectorAll('.field-error').forEach(el => el.textContent = '');
    scope.querySelectorAll('[style*="border-color"]').forEach(el => {
      el.style.borderColor = '';
    });
  }

  /**
   * Disable a submit button and show loading state.
   * @returns original button text (pass to Form.enable to restore)
   */
  function disable(btnEl, text = 'Please wait...') {
    if (!btnEl) return '';
    const original = btnEl.textContent;
    btnEl.disabled = true;
    btnEl.dataset.originalText = original;
    btnEl.textContent = text;
    btnEl.style.opacity = '0.65';
    btnEl.style.cursor = 'not-allowed';
    return original;
  }

  /**
   * Re-enable a button and restore its text.
   */
  function enable(btnEl, originalText) {
    if (!btnEl) return;
    btnEl.disabled = false;
    btnEl.textContent = originalText || btnEl.dataset.originalText || 'Submit';
    btnEl.style.opacity = '';
    btnEl.style.cursor = '';
  }

  /**
   * Reset a form element and clear all errors.
   */
  function reset(formEl) {
    if (!formEl) return;
    formEl.reset();
    clearErrors(formEl);
  }

  return { serialize, setErrors, clearErrors, disable, enable, reset };

})();

// ─── Dom ─────────────────────────────────────────────────────────────────────

const Dom = (() => {

  const el  = (selector, ctx = document) => ctx.querySelector(selector);
  const els = (selector, ctx = document) => [...ctx.querySelectorAll(selector)];

  const on = (target, event, fn, opts) => {
    if (!target) return;
    // Support selector string or element
    if (typeof target === 'string') {
      document.querySelectorAll(target).forEach(t => t.addEventListener(event, fn, opts));
    } else {
      target.addEventListener(event, fn, opts);
    }
  };

  const show = (el, displayType = 'block') => {
    if (!el) return;
    el.style.display = displayType;
    el.classList.remove('hidden', 'd-none');
  };

  const hide = (el) => {
    if (!el) return;
    el.style.display = 'none';
  };

  const toggle = (el, condition, displayType = 'block') => {
    if (!el) return;
    condition ? show(el, displayType) : hide(el);
  };

  // Set innerHTML — basic sanitize: strip <script> tags
  const html = (el, markup) => {
    if (!el) return;
    el.innerHTML = markup.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
  };

  const text = (el, str) => {
    if (!el) return;
    el.textContent = str;
  };

  // Insert HTML into every matched element by selector
  const fill = (selector, markup) => {
    document.querySelectorAll(selector).forEach(el => html(el, markup));
  };

  // Empty a container (remove all children)
  const empty = (el) => {
    if (!el) return;
    while (el.firstChild) el.removeChild(el.firstChild);
  };

  // Add / remove class convenience
  const addClass    = (el, cls) => el && el.classList.add(cls);
  const removeClass = (el, cls) => el && el.classList.remove(cls);
  const hasClass    = (el, cls) => el && el.classList.contains(cls);

  return {
    el, els, on, show, hide, toggle, html, text, fill, empty,
    addClass, removeClass, hasClass
  };

})();

// ─── Format ──────────────────────────────────────────────────────────────────

const Format = (() => {

  /** ₹1,200.00 */
  function currency(n, symbol = '₹') {
    if (n === null || n === undefined || isNaN(n)) return `${symbol}0.00`;
    return symbol + Number(n).toLocaleString('en-IN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  /** "2026-04-06" → "06 Apr 2026" */
  function date(str) {
    if (!str) return '—';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  /** "2026-04-06T10:30:00" → "06 Apr 2026  10:30 AM" */
  function dateTime(str) {
    if (!str) return '—';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('en-IN', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: true
    });
  }

  /** "9876543210" → "+91 98765 43210" */
  function phone(str) {
    if (!str) return '—';
    const digits = str.replace(/\D/g, '');
    if (digits.length === 10) {
      return `+91 ${digits.slice(0,5)} ${digits.slice(5)}`;
    }
    return str;
  }

  /** "Ajit Rajput" → "AR" */
  function initials(name) {
    if (!name) return '?';
    return name
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map(w => w[0].toUpperCase())
      .join('');
  }

  /** "2 hours ago", "just now", "3 days ago" */
  function relativeTime(dateStr) {
    if (!dateStr) return '—';
    const diff = Date.now() - new Date(dateStr).getTime();
    const secs  = Math.floor(diff / 1000);
    const mins  = Math.floor(secs  / 60);
    const hours = Math.floor(mins  / 60);
    const days  = Math.floor(hours / 24);

    if (secs  < 10)  return 'just now';
    if (secs  < 60)  return `${secs}s ago`;
    if (mins  < 60)  return `${mins}m ago`;
    if (hours < 24)  return `${hours}h ago`;
    if (days  < 30)  return `${days}d ago`;
    return date(dateStr);
  }

  /** "hello world" → "Hello World" */
  function capitalize(str) {
    if (!str) return '';
    return str.replace(/\b\w/g, c => c.toUpperCase());
  }

  /** "Long text here..." truncated at n chars */
  function truncate(str, n = 40) {
    if (!str) return '';
    return str.length <= n ? str : str.slice(0, n - 3) + '...';
  }

  /** "morning" → "Morning Shift" */
  function shiftLabel(code) {
    const map = {
      morning: 'Morning',
      evening: 'Evening',
      fullday: 'Full Day',
      night:   'Night',
    };
    return map[code] || capitalize(code);
  }

  /** badge HTML for seat status */
  function seatStatusBadge(status) {
    const map = {
      empty:   { label: 'Available', cls: 'badge-success' },
      morning: { label: 'Morning',   cls: 'badge-warning' },
      evening: { label: 'Evening',   cls: 'badge-info'    },
      fullday: { label: 'Full Day',  cls: 'badge-danger'  },
      removed: { label: 'Removed',   cls: 'badge-muted'   },
    };
    const s = map[status] || { label: capitalize(status), cls: 'badge-muted' };
    return `<span class="badge ${s.cls}">${s.label}</span>`;
  }

  /** fee status badge */
  function feeStatusBadge(status) {
    const map = {
      paid:    { label: 'Paid',    cls: 'badge-success' },
      pending: { label: 'Pending', cls: 'badge-warning' },
      failed:  { label: 'Failed',  cls: 'badge-danger'  },
      partial: { label: 'Partial', cls: 'badge-info'    },
    };
    const s = map[status] || { label: capitalize(status), cls: 'badge-muted' };
    return `<span class="badge ${s.cls}">${s.label}</span>`;
  }

  return {
    currency, date, dateTime, phone, initials,
    relativeTime, capitalize, truncate,
    shiftLabel, seatStatusBadge, feeStatusBadge,
  };

})();

// ─── Guard ───────────────────────────────────────────────────────────────────

const Guard = (() => {

  /**
   * Redirect to login if no token found in cookie.
   * Call at top of every protected page's JS file.
   * @param {'admin'|'user'|'superadmin'} role
   */
  function requireAuth(role = 'admin') {
    if (!Api.isLoggedIn()) {
      const pages = Api.CONFIG.loginPages;
      window.location.href = (pages[role] || pages.admin) + '?reason=not_logged_in';
    }
  }

  /**
   * Redirect away from login/register page if already logged in.
   * @param {'admin'|'user'|'superadmin'} role
   * @param {string} dashboardUrl
   */
  function redirectIfLoggedIn(role = 'admin', dashboardUrl) {
    if (Api.isLoggedIn()) {
      const defaults = {
        admin:      '/public/admin/dashboard.php',
        user:       '/public/user/dashboard.php',
        superadmin: '/public/superadmin/system.php',
      };
      window.location.href = dashboardUrl || defaults[role] || defaults.admin;
    }
  }

  return { requireAuth, redirectIfLoggedIn };

})();

// ─── Global exports ──────────────────────────────────────────────────────────
window.Form   = Form;
window.Dom    = Dom;
window.Format = Format;
window.Guard  = Guard;
