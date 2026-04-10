<?php
  require_once("components/Components.php");

  $sidebar = Components::sidebar();
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
      <div class="display text-base sm:text-xl text-stone-100 font-bold leading-tight">Good morning, Rahul 👋</div>
      <div class="text-xs text-stone-500 hidden sm:block">Wednesday, 18 March 2026 · Nagpur Main</div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
      <button class="relative w-9 h-9 flex items-center justify-center rounded-xl text-stone-400 hover:text-amber-400" style="background:rgba(255,255,255,0.05)">
        🔔<span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500"></span>
      </button>
      <a href="admin-seats.html" class="text-xs px-3 py-2 rounded-xl font-semibold text-stone-900 whitespace-nowrap hidden sm:block" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">+ Assign Seat</a>
    </div>
  </header>

  <main class="flex-1 p-4 sm:p-6 space-y-4 sm:space-y-5">

    <!-- KPI row – 2×2 on mobile, 4 on desktop -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">🏛</div>
          <span class="bg bg-g hidden sm:inline">+2 new</span>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-stone-100 display">3</div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Active Halls</div>
        <div class="text-xs text-stone-600 mt-0.5 hidden sm:block">Across 2 branches</div>
      </div>
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">🪑</div>
          <span class="bg bg-a hidden sm:inline">82%</span>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-stone-100 display">148<span class="text-base sm:text-lg text-stone-500">/180</span></div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Seats Occupied</div>
        <div class="pb h-1.5 mt-1.5"><div class="h-full rounded-full bg-amber-400" style="width:82%"></div></div>
      </div>
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">👥</div>
          <span class="bg bg-g hidden sm:inline">↑12</span>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-stone-100 display">243</div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Total Students</div>
        <div class="text-xs text-stone-600 mt-0.5 hidden sm:block">195 active subs</div>
      </div>
      <div class="stat p-4 sm:p-5">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
          <div class="text-2xl sm:text-3xl">💰</div>
          <span class="bg bg-g hidden sm:inline">↑₹18k</span>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-stone-100 display">₹1.24L</div>
        <div class="text-stone-400 text-xs sm:text-sm mt-0.5">Monthly Revenue</div>
        <div class="text-xs text-red-400 mt-0.5">⚠ 8 dues pending</div>
      </div>
    </div>

    <!-- Charts row – stacked on mobile -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="card p-4 sm:p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-3 sm:mb-4 flex-wrap gap-2">
          <div>
            <div class="font-semibold text-stone-200 text-sm sm:text-base">Revenue Overview</div>
            <div class="text-xs text-stone-500">Last 6 months</div>
          </div>
          <div class="flex gap-1.5">
            <button class="text-xs px-2.5 py-1 rounded-lg text-amber-400 font-medium" style="background:rgba(201,168,76,0.15)">Monthly</button>
            <button class="text-xs px-2.5 py-1 rounded-lg text-stone-500">Weekly</button>
          </div>
        </div>
        <div class="relative" style="height:160px"><canvas id="revChart"></canvas></div>
      </div>
      <div class="card p-4 sm:p-5">
        <div class="font-semibold text-stone-200 text-sm sm:text-base mb-1">Seat Status</div>
        <div class="text-xs text-stone-500 mb-3">Real-time overview</div>
        <div class="flex items-center gap-4">
          <div class="relative flex-shrink-0" style="width:110px;height:110px"><canvas id="seatChart"></canvas></div>
          <div class="space-y-2 text-xs">
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded flex-shrink-0" style="background:#ef4444"></span><span class="text-stone-400">Occupied</span><span class="text-stone-200 font-bold ml-auto pl-3">148</span></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded flex-shrink-0" style="background:#3b82f6"></span><span class="text-stone-400">Empty</span><span class="text-stone-200 font-bold ml-auto pl-3">22</span></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded flex-shrink-0" style="background:#f59e0b"></span><span class="text-stone-400">Half-day</span><span class="text-stone-200 font-bold ml-auto pl-3">10</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Halls + Activity + Dues – stacked on mobile, 3-col on xl -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

      <!-- Hall Summary -->
      <div class="card p-4 sm:p-5">
        <div class="font-semibold text-stone-200 mb-3 text-sm sm:text-base">Hall Overview</div>
        <div class="space-y-2.5">
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
        <a href="admin-halls.html" class="block mt-3 text-center text-xs text-amber-400 hover:underline">Manage All Halls →</a>
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
          <span class="bg bg-r">8 pending</span>
        </div>
        <div id="fee-alerts" class="space-y-2"></div>
        <a href="admin-fees.html" class="block mt-3 text-center text-xs text-amber-400 hover:underline">Manage Fees →</a>
      </div>
    </div>

    <!-- Quick Seat Grid -->
    <div class="card p-4 sm:p-5">
      <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <div>
          <div class="font-semibold text-stone-200 text-sm sm:text-base">Hall A – Quick Seat View</div>
          <div class="text-xs text-stone-500">Click any seat for details</div>
        </div>
        <div class="flex items-center gap-3 text-xs flex-wrap">
          <span class="flex items-center gap-1 text-stone-400"><span class="w-3 h-3 rounded dot e"></span>Empty</span>
          <span class="flex items-center gap-1 text-stone-400"><span class="w-3 h-3 rounded dot o"></span>Occupied</span>
          <span class="flex items-center gap-1 text-stone-400"><span class="w-3 h-3 rounded dot h"></span>Half-day</span>
          <a href="admin-seats.html" class="text-amber-400 hover:underline">Full View →</a>
        </div>
      </div>
      <div id="seat-grid" class="flex flex-wrap gap-1.5"></div>
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

