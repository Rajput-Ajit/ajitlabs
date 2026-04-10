/**
 * ui.js — Global Loader + Toast System
 * ─────────────────────────────────────
 * Provides:
 *   UI.loader.show()        — show full-page loader
 *   UI.loader.hide()        — hide loader
 *   UI.toast(msg, type)     — show toast ('success'|'error'|'warning'|'info')
 *   UI.toast.success(msg)
 *   UI.toast.error(msg)
 *   UI.toast.warning(msg)
 *   UI.toast.info(msg)
 *
 * Auto-injects styles + DOM on first call.
 * No dependencies — pure vanilla JS.
 */
const UI = (() => {

  // ─── Styles ──────────────────────────────────────────────────────────────

  const CSS = `
    /* ── Loader Overlay ── */
    #mrh-loader {
      position: fixed;
      inset: 0;
      z-index: 99999;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(10, 14, 26, 0.55);
      backdrop-filter: blur(3px);
      -webkit-backdrop-filter: blur(3px);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s ease;
    }
    #mrh-loader.visible {
      opacity: 1;
      pointer-events: all;
    }
    .mrh-spinner {
      width: 44px;
      height: 44px;
      border: 3px solid rgba(255,255,255,0.12);
      border-top-color: #6C8FFF;
      border-radius: 50%;
      animation: mrh-spin 0.75s linear infinite;
    }
    @keyframes mrh-spin {
      to { transform: rotate(360deg); }
    }

    /* ── Toast Container ── */
    #mrh-toast-container {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 100000;
      display: flex;
      flex-direction: column;
      gap: 10px;
      pointer-events: none;
    }

    /* ── Single Toast ── */
    .mrh-toast {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      min-width: 280px;
      max-width: 380px;
      padding: 13px 16px;
      border-radius: 10px;
      font-family: 'Segoe UI', system-ui, sans-serif;
      font-size: 13.5px;
      font-weight: 500;
      line-height: 1.45;
      color: #fff;
      pointer-events: all;
      cursor: pointer;
      box-shadow: 0 8px 32px rgba(0,0,0,0.28), 0 1px 0 rgba(255,255,255,0.06) inset;
      border: 1px solid rgba(255,255,255,0.08);
      transform: translateX(120%);
      opacity: 0;
      transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
      will-change: transform, opacity;
    }
    .mrh-toast.show {
      transform: translateX(0);
      opacity: 1;
    }
    .mrh-toast.hide {
      transform: translateX(120%);
      opacity: 0;
      transition: transform 0.25s ease, opacity 0.25s ease;
    }

    /* Types */
    .mrh-toast.success { background: #0e6b3f; border-color: rgba(52,211,153,0.25); }
    .mrh-toast.error   { background: #7f1d1d; border-color: rgba(248,113,113,0.25); }
    .mrh-toast.warning { background: #78350f; border-color: rgba(251,191,36,0.25);  }
    .mrh-toast.info    { background: #1e3a5f; border-color: rgba(96,165,250,0.25);  }

    /* Icon */
    .mrh-toast-icon {
      font-size: 16px;
      flex-shrink: 0;
      margin-top: 1px;
    }
    /* Progress bar */
    .mrh-toast-progress {
      position: absolute;
      bottom: 0; left: 0;
      height: 2px;
      border-radius: 0 0 10px 10px;
      animation: mrh-progress linear forwards;
    }
    .mrh-toast { position: relative; overflow: hidden; }
    .mrh-toast.success .mrh-toast-progress { background: #34d399; }
    .mrh-toast.error   .mrh-toast-progress { background: #f87171; }
    .mrh-toast.warning .mrh-toast-progress { background: #fbbf24; }
    .mrh-toast.info    .mrh-toast-progress { background: #60a5fa; }
    @keyframes mrh-progress {
      from { width: 100%; }
      to   { width: 0%; }
    }
  `;

  // ─── DOM injection ────────────────────────────────────────────────────────

  let _initialized = false;

  function _init() {
    if (_initialized) return;
    _initialized = true;

    // Inject styles
    const style = document.createElement('style');
    style.id = 'mrh-ui-styles';
    style.textContent = CSS;
    document.head.appendChild(style);

    // Loader
    const loaderEl = document.createElement('div');
    loaderEl.id = 'mrh-loader';
    loaderEl.innerHTML = '<div class="mrh-spinner"></div>';
    document.body.appendChild(loaderEl);

    // Toast container
    const toastContainer = document.createElement('div');
    toastContainer.id = 'mrh-toast-container';
    document.body.appendChild(toastContainer);
  }

  // ─── Loader ───────────────────────────────────────────────────────────────

  let _loaderCount = 0; // counter so nested calls don't hide prematurely

  const loader = {
    show() {
      _init();
      _loaderCount++;
      document.getElementById('mrh-loader').classList.add('visible');
    },
    hide() {
      _init();
      _loaderCount = Math.max(0, _loaderCount - 1);
      if (_loaderCount === 0) {
        document.getElementById('mrh-loader').classList.remove('visible');
      }
    },
    // Force hide regardless of nesting count (use after errors/logout)
    forceHide() {
      _loaderCount = 0;
      const el = document.getElementById('mrh-loader');
      if (el) el.classList.remove('visible');
    }
  };

  // ─── Toast ────────────────────────────────────────────────────────────────

  const ICONS = {
    success: '✓',
    error:   '✕',
    warning: '⚠',
    info:    'ℹ',
  };

  const DURATION = {
    success: 3500,
    error:   5000,
    warning: 4500,
    info:    3500,
  };

  /**
   * Show a toast notification.
   * @param {string} message
   * @param {'success'|'error'|'warning'|'info'} type
   * @param {number} [duration] - ms before auto-dismiss (0 = sticky)
   */
  function toast(message, type = 'info', duration) {
    _init();

    const ms = duration !== undefined ? duration : DURATION[type] ?? 3500;

    const container = document.getElementById('mrh-toast-container');

    const el = document.createElement('div');
    el.className = `mrh-toast ${type}`;
    el.innerHTML = `
      <span class="mrh-toast-icon">${ICONS[type] ?? 'ℹ'}</span>
      <span>${_escapeHtml(message)}</span>
      ${ms > 0 ? `<div class="mrh-toast-progress" style="animation-duration:${ms}ms"></div>` : ''}
    `;

    // Click to dismiss
    el.addEventListener('click', () => _dismiss(el));

    container.appendChild(el);

    // Trigger enter animation
    requestAnimationFrame(() => {
      requestAnimationFrame(() => el.classList.add('show'));
    });

    // Auto dismiss
    if (ms > 0) {
      setTimeout(() => _dismiss(el), ms);
    }

    return el;
  }

  function _dismiss(el) {
    el.classList.remove('show');
    el.classList.add('hide');
    el.addEventListener('transitionend', () => el.remove(), { once: true });
  }

  function _escapeHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }

  // Convenience methods
  toast.success = (msg, dur) => toast(msg, 'success', dur);
  toast.error   = (msg, dur) => toast(msg, 'error',   dur);
  toast.warning = (msg, dur) => toast(msg, 'warning', dur);
  toast.info    = (msg, dur) => toast(msg, 'info',    dur);

  // ─── Public API ──────────────────────────────────────────────────────────

  return { loader, toast };

})();

// Global export
window.UI = UI;
