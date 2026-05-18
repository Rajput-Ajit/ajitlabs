/**
 * admin/dashboard.js — Admin Dashboard Page
 * ─────────────────────────────────────────────
 * Loads: KPI stats, revenue chart data, seat stats,
 *        recent activity, fee alerts
 *
 * All data is scoped to the logged-in admin's tenant automatically
 * by the server (admin_id extracted from JWT on backend).
 *
 * Expected HTML hooks (data-* attributes):
 *   [data-stat="total_halls"]     [data-stat="total_seats"]
 *   [data-stat="occupied_seats"]  [data-stat="total_students"]
 *   [data-stat="monthly_revenue"]
 *   #recent-activity              — table tbody
 *   #fee-alerts                   — table tbody
 *   #revenue-chart-data           — hidden element (pass to chart lib)
 */

document.addEventListener('DOMContentLoaded', async () => {

  // Protect page — redirect if not logged in
  Guard.requireAuth('admin');

  await loadDashboard();

  // Logout button
  Dom.on('#logout-btn', 'click', () => {
    Api.logout('admin');
  });

});

// ─── Load all dashboard data ─────────────────────────────────────────────────

async function loadDashboard() {
  try {
    const res = await Api.post('/app/api/admin.dashboard.php', {}, {
      loader: true,    // show global spinner
      toast:  false,   // no success toast on page load
    });
    console.log(res);
    _renderStats(res.stats);
    _renderActivity(res.activity);
    _renderFeeAlerts(res.fee_alerts);
    _renderChartData(res.charts);

    renderHallOverview(res.hall_overview);
    // my page animation ends here
    const loader = document.getElementById("initial-loader");

    // Add hide class after slight delay (optional for smoother feel)
    setTimeout(() => {
      loader.classList.add("hide");

      // show empty dasbaord
      //showEmptyDashboard();
      // Remove from DOM after transition ends
      setTimeout(() => {
        loader.remove();
      }, 500); // matches CSS transition time
    }, 500); // delay before fade starts

  } catch (_) {
    // Error toast already shown by Api.post()
    // Optionally show skeleton error state here
  }
}

// ─── Stat cards ──────────────────────────────────────────────────────────────

function _renderStats(stats) {
  if (!stats) return;

  // Fill any element with data-stat="key"
  const statMap = {
    total_halls:     stats.total_halls,
    total_seats:     stats.total_seats,
    occupied_seats:  stats.occupied_seats,
    total_students:  stats.total_students,
    monthly_revenue: Format.currency(stats.monthly_revenue),
  };

  Object.entries(statMap).forEach(([key, value]) => {
    Dom.els(`[data-stat="${key}"]`).forEach(el => {
      Dom.text(el, value ?? '0');
    });
  });

  // Occupancy percentage
  const pct = stats.total_seats > 0
    ? Math.round((stats.occupied_seats / stats.total_seats) * 100)
    : 0;
  Dom.els('[data-stat="occupancy_pct"]').forEach(el => {
    Dom.text(el, `${pct}%`);
  });
}

// ─── Recent activity table ────────────────────────────────────────────────────

function _renderActivity(activity) {
  const tbody = Dom.el('#activity-list');
  if (!tbody) return;

  if (!activity || activity.length === 0) {
    Dom.html(tbody, `
      <tr><td colspan="3" style="text-align:center;color:#888;padding:20px;">
        No recent activity
      </td></tr>
    `);
    return;
  }

  Dom.html(tbody, activity.map(row => `
    
    <div 
      class="flex items-start gap-3 py-3 px-2 rounded-xl hover:bg-white/5 transition-all"
      style="border-bottom:1px solid rgba(255,255,255,0.05)"
    >

      <!-- Icon -->
      <span class="text-xl flex-shrink-0 mt-0.5">
        ⏰
      </span>

      <!-- Content -->
      <div class="flex-1 min-w-0">

        <!-- Student -->
        <div class="text-xs sm:text-sm font-medium text-stone-200 truncate">
          ${Dom._escape(row.student_name || row.first_name || '—')}
        </div>

        <!-- Action -->
        <div class="text-xs text-stone-500 truncate mt-0.5">
          ${Dom._escape(row.action || 'Seat Allocated')}
        </div>

      </div>

      <!-- Time -->
      <span class="text-[11px] text-stone-600 whitespace-nowrap flex-shrink-0">
        ${Format.relativeTime(row.created_at)}
      </span>

    </div>

  `).join(''));
}