// Revenue Chart
new Chart(document.getElementById('revChart').getContext('2d'),{
  type:'bar',
  data:{
    labels:['Oct','Nov','Dec','Jan','Feb','Mar'],
    datasets:[
      {label:'Revenue',data:[82000,95000,110000,88000,118000,124000],backgroundColor:'rgba(201,168,76,0.65)',borderColor:'#c9a84c',borderWidth:1,borderRadius:6},
      {label:'Collected',data:[75000,88000,102000,80000,110000,116000],backgroundColor:'rgba(59,130,246,0.45)',borderColor:'#3b82f6',borderWidth:1,borderRadius:6}
    ]
  },
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#94a3b8',font:{size:10},boxWidth:12}}},scales:{x:{ticks:{color:'#64748b',font:{size:10}},grid:{color:'rgba(255,255,255,0.04)'}},y:{ticks:{color:'#64748b',font:{size:10},callback:v=>'₹'+v/1000+'k'},grid:{color:'rgba(255,255,255,0.04)'}}}}
});

// Donut
new Chart(document.getElementById('seatChart').getContext('2d'),{
  type:'doughnut',
  data:{labels:['Occupied','Empty','Half-day'],datasets:[{data:[148,22,10],backgroundColor:['#ef4444','#3b82f6','#f59e0b'],borderWidth:0}]},
  options:{cutout:'70%',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}
});

// Activity
const acts=[
  {n:'Priya Deshmukh',a:'Seat A-12 assigned',t:'2 min ago',i:'🪑'},
  {n:'Rohan Joshi',a:'Fee paid ₹1,500',t:'15 min ago',i:'💰'},
  {n:'Amit Khurrana',a:'Seat B-34 released',t:'1 hr ago',i:'🔓'},
  {n:'Sneha Patil',a:'New admission',t:'2 hrs ago',i:'✅'},
  {n:'Vikram Singh',a:'Fee overdue 5 days',t:'3 hrs ago',i:'⚠️'},
];
document.getElementById('activity-list').innerHTML=acts.map(a=>`
  <div class="flex items-start gap-3 py-2" style="border-bottom:1px solid rgba(255,255,255,0.05)">
    <span class="text-xl flex-shrink-0 mt-0.5">${a.i}</span>
    <div class="flex-1 min-w-0">
      <div class="text-xs sm:text-sm font-medium text-stone-200 truncate">${a.n}</div>
      <div class="text-xs text-stone-500 truncate">${a.a}</div>
    </div>
    <span class="text-xs text-stone-600 whitespace-nowrap flex-shrink-0">${a.t}</span>
  </div>`).join('');

