/**
 * admin/seats.js — Seat Management Page
 * ───────────────────────────────────────
 * Features:
 *   • Load hall selector dropdown
 *   • Render seat grid with occupancy colour codes
 *   • Click seat → open assign seat modal
 *   • Submit assign seat form → POST to API
 *
 * Seat status colours:
 *   empty   → green
 *   morning → yellow
 *   evening → blue
 *   fullday → red
 *   removed → grey
 *
 * Expected HTML:
 *   <select id="hall-select">
 *   <div id="seat-stats">  (data-stat="empty|occupied|half|total")
 *   <div id="seat-grid">
 *   <dialog id="assign-modal"> / <div id="assign-modal">
 *     <form id="assign-form">
 *       <input name="seat_id" type="hidden">
 *       <input name="hall_id" type="hidden">
 *       <input name="student_name"> <input name="mobile">
 *       <select name="shift">  <select name="duration">
 *       <input name="start_date"> <input name="collected_fees">
 *       <select name="payment_method">  <input name="note">
 *       <button type="submit" id="assign-btn">Assign</button>
 *     </form>
 *   </dialog>
 */

document.addEventListener('DOMContentLoaded', async () => {

  Guard.requireAuth('admin');

  // Initial load — auto-selects first hall
  await loadSeats();

  // Hall switcher
  Dom.on('#hall-select', 'change', async () => {
    await loadSeats(Dom.el('#hall-select').value);
  });

  // Assign form submit
  Dom.on('#assign-form', 'submit', handleAssign);

  // Close modal
  Dom.on('#modal-close-btn', 'click', closeModal);

  // Logout
  Dom.on('#logout-btn', 'click', () => Api.logout('admin'));

});

// ─── Load seats for a hall ────────────────────────────────────────────────────

async function loadSeats(hallId = null) {
  try {
    const body = hallId ? { hall_id: parseInt(hallId) } : {};

    const res = await Api.post('/app/api/admin.seats.data.php', body, {
      loader: true,
      toast:  false,
    });

    _renderHallDropdown(res.halls, res.selected_hall_id);
    _renderStats(res.stats);
    _renderSeatGrid(res.data, res.selected_hall_id);

  } catch (_) {
    // Error shown by Api.post
  }
}

// ─── Hall dropdown ────────────────────────────────────────────────────────────

function _renderHallDropdown(halls, selectedId) {
  const select = Dom.el('#hall-select');
  if (!select || !halls) return;

  select.innerHTML = halls.map(h => `
    <option value="${h.id}" ${h.id == selectedId ? 'selected' : ''}>
      ${Dom._escape(h.name)}
    </option>
  `).join('');
}

// ─── Stats bar ────────────────────────────────────────────────────────────────

function _renderStats(stats) {
  if (!stats) return;
  ['empty', 'occupied', 'half', 'total'].forEach(key => {
    Dom.els(`[data-stat="${key}"]`).forEach(el => {
      Dom.text(el, stats[key] ?? 0);
    });
  });
}

// ─── Seat grid ────────────────────────────────────────────────────────────────

const STATUS_COLOR = {
  empty:   '#22c55e',  // green
  morning: '#eab308',  // yellow
  evening: '#3b82f6',  // blue
  fullday: '#ef4444',  // red
  removed: '#6b7280',  // grey
};

function _renderSeatGrid(seats, hallId) {
  const grid = Dom.el('#seat-grid');
  if (!grid) return;

  if (!seats || seats.length === 0) {
    Dom.html(grid, '<p style="color:#888;text-align:center;padding:40px">No seats found.</p>');
    return;
  }

  Dom.html(grid, seats.map(seat => {
    const color   = STATUS_COLOR[seat.status] || '#6b7280';
    const isEmpty = seat.status === 'empty';
    const label   = seat.label || seat.seat_number;

    // Tooltip: show occupant names for occupied seats
    const occupants = (seat.occupants || [])
      .map(o => `${Format.shiftLabel(o.shift_code)}: ${o.first_name} ${o.last_name || ''} (${Format.date(o.end_date)})`)
      .join('\n');

    return `
      <div class="seat-cell ${seat.status}"
           data-seat-id="${seat.id}"
           data-hall-id="${hallId}"
           data-status="${seat.status}"
           title="${occupants || label}"
           style="
             background: ${color}22;
             border: 2px solid ${color};
             border-radius: 8px;
             padding: 10px 6px;
             text-align: center;
             font-size: 12px;
             font-weight: 600;
             color: ${color};
             cursor: ${isEmpty ? 'pointer' : 'default'};
             transition: transform 0.15s, box-shadow 0.15s;
             user-select: none;
           "
           ${isEmpty ? 'role="button" tabindex="0"' : ''}
           onclick="${isEmpty ? `openAssignModal(${seat.id}, ${hallId})` : ''}">
        ${Dom._escape(label)}
      </div>
    `;
  }).join(''));
}

