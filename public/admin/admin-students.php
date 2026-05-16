<?php
  require_once("components/Components.php");

  $sidebar = Components::sidebar("students");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ReadSpace – Students</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}body{font-family:'DM Sans',sans-serif;background:#0e0e22;color:#e2e8f0;margin:0}.display{font-family:'Playfair Display',serif}
  #sidebar{position:fixed;top:0;left:0;height:100%;width:256px;background:#12122a;border-right:1px solid rgba(201,168,76,0.15);z-index:50;transform:translateX(-100%);transition:transform 0.3s ease;display:flex;flex-direction:column;overflow-y:auto}
  #sidebar.open{transform:translateX(0)}
  #overlay{position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:49;display:none}
  #overlay.show{display:block}
  @media(min-width:1024px){#sidebar{transform:translateX(0)}#overlay{display:none!important}#main{margin-left:256px}#menu-btn{display:none}}
  .card{background:#1a1a35;border:1px solid rgba(255,255,255,0.06);border-radius:16px}
  .nl{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;margin-bottom:2px;font-size:14px;font-weight:500;color:#94a3b8;transition:all 0.2s;text-decoration:none}
  .nl:hover{background:rgba(255,255,255,0.05);color:#e2e8f0}.nl.on{background:linear-gradient(135deg,#c9a84c,#e8b84b);color:#12122a;font-weight:700}
  .topbar{background:#12122a;border-bottom:1px solid rgba(201,168,76,0.1);position:sticky;top:0;z-index:40}
  .bg-g{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.2)}
  .bg-r{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.2)}
  .bg-a{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;background:rgba(201,168,76,0.15);color:#e8b84b;border:1px solid rgba(201,168,76,0.2)}
  .bg-b{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.2)}
  input,select{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;border-radius:10px}
  input::placeholder{color:#475569}input:focus,select:focus{outline:none;border-color:#c9a84c}select option{background:#1a1a35}
  /* Table – hide on mobile, show on md */
  .desktop-table{display:none}
  @media(min-width:768px){.desktop-table{display:block}.mobile-cards{display:none}}
  .student-card{cursor:pointer;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px;background:#1a1a35;transition:all 0.2s}
  .student-card:active{opacity:0.85}
  table{border-collapse:separate;border-spacing:0}
  th{background:#12122a;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:600}
  tr:hover td{background:rgba(255,255,255,0.02)}td{border-bottom:1px solid rgba(255,255,255,0.04)}
  .modal-bg{position:fixed;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(4px);z-index:60;display:flex;align-items:flex-end;justify-content:center}
  @media(min-width:640px){.modal-bg{align-items:center;padding:16px}}
  .modal-card{background:#1a1a35;border:1px solid rgba(201,168,76,0.3);border-radius:20px 20px 0 0;width:100%;max-width:480px;padding:20px;position:relative;max-height:92vh;overflow-y:auto}
  @media(min-width:640px){.modal-card{border-radius:20px;padding:24px}}
  ::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:#0e0e22}::-webkit-scrollbar-thumb{background:#c9a84c44;border-radius:2px}
  .av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0}
</style>
</head>
<body>
<div id="overlay" onclick="closeSidebar()"></div>
<aside id="sidebar">
  <?php
    echo $sidebar;
  ?>
</aside>

<div id="main" class="flex flex-col min-h-screen">
  <header class="topbar px-4 sm:px-6 py-3 sm:py-4 flex items-center gap-3">
    <button id="menu-btn" onclick="openSidebar()" class="w-9 h-9 flex items-center justify-center rounded-xl text-amber-400 flex-shrink-0">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
    </button>
    <div class="flex-1 min-w-0">
      <div class="display text-base sm:text-xl text-stone-100 font-bold">Students</div>
      <div class="text-xs text-stone-500 hidden sm:block">Manage student records & subscriptions</div>
    </div>
    <button onclick="document.getElementById('add-modal').classList.remove('hidden');document.body.style.overflow='hidden'" class="text-xs px-3 sm:px-4 py-2 rounded-xl font-semibold text-stone-900 whitespace-nowrap" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">+ Add</button>
  </header>

  <main class="flex-1 p-4 sm:p-6 space-y-4">
    <!-- Stats – 2×2 mobile, 4 on lg -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="card p-4 flex items-center gap-3"><div class="text-2xl">👥</div><div><div class="text-xl font-bold text-stone-100" id="stat_total">243</div><div class="text-xs text-stone-500">Total</div></div></div>
      <div class="card p-4 flex items-center gap-3"><div class="text-2xl">✅</div><div><div class="text-xl font-bold text-green-400" id="stat_active">195</div><div class="text-xs text-stone-500">Active</div></div></div>
      <div class="card p-4 flex items-center gap-3"><div class="text-2xl">⚠️</div><div><div class="text-xl font-bold text-amber-400" id="stat_expiring">32</div><div class="text-xs text-stone-500">Expiring</div></div></div>
      <div class="card p-4 flex items-center gap-3"><div class="text-2xl">🆕</div><div><div class="text-xl font-bold text-blue-400" id="stat_new">16</div><div class="text-xs text-stone-500">New/week</div></div></div>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="p-3 sm:p-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3" style="border-bottom:1px solid rgba(255,255,255,0.06)">
        <input type="text" id="search-inp" placeholder="🔍  Search name, phone, seat…" class="px-3 py-2.5 text-sm flex-1 min-w-0" oninput="filterStudentsSearch()">
        <div class="flex gap-2 flex-wrap">
          <select id="f-hall" class="px-3 py-2.5 text-sm flex-1 sm:flex-none" onchange="filterStudents()">
            <option value="">All Halls</option><option>Hall A</option><option>Hall B</option><option>Hall C</option>
          </select>
          <select id="f-shift" class="px-3 py-2.5 text-sm flex-1 sm:flex-none" onchange="filterStudents()">
            <option value="">All Shifts</option><option>Morning</option><option>Evening</option><option>Full Day</option>
          </select>
          <select id="f-status" class="px-3 py-2.5 text-sm flex-1 sm:flex-none" onchange="filterStudents()">
            <option value="">All Status</option><option>Active</option><option>Expired</option><option>Due</option>
          </select>
        </div>
      </div>

      <!-- Mobile card list -->
      <div class="mobile-cards p-3 space-y-2.5" id="mobile-list"></div>

      <!-- Desktop table -->
      <div class="desktop-table overflow-x-auto">
        <table class="w-full">
          <thead><tr>
            <th class="px-4 py-3 text-left">Student</th>
            <th class="px-4 py-3 text-left">Seat</th>
            <th class="px-4 py-3 text-left">Hall & Shift</th>
            <th class="px-4 py-3 text-left">Valid Till</th>
            <th class="px-4 py-3 text-left">Fee</th>
            <th class="px-4 py-3 text-left">Status</th>
          </tr></thead>
          <tbody id="desktop-table"></tbody>
        </table>
      </div>

      <div class="p-3 sm:p-4 flex items-center justify-between text-xs text-stone-500 flex-wrap gap-2" style="border-top:1px solid rgba(255,255,255,0.06)">
        <span id="tbl-info">Showing 20 of 243 students</span>
        <div class="flex gap-1">
          <button class="px-2.5 py-1.5 rounded-lg hover:text-amber-400" style="background:rgba(255,255,255,0.05)">← Prev</button>
          <button class="px-2.5 py-1.5 rounded-lg text-amber-400" style="background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2)">1</button>
          <button class="px-2.5 py-1.5 rounded-lg hover:text-amber-400" style="background:rgba(255,255,255,0.05)">2</button>
          <button class="px-2.5 py-1.5 rounded-lg hover:text-amber-400" style="background:rgba(255,255,255,0.05)">3</button>
          <button class="px-2.5 py-1.5 rounded-lg hover:text-amber-400" style="background:rgba(255,255,255,0.05)">Next →</button>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Add Student Modal -->
<div id="add-modal" class="modal-bg hidden">
  <div class="modal-card">
    <div class="w-10 h-1 bg-stone-600 rounded-full mx-auto mb-4 sm:hidden"></div>
    <button onclick="document.getElementById('add-modal').classList.add('hidden');document.body.style.overflow=''" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-stone-400 hover:text-stone-200 rounded-lg hover:bg-white/10 text-xl">✕</button>
    <h3 class="display text-xl text-stone-100 font-bold mb-5">Add New Student</h3>
    <div class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">First Name</label><input type="text" placeholder="Priya" class="w-full px-3 py-2.5 text-sm"></div>
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Last Name</label><input type="text" placeholder="Deshmukh" class="w-full px-3 py-2.5 text-sm"></div>
      </div>
      <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Phone</label><input type="tel" placeholder="+91 98765 43210" class="w-full px-3 py-2.5 text-sm"></div>
      <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Email (Optional)</label><input type="email" placeholder="priya@email.com" class="w-full px-3 py-2.5 text-sm"></div>
      <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Address</label><input type="text" placeholder="Nagpur, Maharashtra" class="w-full px-3 py-2.5 text-sm"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Hall</label><select class="w-full px-3 py-2.5 text-sm"><option>Hall A</option><option>Hall B</option><option>Hall C</option></select></div>
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Seat No.</label><input type="text" placeholder="A-12" class="w-full px-3 py-2.5 text-sm"></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Shift</label><select class="w-full px-3 py-2.5 text-sm"><option>Morning</option><option>Evening</option><option>Full Day</option><option>Half Day</option></select></div>
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Duration</label><select class="w-full px-3 py-2.5 text-sm"><option>1 Month</option><option>3 Months</option><option>6 Months</option><option>1 Year</option></select></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Start Date</label><input type="date" class="w-full px-3 py-2.5 text-sm"></div>
        <div><label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Monthly Fee</label><input type="number" placeholder="₹1500" class="w-full px-3 py-2.5 text-sm"></div>
      </div>
      <div class="flex gap-3 pt-1">
        <button onclick="document.getElementById('add-modal').classList.add('hidden');document.body.style.overflow=''" class="flex-1 py-3 rounded-xl text-sm text-stone-400" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1)">Cancel</button>
        <button onclick="addStudent()" class="flex-1 py-3 rounded-xl text-sm font-semibold text-stone-900" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">Add Student</button>
      </div>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div id="detail-modal" class="modal-bg hidden">
  <div class="modal-card" id="detail-content"></div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('overlay').classList.add('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show')}
function checkBtn(){document.getElementById('menu-btn').style.display=window.innerWidth>=1024?'none':'flex'}
checkBtn();window.addEventListener('resize',checkBtn);

const NAMES=['Priya Deshmukh','Rohan Joshi','Amit Khurrana','Sneha Patil','Vikram Singh','Anita Bhende','Suresh Kamble','Pooja Wankhede','Ravi Kumar','Neha Sharma','Ajay Patil','Meera Gupta','Rahul Mehta','Sunita Yadav','Deepak Verma','Kavita Joshi','Sachin Tiwari','Rekha Nair','Arun Mishra','Divya Reddy'];
const HALLS=['Hall A','Hall B','Hall C'];
const SHIFTS=['Morning','Evening','Full Day','Half Day'];
const STATUSES=['Active','Active','Active','Active','Expired','Due'];
const COLORS=['#c9a84c','#e8b84b','#f97316','#3b82f6','#a78bfa','#f43f5e','#22c55e'];

const students=Array.from({length:50},(_,i)=>({
  id:i+1,name:NAMES[i%NAMES.length],
  phone:`+91 9${Math.floor(Math.random()*9e8+1e8)}`,
  seat:`${['A','B','C'][Math.floor(Math.random()*3)]}-${Math.floor(Math.random()*60+1)}`,
  hall:HALLS[Math.floor(Math.random()*3)],
  shift:SHIFTS[Math.floor(Math.random()*4)],
  validTill:['28 Feb 2026','31 Mar 2026','30 Apr 2026','31 May 2026'][Math.floor(Math.random()*4)],
  fee:[1200,1500,1800,2000,2500][Math.floor(Math.random()*5)],
  paid:Math.random()>0.18,
  status:STATUSES[Math.floor(Math.random()*STATUSES.length)],
  color:COLORS[Math.floor(Math.random()*COLORS.length)],
  initials:NAMES[i%NAMES.length].split(' ').map(w=>w[0]).join(''),
}));

function statusCls(s){return s==='Active'?'bg-g':s==='Due'?'bg-r':'bg-a'}

function renderAll(data){
  const slice=data.slice(0,20);
  document.getElementById('tbl-info').textContent=`Showing ${Math.min(20,data.length)} of ${data.length} students`;
  // Mobile cards
  document.getElementById('mobile-list').innerHTML=slice.map(s=>`
    <div class="student-card" onclick="showDetail(${s.id})">
      <div class="flex items-center gap-3">
        <div class="av" style="background:${s.color}22;color:${s.color}">${s.initials}</div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 justify-between">
            <div class="text-sm font-semibold text-stone-200 truncate">${s.name}</div>
            <span class="${statusCls(s.status)}">${s.status}</span>
          </div>
          <div class="text-xs text-stone-500 truncate">${s.phone}</div>
        </div>
      </div>
      <div class="flex items-center gap-4 mt-2 pt-2" style="border-top:1px solid rgba(255,255,255,0.06)">
        <span class="text-xs text-stone-400 whitespace-nowrap">🪑 <span class="font-mono text-amber-400">${s.seat}</span></span>
        <span class="text-xs text-stone-400">🏛 ${s.hall}</span>
        <span class="text-xs text-stone-400">⏰ ${s.shift}</span>
      </div>
    </div>`).join('');

  // Desktop table
  document.getElementById('desktop-table').innerHTML=slice.map(s=>`
    <tr class="cursor-pointer" onclick="showDetail(${s.id})">
      <td class="px-4 py-3">
        <div class="flex items-center gap-3">
          <div class="av" style="background:${s.color}22;color:${s.color};width:32px;height:32px;font-size:12px">${s.initials}</div>
          <div><div class="text-sm font-medium text-stone-200">${s.name}</div><div class="text-xs text-stone-500">${s.phone}</div></div>
        </div>
      </td>
      <td class="px-4 py-3"><span class="text-sm font-mono text-amber-400">${s.seat}</span></td>
      <td class="px-4 py-3"><div class="text-sm text-stone-300">${s.hall}</div><div class="text-xs text-stone-500">${s.shift}</div></td>
      <td class="px-4 py-3 text-sm text-stone-300">${s.validTill}</td>
      <td class="px-4 py-3"><div class="text-sm text-stone-300">₹${s.fee}/mo</div><div class="text-xs ${s.paid?'text-green-400':'text-red-400'}">${s.paid?'✓ Paid':'⚠ Due'}</div></td>
      <td class="px-4 py-3"><span class="${statusCls(s.status)}">${s.status}</span></td>
    </tr>`).join('');
}

function filterStudents(){
  const q=document.getElementById('search-inp').value.toLowerCase();
  const h=document.getElementById('f-hall').value;
  const sh=document.getElementById('f-shift').value;
  const st=document.getElementById('f-status').value;
  renderAll(students.filter(s=>(!q||s.name.toLowerCase().includes(q)||s.phone.includes(q)||s.seat.toLowerCase().includes(q))&&(!h||s.hall===h)&&(!sh||s.shift===sh)&&(!st||s.status===st)));
}

function showDetail(id){
  const s=students.find(x=>x.id===id);
  document.getElementById('detail-content').innerHTML=`
    <div class="w-10 h-1 bg-stone-600 rounded-full mx-auto mb-4 sm:hidden"></div>
    <button onclick="document.getElementById('detail-modal').classList.add('hidden');document.body.style.overflow=''" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-stone-400 hover:text-stone-200 rounded-lg hover:bg-white/10 text-xl">✕</button>
    <div class="text-center mb-5">
      <div class="w-16 h-16 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl font-bold" style="background:${s.color}22;color:${s.color}">${s.initials}</div>
      <div class="display text-xl font-bold text-stone-100">${s.name}</div>
      <span class="${statusCls(s.status)}">${s.status}</span>
    </div>
    <div class="space-y-0 text-sm">
      ${[['Phone',s.phone],['Seat',s.seat],['Hall',s.hall],['Shift',s.shift],['Valid Till',s.validTill],['Monthly Fee','₹'+s.fee],['Fee Status',s.paid?'✓ Paid':'⚠ Pending']].map(([k,v])=>`
      <div class="flex justify-between py-2.5" style="border-bottom:1px solid rgba(255,255,255,0.06)">
        <span class="text-stone-500">${k}</span><span class="text-stone-200 font-medium">${v}</span>
      </div>`).join('')}
    </div>
    <div class="flex gap-2 mt-5">
      <button class="flex-1 py-3 rounded-xl text-xs font-semibold text-stone-900" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">Edit</button>
      <button class="flex-1 py-3 rounded-xl text-xs font-semibold text-stone-400" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1)">Renew</button>
    </div>`;
  document.getElementById('detail-modal').classList.remove('hidden');
  document.body.style.overflow='hidden';
}

function addStudent(){document.getElementById('add-modal').classList.add('hidden');document.body.style.overflow='';toast('Student added!')}
function toast(msg){const t=document.createElement('div');t.className='fixed bottom-6 left-1/2 -translate-x-1/2 sm:left-auto sm:right-6 sm:translate-x-0 px-5 py-3 rounded-xl text-sm font-semibold text-stone-900 z-50 shadow-lg';t.style='background:linear-gradient(135deg,#c9a84c,#e8b84b)';t.textContent='✓ '+msg;document.body.appendChild(t);setTimeout(()=>t.remove(),2500)}

//renderAll(students);
</script>
<script src="../assets/js/core/ui.js"></script>
<script src="../assets/js/core/api.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/core/helpers.js"></script>
<script src="../assets/js/admin/students.js"></script>
</body>
</html>