// Fee alerts
const dues=[
  {n:'Vikram Singh',amt:'₹1,500',d:5},
  {n:'Anita Bhende',amt:'₹2,000',d:8},
  {n:'Suresh Kamble',amt:'₹1,200',d:12},
  {n:'Pooja Wankhede',amt:'₹1,800',d:3},
];
document.getElementById('fee-alerts').innerHTML=dues.map(d=>`
  <div class="flex items-center justify-between p-2.5 rounded-xl" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.15)">
    <div class="min-w-0 flex-1 mr-2">
      <div class="text-xs font-medium text-stone-200 truncate">${d.n}</div>
      <div class="text-xs text-red-400">${d.amt} · ${d.d}d overdue</div>
    </div>
    <button class="text-xs px-2 py-1 rounded-lg text-amber-400 flex-shrink-0" style="background:rgba(201,168,76,0.1)">Remind</button>
  </div>`).join('');

// Seat grid
const seatData=Array.from({length:60},(_,i)=>{
  const r=Math.random();
  const s=r<0.78?(r<0.4?'o':'h'):'e';
  return{id:i+1,status:s,name:['Priya D.','Rohan J.','Amit K.','Sneha P.'][Math.floor(Math.random()*4)],shift:r<0.5?'Morning':'Evening'};
});
const grid=document.getElementById('seat-grid');
seatData.forEach(s=>{
  const el=document.createElement('div');
  el.className=`dot ${s.status}`;
  el.title=`Seat ${s.id}`;
  el.onclick=()=>showModal(s);
  grid.appendChild(el);
});

function showModal(s){
  const c={e:'#3b82f6',o:'#ef4444',h:'#f59e0b'}[s.status];
  const lbl={e:'Empty',o:'Occupied',h:'Half-day'}[s.status];
  document.getElementById('modal-body').innerHTML=`
    <div class="text-center mb-4">
      <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl" style="background:${c}22;border:2px solid ${c}">🪑</div>
      <div class="display text-xl font-bold text-stone-100">Seat ${s.id}</div>
      <span class="text-xs px-3 py-1 rounded-full font-semibold" style="background:${c}22;color:${c}">${lbl.toUpperCase()}</span>
    </div>
    ${s.status!=='e'?`
    <div class="space-y-2 text-sm">
      ${[['Student',s.name],['Shift',s.shift],['Valid Till','31 Mar 2026'],['Fee Status','Paid']].map(([k,v])=>`
      <div class="flex justify-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06)">
        <span class="text-stone-400">${k}</span>
        <span class="text-stone-200 font-medium">${v}</span>
      </div>`).join('')}
    </div>
    <div class="flex gap-2 mt-4">
      <button class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-stone-900" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">Edit</button>
      <button class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-red-400" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2)">Release</button>
    </div>`:
    `<div class="text-center py-3">
      <p class="text-stone-400 text-sm mb-4">This seat is currently empty</p>
      <a href="admin-seats.html"><button class="w-full py-2.5 rounded-xl text-xs font-semibold text-stone-900" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">Assign Student</button></a>
    </div>`}`;
  document.getElementById('seat-modal').classList.remove('hidden');
}
</script>
<script src="../assets/js/core/ui.js"></script>
<script src="../assets/js/core/api.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/core/helpers.js"></script>
<script src="../assets/js/admin/dashboard.js"></script>
</body>
</html>
