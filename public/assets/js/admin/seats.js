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
  //Dom.on('#assign-form', 'submit', handleAssign);

  // Close modal
  //Dom.on('#modal-close-btn', 'click', closeModal);

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
    // selected hall shifts
    window.global_hall_shifts = res?.hall_shifts ?? [];
    
    seatsData = res?.data || [];
    // render hall dropdown
    _renderHallDropdown(res?.halls ?? [], res?.selected_hall_id ?? null);
    // find hall name and store
    const selectedHall = res.halls.find(h => h.id == res?.selected_hall_id);
    // store name
    window._selectedHallName = selectedHall ? selectedHall.name : null;
    

    // render seats
    renderSeats(seatsData);
    _renderStats(res.stats);
    /*
    _renderHallDropdown(res.halls, res.selected_hall_id);
    
    _renderSeatGrid(res.data, res.selected_hall_id);
    */
    // filer button selected all default
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    document.querySelector('.fb')?.classList.add('on');

    // onchange 
    document.getElementById('hallSel').addEventListener('change', (e) => {
      loadSeats(e.target.value);
    });

  } catch (err) {
    // Error shown by Api.post
    console.error(err);
  }
}

// ─── Hall dropdown ────────────────────────────────────────────────────────────

function _renderHallDropdown(halls, selectedId) {
  window._selected_hall_id = selectedId;
  
  const select = Dom.el('#hallSel');
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

    closeAssignModal();

    // rest form
    Form.reset(form);
    Form.enable(btn, 'Save Seat');
    // Reload the seat grid to reflect the new booking
    await loadSeats(body.hall_id);

  } catch (error) {
    console.log(error);
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

// ---------   AJIT      ----------------

// ------  Render Seats   ---------------
function renderSeats(seatsData = []){
  // empty grid
    grid.innerHTML = "";
    seatsData.forEach((value) =>{
      seatMap[value.id] = value;
      const seat_number = value?.seat_number ?? '';
      
      if (value.status == 'fullday') {
        // has occupants
        if(value.active_shifts.length == 1){
          grid.appendChild(fullSeat({seat: seat_number, seatId : value.id}));
        }else{
          // occupied in 2 slot
          grid.appendChild(halfSeat({seat: seat_number, seatId : value.id}));
        }
      }else if(value.status == 'morning'){
          // morning
          grid.appendChild(morningSeat({seat: seat_number, seatId : value.id}));
      }else if(value.status == 'evening'){
        // evening
        grid.appendChild(eveningSeat({seat: seat_number, seatId : value.id}));
      }else{
        // empty seat
          grid.appendChild(emptySeat({seat: seat_number, seatId : value.id}));
      }
        
    });
}

function emptySeat({seat, seatId}, opacity = 1){
  // empty
  const empty=document.createElement('div');
    empty.className=`seat empty`;
    empty.style.opacity = `${opacity}`;
    empty.dataset.id = seatId;   
    empty.innerHTML=`${Dom._escape(seat)}`;
  return empty;
}

function morningSeat({seat, seatId}, opacity = 1){
// for morning
  const morning=document.createElement('div');
    morning.className=`seat morn`;
    morning.style.opacity = `${opacity}`;
    morning.dataset.id = seatId;
    morning.innerHTML=`${Dom._escape(seat)} <span class="seatdot bg-indigo-400"></span>`;
  return morning;
}
function eveningSeat({seat, seatId}, opacity = 1){
  const evening=document.createElement('div');
    evening.className=`seat eve`;
    evening.style.opacity = `${opacity}`;
    evening.dataset.id = seatId;
    evening.innerHTML=`${Dom._escape(seat)}<span class="seatdot bg-orange-400"></span>`;
  return evening;
}

function fullSeat({seat, seatId}, opacity = 1){
  // full day
  const full=document.createElement('div');
    full.className=`seat occ`;
    full.style.opacity= `${opacity}`;
    full.dataset.id = seatId;
    full.innerHTML=`${Dom._escape(seat)} <span class="seatdot bg-green-400"></span>`;
  return full;
}

function halfSeat({seat, seatId} , opacity = 1){
  // half 
  const half=document.createElement('div');
    half.className=`seat half`;
    half.style.opacity = `${opacity}`;
    half.dataset.id = seatId;
    half.innerHTML=`${Dom._escape(seat)}<span class="seatdot bg-amber-400"></span>`;
  return half;
}


// -- -- selected seat all / empty / occupied / half day   ------------------
function renderFilteredSeats(filterType){
  grid.innerHTML = "";

  seatsData.forEach((value) => {
    const seat_number = value?.seat_number ?? '';

    const isEmpty = value.status === 'empty';
    const isFull = value.status === 'fullday';
    const isMorning = value.status === 'morning';
    const isEvening = value.status === 'evening';

    // 🎯 CHECK if seat should be ACTIVE
    let isActive = false;

    if (filterType === 'all') {
      isActive = true;
    } 
    else if (filterType === 'empty' && isEmpty) {
      isActive = true;
    } 
    else if (filterType === 'occupied' && !isEmpty) {
      isActive = true;
    } 
    else if (filterType === 'half' && (isMorning || isEvening)) {
      isActive = true;
    }

    // 🎨 CREATE SEAT NODE
    let seatEl;

    if (isEmpty) {
      seatEl = emptySeat({seat: seat_number, seatId: value.id});
    } 
    else if (isFull) {
      if (value.active_shifts.length === 2) {
        seatEl = halfSeat({seat: seat_number, seatId: value.id});
      } else {
        seatEl = fullSeat({seat: seat_number, seatId: value.id});
      }
    } 
    else if (isMorning) {
      seatEl = morningSeat({seat: seat_number, seatId: value.id});
    } 
    else if (isEvening) {
      seatEl = eveningSeat({seat: seat_number, seatId: value.id});
    }

    // 💡 APPLY DISABLED STYLE
    if (!isActive) {
      seatEl.style.opacity = "0.15";
      seatEl.style.pointerEvents = "none"; // disable click
    }

    grid.appendChild(seatEl);
  });
}

// -----------------           Release Seat       ---------------------

async function releaseSeat(allocationId, studentId) {
  
  // Convert numeric fields
  const _allocationId = parseInt(allocationId);
  const _studentId = parseInt(studentId);
  
  const btn  = Dom.el('#confirm-btn');
  btn.disabled = true;
  btn.textContent = 'Releasing...';
  try {
    const body = (_allocationId && _studentId) ? { allocation_id: _allocationId, student_id : _studentId} : {};

    await Api.post('/app/api/admin.release.seat.php', body, {
      toast:      true,
      loader: true,
      successMsg: 'Seat Release successfully!',
    });

    // Reload the seat grid to reflect the new booking
    await loadSeats(window._selected_hall_id);

  } catch (error) {
    console.log(error);
  }finally {
    btn.disabled = false;
    btn.textContent = 'Yes, Relsease';
  }
}

// ---------           JQUERY      _------------------
// ----------     Filter  ------------------------
$(document).on('click', '.fb', function () {
  const filter = $(this).data('filter');

  // 👉 active button
  $('.fb').removeClass('on');
  $(this).addClass('on');

  // 👉 call filter
  renderFilteredSeats(filter);
});


// -----------     onclick on seats     ---------------
$('#seat-grid').on('click', '.seat', function () {

  const seatId = $(this).data('id');

  const seat = seatMap[seatId]; // or find()

  if (!seat) return;

  selectSeat(seat, this);

});

// assign seat model handle by j query
$(document).on('submit', '#assign-form', function (e) {
    handleAssign(e);
});