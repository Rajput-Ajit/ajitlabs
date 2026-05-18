<?php
  require_once("components/Components.php");

  $sidebar = Components::sidebar("dashboard");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ReadSpace – Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  body{font-family:'DM Sans',sans-serif;background:#0e0e22;color:#e2e8f0;margin:0}
  .display{font-family:'Playfair Display',serif}
  /* ── Sidebar ── */
  #sidebar{position:fixed;top:0;left:0;height:100%;width:256px;background:#12122a;border-right:1px solid rgba(201,168,76,0.15);z-index:50;transform:translateX(-100%);transition:transform 0.3s ease;display:flex;flex-direction:column;overflow-y:auto}
  #sidebar.open{transform:translateX(0)}
  #overlay{position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:49;display:none}
  #overlay.show{display:block}
  @media(min-width:1024px){
    #sidebar{transform:translateX(0)}
    #overlay{display:none!important}
    #main{margin-left:256px}
    #menu-btn{display:none}
  }
  /* ── Cards ── */
  .card{background:#1a1a35;border:1px solid rgba(255,255,255,0.06);border-radius:16px}
  .stat{background:#1a1a35;border:1px solid rgba(255,255,255,0.06);border-radius:16px;transition:all 0.3s}
  .stat:hover{border-color:rgba(201,168,76,0.3);transform:translateY(-2px)}
  /* ── Nav ── */
  .nl{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;margin-bottom:2px;font-size:14px;font-weight:500;color:#94a3b8;transition:all 0.2s;text-decoration:none}
  .nl:hover{background:rgba(255,255,255,0.05);color:#e2e8f0}
  .nl.on{background:linear-gradient(135deg,#c9a84c,#e8b84b);color:#12122a;font-weight:700}
  /* ── Topbar ── */
  .topbar{background:#12122a;border-bottom:1px solid rgba(201,168,76,0.1);position:sticky;top:0;z-index:40}
  /* ── Badges ── */
  .bg{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600}
  .bg-g{background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.2)}
  .bg-r{background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.2)}
  .bg-a{background:rgba(201,168,76,0.15);color:#e8b84b;border:1px solid rgba(201,168,76,0.2)}
  /* ── Seat dots ── */
  .dot{width:18px;height:18px;border-radius:5px;cursor:pointer;transition:transform 0.15s;flex-shrink:0}
  .dot:hover{transform:scale(1.4)}
  .dot.e{background:#1e3a5f;border:1px solid #2563eb}
  .dot.o{background:#7f1d1d;border:1px solid #ef4444}
  .dot.h{background:#713f12;border:1px solid #f59e0b}
  /* ── Progress ── */
  .pb{background:rgba(255,255,255,0.08);border-radius:99px;overflow:hidden}
  /* ── Modal ── */
  .modal-bg{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);z-index:60;display:flex;align-items:center;justify-content:center;padding:16px}
  .modal-card{background:#1a1a35;border:1px solid rgba(201,168,76,0.3);border-radius:20px;width:100%;max-width:340px;padding:24px;position:relative;max-height:90vh;overflow-y:auto}
  /* ── Scrollbar ── */
  ::-webkit-scrollbar{width:4px;height:4px}
  ::-webkit-scrollbar-track{background:#0e0e22}
  ::-webkit-scrollbar-thumb{background:#c9a84c44;border-radius:2px}
  /* ── Field ── */
  input,select{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;border-radius:10px}
  select option{background:#1a1a35}
  input:focus,select:focus{outline:none;border-color:#c9a84c}
  input::placeholder{color:#475569}
</style>
</head>
<body>
<style>
  #initial-loader {
  position: fixed;
  inset: 0;
  z-index: 999999;
  display: flex;
  align-items: center;
  justify-content: center;

  background:
    radial-gradient(circle at 20% 20%, rgba(201,168,76,0.15), transparent 40%),
    radial-gradient(circle at 80% 80%, rgba(59,130,246,0.15), transparent 40%),
    #0e0e22;

  backdrop-filter: blur(10px);
  transition: opacity .5s ease, visibility .5s ease;
}

#initial-loader.hide {
  opacity: 0;
  visibility: hidden;
}

/* Glass card */
.loader-box {
  position: relative;
  padding: 40px 35px;
  border-radius: 24px;
  text-align: center;
  background: rgba(26, 26, 53, 0.6);
  border: 1px solid rgba(255,255,255,0.08);
  backdrop-filter: blur(12px);

  box-shadow:
    0 20px 80px rgba(0,0,0,0.5),
    inset 0 1px 0 rgba(255,255,255,0.05);
}

/* Glow animation */
.loader-glow {
  position: absolute;
  inset: -2px;
  border-radius: 24px;
  background: linear-gradient(135deg,#c9a84c,#e8b84b,#3b82f6);
  filter: blur(25px);
  opacity: 0.25;
  animation: glowMove 4s linear infinite;
  z-index: -1;
}

@keyframes glowMove {
  0% { transform: rotate(0deg) }
  100% { transform: rotate(360deg) }
}

/* Circle loader */
.loader-circle {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  border-radius: 50%;
  background: conic-gradient(#c9a84c, #e8b84b, #3b82f6, #c9a84c);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: spin 1.2s linear infinite;
}

.loader-inner {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #0e0e22;
}

/* Text */
.loader-title {
  font-size: 26px;
  font-weight: bold;
  color: #f8fafc;
  font-family: 'Playfair Display', serif;
  letter-spacing: 1px;
}

.loader-sub {
  font-size: 13px;
  color: #94a3b8;
  margin-top: 6px;
  animation: pulse 1.6s infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%,100% { opacity: 0.5 }
  50% { opacity: 1 }
}
</style>
<!-- INITIAL LOADER-->
<div id="initial-loader">
  <div class="loader-box">
    
    <div class="loader-glow"></div>

    <div class="loader-circle">
      <div class="loader-inner"></div>
    </div>

    <h2 class="loader-title">ReadSpace</h2>
    <p class="loader-sub">Preparing your workspace...</p>

  </div>
</div>


<!--Loader ENDS Here-->
<!-- Overlay -->
<div id="overlay" onclick="closeSidebar()"></div>

<!-- ═══ SIDEBAR ═══ -->
<aside id="sidebar">
  <?php
    echo $sidebar;
  ?>
</aside>

<!-- ═══ MAIN ═══ -->
<div id="main" class="flex flex-col min-h-screen">

  <!-- Topbar -->
  <header class="topbar px-4 sm:px-6 py-3 sm:py-4 flex items-center gap-3">
    <button id="menu-btn" onclick="openSidebar()" class="w-9 h-9 flex items-center justify-center rounded-xl text-amber-400 hover:bg-white/5 flex-shrink-0" style="display:flex">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
    </button>
    <div class="flex-1 min-w-0">
      <!--<div class="display text-base sm:text-xl text-stone-100 font-bold leading-tight">Good morning, Rahul 👋</div> -->
      <div class="text-xs text-stone-500 hidden sm:block"><?php echo date("l, d F Y");?></div>
    </div>
  </header>

  <main class="flex-1 p-4 sm:p-6 space-y-4 sm:space-y-5">

    <!-- KPI row – 2×2 on mobile, 4 on desktop -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">🏛</div>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-stone-100 display" data-stat="total_halls">0</div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Active Halls</div>
      </div>
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">🪑</div>
          <span class="bg bg-a hidden sm:inline" data-stat="occupancy_pct">0%</span>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-stone-100 display" data-stat="occupied_seats">0</div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Seats Occupied</div>
      </div>
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">👥</div>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-stone-100 display" data-stat="total_seats">0</div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Total Seats</div>
      </div>
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">💰</div>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-stone-100 display" data-stat="monthly_revenue">0</div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Monthly Revenue</div>
      </div>
    </div>

    <!-- Halls + Activity + Dues – stacked on mobile, 3-col on xl -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

      <!-- Hall Summary -->
      <div class="card p-4 sm:p-5">
        <div class="font-semibold text-stone-200 mb-3 text-sm sm:text-base">Hall Overview</div>
        <div class="space-y-2.5" id="hallOverview">
          <div class="p-3 rounded-xl" style="background:rgba(255,255,255,0.04)">
            <div class="flex justify-between items-center mb-1.5">
              <div>
                <div class="text-xs sm:text-sm font-semibold text-stone-200">Hall A – Morning</div>
                <div class="text-xs text-stone-500">6AM–12PM · 60 seats</div>
              </div>
              <span class="bg bg-g">Open</span>
            </div>
            <div class="pb h-1.5"><div class="h-full rounded-full bg-green-500" style="width:78%"></div></div>
            <div class="text-xs text-stone-500 mt-1">47/60 seats</div>
          </div>
          <div class="p-3 rounded-xl" style="background:rgba(255,255,255,0.04)">
            <div class="flex justify-between items-center mb-1.5">
              <div>
                <div class="text-xs sm:text-sm font-semibold text-stone-200">Hall A – Evening</div>
                <div class="text-xs text-stone-500">2PM–10PM · 60 seats</div>
              </div>
              <span class="bg bg-a">Active</span>
            </div>
            <div class="pb h-1.5"><div class="h-full rounded-full bg-amber-400" style="width:92%"></div></div>
            <div class="text-xs text-stone-500 mt-1">55/60 seats</div>
          </div>
          <div class="p-3 rounded-xl" style="background:rgba(255,255,255,0.04)">
            <div class="flex justify-between items-center mb-1.5">
              <div>
                <div class="text-xs sm:text-sm font-semibold text-stone-200">Hall B – Full Day</div>
                <div class="text-xs text-stone-500">6AM–10PM · 60 seats</div>
              </div>
              <span class="bg bg-g">Open</span>
            </div>
            <div class="pb h-1.5"><div class="h-full rounded-full bg-blue-500" style="width:77%"></div></div>
            <div class="text-xs text-stone-500 mt-1">46/60 seats</div>
          </div>
        </div>
        <a href="admin-halls.php" class="block mt-3 text-center text-xs text-amber-400 hover:underline">Manage All Halls →</a>
      </div>

      <!-- Activity -->
      <div class="card p-4 sm:p-5">
        <div class="flex justify-between items-center mb-3">
          <div class="font-semibold text-stone-200 text-sm sm:text-base">Recent Activity</div>
          <a href="admin-students.html" class="text-xs text-amber-400 hover:underline">View all</a>
        </div>
        <div id="activity-list" class="space-y-2.5"></div>
      </div>

      <!-- Fee Alerts -->
      <div class="card p-4 sm:p-5">
        <div class="flex justify-between items-center mb-3">
          <div class="font-semibold text-stone-200 text-sm sm:text-base">Fee Alerts</div>
          <span class="bg bg-r" id="pendingCount">0 pending</span>
        </div>
        <div id="fee-alerts" class="space-y-2"></div>
        <a href="admin-fees.html" class="block mt-3 text-center text-xs text-amber-400 hover:underline">Manage Fees →</a>
      </div>
    </div>
  </main>
</div>

<!-- Seat Modal -->
<div id="seat-modal" class="modal-bg hidden">
  <div class="modal-card">
    <button onclick="document.getElementById('seat-modal').classList.add('hidden')" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-stone-400 hover:text-stone-200 rounded-lg hover:bg-white/10 text-xl">✕</button>
    <div id="modal-body"></div>
  </div>
</div>

<script>
// Sidebar
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('overlay').classList.add('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show')}
// Hide menu btn on desktop
function checkMenuBtn(){document.getElementById('menu-btn').style.display=window.innerWidth>=1024?'none':'flex'}
checkMenuBtn();window.addEventListener('resize',checkMenuBtn);


</script>
<script src="../assets/js/core/ui.js"></script>
<script src="../assets/js/core/api.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/core/helpers.js"></script>
<script src="../assets/js/admin/dashboard.js"></script>
</body>
</html>
