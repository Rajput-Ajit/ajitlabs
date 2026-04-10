document.addEventListener('DOMContentLoaded', () => {

  const container = Dom.el('#seats-container');
  const statsBox  = Dom.el('#seats-stats');
  const hallTitle = Dom.el('#hall-title');

  if (!container) return;

  const loadSeats = async () => {
    container.innerHTML = 'Loading seats...';

    try {
      const res = await Api.get('/app/api/get.seats.php');

      if (res.status !== 'success') {
        UI.toast.error('Failed to load seats');
        return;
      }

      // ✅ Hall name
      if (hallTitle && res.halls?.length) {
        hallTitle.innerText = res.halls[0].name;
      }

      // ✅ Stats
      if (statsBox && res.stats) {
        statsBox.innerHTML = `
          <div>Empty: ${res.stats.empty}</div>
          <div>Occupied: ${res.stats.occupied}</div>
          <div>Half: ${res.stats.half}</div>
          <div>Total: ${res.stats.total}</div>
        `;
      }

      // ✅ Seats rendering
      container.innerHTML = res.data.map(seat => {

        const isOccupied = seat.occupants && seat.occupants.length > 0;

        let occupantHtml = '';
        if (isOccupied) {
          const o = seat.occupants[0];

          occupantHtml = `
            <div class="seat-user">
              ${o.first_name} ${o.last_name}<br>
              <small>${o.shift_name}</small>
            </div>
          `;
        }

        return `
          <div class="seat ${seat.status}">
            <div class="seat-label">${seat.label}</div>
            <div class="seat-status">${seat.status}</div>
            ${occupantHtml}
          </div>
        `;
      }).join('');

    } catch (err) {
      container.innerHTML = '<p>Failed to load seats</p>';
    }
  };

  loadSeats();

});