// ─── Fee alerts table ─────────────────────────────────────────────────────────

function _renderFeeAlerts(alerts) {
  const tbody = Dom.el('#fee-alerts');
  const pendingCount = Dom.el('#pendingCount');
        pendingCount.innerText = `${alerts.length}  Pending`;
  if (!tbody) return;

  if (!alerts || alerts.length === 0) {
    Dom.html(tbody, `
      <tr><td colspan="4" style="text-align:center;color:#888;padding:20px;">
        No overdue fees 🎉
      </td></tr>
    `);
    return;
  }

  Dom.html(tbody, alerts.map(row => `
                                  <div 
                                class="flex items-center gap-3 py-3 px-2 rounded-xl hover:bg-white/5 transition-all"
                                style="border-bottom:1px solid rgba(255,255,255,0.05)"
                              >

                                <!-- Avatar -->
                                <div 
                                  class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                                  style="
                                    background:linear-gradient(135deg,#ef4444,#dc2626);
                                    box-shadow:0 4px 10px rgba(239,68,68,0.25);
                                  "
                                >
                                  ${Dom._escape((row.first_name || 'U')[0])}
                                </div>

                                <!-- Student Info -->
                                <div class="flex-1 min-w-0">

                                  <div class="text-sm font-semibold text-stone-200 truncate">
                                    ${Dom._escape(row.first_name || '—')}
                                    ${Dom._escape(row.last_name || '')}
                                  </div>
                                  <span
                                    style="
                                      display:inline-block;
                                      max-width:120px;
                                      overflow:hidden;
                                      text-overflow:ellipsis;
                                      white-space:nowrap;
                                      font-size:11px;
                                      color:#94a3b8;
                                      vertical-align:middle;
                                    "
                                    title="${Dom._escape(row.hall_name || '—')}"
                                  >
                                    (${Dom._escape(row.hall_name || '—')})
                                  </span>

                                  <div class="flex items-center gap-2 mt-1 flex-wrap">

                                    <!-- Amount -->
                                    <span class="
                                      text-xs
                                      px-2.5 py-1
                                      rounded-full
                                      bg-emerald-500/10
                                      text-emerald-400
                                      border border-emerald-400/10
                                      font-medium
                                    ">
                                      ${Format.currency(row.amount)}
                                    </span>
                                  </div>

                                </div>

                                <!-- Overdue -->
                                <div 
                                  class="
                                    text-xs
                                    font-semibold
                                    whitespace-nowrap
                                    px-3 py-1.5
                                    rounded-full
                                  "
                                  style="
                                    background:rgba(239,68,68,0.12);
                                    color:#f87171;
                                    border:1px solid rgba(248,113,113,0.12);
                                  "
                                >
                                  ⚠ ${row.overdue_days} Days
                                </div>

                              </div>
  `).join(''));
}

// ─── Chart data ───────────────────────────────────────────────────────────────

function _renderChartData(charts) {
  if (!charts) return;

  // Store chart data on a hidden element so chart lib (Chart.js etc.) can pick it up
  const el = Dom.el('#revenue-chart-data');
  if (el) {
    el.dataset.revenue = JSON.stringify(charts.revenue || []);
    el.dataset.seats   = JSON.stringify(charts.seats   || {});
  }

  // Fire a custom event so chart initializer can listen
  document.dispatchEvent(new CustomEvent('mrh:chartDataReady', {
    detail: { revenue: charts.revenue, seats: charts.seats }
  }));
}

// ─── Add _escape utility to Dom ──────────────────────────────────────────────
Dom._escape = function(str) {
  if (!str) return '';
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str)));
  return d.innerHTML;
};