// ─── Assign modal ─────────────────────────────────────────────────────────────

function openAssignModal(seatId, hallId) {
  const modal  = Dom.el('#assign-modal');
  const form   = Dom.el('#assign-form');
  if (!modal || !form) return;

  // Pre-fill hidden fields
  const seatInput = form.querySelector('[name="seat_id"]');
  const hallInput = form.querySelector('[name="hall_id"]');
  if (seatInput) seatInput.value = seatId;
  if (hallInput) hallInput.value = hallId;

  // Set today as default start_date
  const dateInput = form.querySelector('[name="start_date"]');
  if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

  // Set selected hall's shifts into the shift dropdown
  _populateShiftDropdown(hallId);

  Form.clearErrors(form);
  Form.reset(form);
  // Re-set hidden fields after reset
  if (seatInput) seatInput.value = seatId;
  if (hallInput) hallInput.value = hallId;
  if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

  // Open modal (supports both <dialog> and div modal)
  if (typeof modal.showModal === 'function') {
    modal.showModal();
  } else {
    Dom.show(modal);
  }
}

function closeModal() {
  const modal = Dom.el('#assign-modal');
  if (!modal) return;
  if (typeof modal.close === 'function') {
    modal.close();
  } else {
    Dom.hide(modal);
  }
}

// Populate shift <select> from loaded hall data
function _populateShiftDropdown(hallId) {
  const shiftSelect = Dom.el('[name="shift"]');
  if (!shiftSelect) return;

  // Try to read shifts from hall dropdown data attribute
  const hallOption = Dom.el(`#hall-select option[value="${hallId}"]`);
  const shiftsJson = hallOption?.dataset?.shifts;

  if (shiftsJson) {
    try {
      const shifts = JSON.parse(shiftsJson);
      shiftSelect.innerHTML = shifts.map(s => `
        <option value="${s.code}">
          ${Format.shiftLabel(s.code)} — ${Format.currency(s.monthly_fee)}/mo
        </option>
      `).join('');
      return;
    } catch (_) {}
  }

  // Fallback: standard shifts
  shiftSelect.innerHTML = `
    <option value="morning">Morning</option>
    <option value="evening">Evening</option>
    <option value="fullday">Full Day</option>
  `;
}

// ─── Assign seat form submit ──────────────────────────────────────────────────

async function handleAssign(e) {
  e.preventDefault();
  Form.clearErrors(Dom.el('#assign-form'));

  const form = Dom.el('#assign-form');
  const btn  = Dom.el('#assign-btn');
  const body = Form.serialize(form);

  // Convert numeric fields
  body.seat_id        = parseInt(body.seat_id);
  body.hall_id        = parseInt(body.hall_id);
  body.duration       = parseInt(body.duration);
  body.collected_fees = parseFloat(body.collected_fees);

  Form.disable(btn, 'Assigning...');

  try {
    await Api.post('/app/api/admin.assign.seat.php', body, {
      toast:      true,
      successMsg: 'Seat assigned successfully!',
    });

    closeModal();

    // Reload the seat grid to reflect the new booking
    await loadSeats(body.hall_id);

  } catch (_) {
    Form.enable(btn, 'Assign Seat');
  }
}

// Attach to window so inline onclick can reach it
window.openAssignModal = openAssignModal;

// ─── Escape helper ────────────────────────────────────────────────────────────
Dom._escape = Dom._escape || function(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str || '')));
  return d.innerHTML;
};