// ---------- empty for new user --------
function showEmptyDashboard(data = {}) {
  const userName = data.userName || "there";

  // main content area
  const main = document.querySelector("main");

  if (!main) return;

  // replace dashboard content
  main.innerHTML = `
    <div class="min-h-[70vh] flex items-center justify-center">
      
      <div class="card w-full max-w-3xl p-6 sm:p-10 text-center">

        <!-- icon -->
        <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-5 rounded-3xl flex items-center justify-center text-4xl"
             style="background:rgba(201,168,76,0.12);border:1px solid rgba(201,168,76,0.15)">
          🚀
        </div>

        <!-- heading -->
        <h2 class="display text-2xl sm:text-4xl font-bold text-stone-100 mb-3">
          Welcome, ${userName} 👋
        </h2>

        <!-- sub text -->
        <p class="text-stone-400 text-sm sm:text-base max-w-xl mx-auto leading-relaxed">
          Your ReadSpace dashboard is ready. 
          Start by adding your first hall, setting up seats, and inviting students.
          Once data comes in, you’ll see reports, seat status, revenue, and alerts here.
        </p>

        <!-- steps -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-8 text-left">

          <div class="p-4 rounded-2xl"
               style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05)">
            <div class="text-2xl mb-2">🏛</div>
            <div class="text-stone-100 font-semibold text-sm">Create Hall</div>
            <div class="text-stone-500 text-xs mt-1">
              Add your first reading hall branch.
            </div>
          </div>

          <div class="p-4 rounded-2xl"
               style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05)">
            <div class="text-2xl mb-2">🪑</div>
            <div class="text-stone-100 font-semibold text-sm">Setup Seats</div>
            <div class="text-stone-500 text-xs mt-1">
              Add seat layout and shifts.
            </div>
          </div>

          <div class="p-4 rounded-2xl"
               style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05)">
            <div class="text-2xl mb-2">👥</div>
            <div class="text-stone-100 font-semibold text-sm">Add Students</div>
            <div class="text-stone-500 text-xs mt-1">
              Start managing admissions.
            </div>
          </div>

        </div>

        <!-- actions -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center mt-8">

          <a href="admin-halls.php"
             class="px-5 py-3 rounded-2xl text-sm font-semibold text-stone-900"
             style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">
            + Create First Hall
          </a>

          <a href="admin-guide.php"
             class="px-5 py-3 rounded-2xl text-sm font-semibold text-stone-300"
             style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08)">
            View Setup Guide
          </a>

        </div>

      </div>

    </div>
  `;
}

function renderHallOverview(halls = []) {

    const hallContainer = document.querySelector('#hallOverview');

    if (!hallContainer) return;

    if (halls.length === 0) {

        hallContainer.innerHTML = `
            <div class="text-center text-stone-500 text-sm py-6">
                No halls found
            </div>
        `;

        return;
    }

    hallContainer.innerHTML = halls.map((hall, index) => {

        const occupied = Number(hall.occupied_seats || 0);
        const total = Number(hall.total_seats || 0);

        const percentage = total > 0
            ? Math.round((occupied / total) * 100)
            : 0;

        let statusClass = 'bg-g';
        let progressClass = 'bg-green-500';
        let statusText = 'Open';

        if (percentage >= 90) {
            statusClass = 'bg-a';
            progressClass = 'bg-amber-400';
            statusText = 'Active';
        }

        const progressColors = [
            'bg-green-500',
            'bg-amber-400',
            'bg-blue-500'
        ];

        progressClass = progressColors[index % progressColors.length];

        return `
            <div class="p-3 rounded-xl" style="background:rgba(255,255,255,0.04)">
                
                <div class="flex justify-between items-center mb-1.5">
                    
                    <div>
                        <div class="text-xs sm:text-sm font-semibold text-stone-200">
                            ${hall.hall_name} – ${hall.shift_name}
                        </div>

                        <div class="text-xs text-stone-500">
                            ${hall.timing} · ${total} seats
                        </div>
                    </div>

                    <span class="bg ${statusClass}">
                        ${statusText}
                    </span>

                </div>

                <div class="pb h-1.5">
                    <div 
                        class="h-full rounded-full ${progressClass}" 
                        style="width:${percentage}%">
                    </div>
                </div>

                <div class="text-xs text-stone-500 mt-1">
                    ${occupied}/${total} seats
                </div>

            </div>
        `;

    }).join('');
}