<?php
  require_once("components/Components.php");

  $sidebar = Components::sidebar("branches");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ReadSpace – Branches</title>
<script src="https://cdn.tailwindcss.com"></script>
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
  @media(min-width:1024px){#sidebar{transform:translateX(0)}#overlay{display:none!important}#main{margin-left:256px}#menu-btn{display:none}}
  /* ── Cards ── */
  .card{background:#1a1a35;border:1px solid rgba(255,255,255,0.06);border-radius:16px}
  .hcard:hover{transform:translateY(-3px);border-color:rgba(201,168,76,0.3)!important;transition:all 0.3s}
  /* ── Nav ── */
  .nl{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;margin-bottom:2px;font-size:14px;font-weight:500;color:#94a3b8;transition:all 0.2s;text-decoration:none}
  .nl:hover{background:rgba(255,255,255,0.05);color:#e2e8f0}
  .nl.on{background:linear-gradient(135deg,#c9a84c,#e8b84b);color:#12122a;font-weight:700}
  /* ── Topbar ── */
  .topbar{background:#12122a;border-bottom:1px solid rgba(201,168,76,0.1);position:sticky;top:0;z-index:40}
  /* ── Badges ── */
  .bg-g{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.2)}
  .bg-r{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.2)}
  .bg-a{border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;background:rgba(201,168,76,0.15);color:#e8b84b;border:1px solid rgba(201,168,76,0.2)}
  /* ── Fields ── */
  input,select,textarea{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;border-radius:10px}
  input::placeholder,textarea::placeholder{color:#475569}
  input:focus,select:focus,textarea:focus{outline:none;border-color:#c9a84c}
  select option{background:#1a1a35}
  /* ── Modal ── */
  .modal-bg{position:fixed;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(4px);z-index:60;display:flex;align-items:flex-end;justify-content:center}
  @media(min-width:640px){.modal-bg{align-items:center;padding:16px}}
  .modal-card{background:#1a1a35;border:1px solid rgba(201,168,76,0.3);border-radius:20px 20px 0 0;width:100%;max-width:520px;padding:20px;position:relative;max-height:92vh;overflow-y:auto}
  @media(min-width:640px){.modal-card{border-radius:20px;padding:24px}}
  /* ── Progress ── */
  .pb{background:rgba(255,255,255,0.08);border-radius:99px;overflow:hidden}
  /* ── Scrollbar ── */
  ::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:#0e0e22}::-webkit-scrollbar-thumb{background:#c9a84c44;border-radius:2px}
</style>
</head>
<body>

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
    <button id="menu-btn" onclick="openSidebar()" class="w-9 h-9 flex items-center justify-center rounded-xl text-amber-400 hover:bg-white/5 flex-shrink-0">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
    </button>
    <div class="flex-1 min-w-0">
      <div class="display text-base sm:text-xl text-stone-100 font-bold">Branches</div>
      <div class="text-xs text-stone-500 hidden sm:block">Manage all library branches & contact info</div>
    </div>
    <button onclick="openAddModal()" class="text-xs px-3 sm:px-4 py-2 rounded-xl font-semibold text-stone-900 whitespace-nowrap" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">+ Add Branch</button>
  </header>

  <main class="flex-1 p-4 sm:p-6 space-y-4">

    <!-- Summary stats -->
    <div class="grid grid-cols-3 gap-3 sm:gap-4">
      <div class="card p-4 text-center">
        <div class="text-2xl font-bold display text-stone-100">3</div>
        <div class="text-xs text-stone-500 mt-0.5">Total Branches</div>
      </div>
      <div class="card p-4 text-center">
        <div class="text-2xl font-bold display text-green-400">3</div>
        <div class="text-xs text-stone-500 mt-0.5">Active</div>
      </div>
      <div class="card p-4 text-center">
        <div class="text-2xl font-bold display text-amber-400">290</div>
        <div class="text-xs text-stone-500 mt-0.5">Total Seats</div>
      </div>
    </div>

    <!-- Branch cards grid -->
    <div id="branches-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4"></div>

  </main>
</div>

<!-- ═══ ADD / EDIT BRANCH MODAL ═══ -->
<div id="branch-modal" class="modal-bg hidden">
  <div class="modal-card">
    <div class="w-10 h-1 bg-stone-600 rounded-full mx-auto mb-4 sm:hidden"></div>
    <button onclick="closeModal()" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-stone-400 hover:text-stone-200 rounded-lg hover:bg-white/10 text-xl">✕</button>
    <h3 id="modal-title" class="display text-xl text-stone-100 font-bold mb-5">Add New Branch</h3>
    <div class="space-y-3">
      <div>
        <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Branch Name</label>
        <input id="f-name" type="text" placeholder="e.g. Dharampeth Branch" class="w-full px-3 py-2.5 text-sm">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">City</label>
          <input id="f-city" type="text" placeholder="Nagpur" class="w-full px-3 py-2.5 text-sm">
        </div>
        <div>
          <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Status</label>
          <select id="f-status" class="w-full px-3 py-2.5 text-sm">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div>
        <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Address</label>
        <textarea id="f-address" rows="2" placeholder="Full address..." class="w-full px-3 py-2.5 text-sm resize-none"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Phone</label>
          <input id="f-phone" type="tel" placeholder="+91 98765 43210" class="w-full px-3 py-2.5 text-sm">
        </div>
        <div>
          <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Email</label>
          <input id="f-email" type="email" placeholder="branch@readspace.in" class="w-full px-3 py-2.5 text-sm">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Opening Time</label>
          <input id="f-open" type="time" value="06:00" class="w-full px-3 py-2.5 text-sm">
        </div>
        <div>
          <label class="text-xs text-stone-400 uppercase tracking-wider mb-1 block">Closing Time</label>
          <input id="f-close" type="time" value="22:00" class="w-full px-3 py-2.5 text-sm">
        </div>
      </div>
      <div>
        <label class="text-xs text-stone-400 uppercase tracking-wider mb-2 block">Facilities</label>
        <div class="flex flex-wrap gap-2">
          <label class="flex items-center gap-1.5 text-xs text-stone-400 cursor-pointer bg-white/5 px-2.5 py-1.5 rounded-lg"><input type="checkbox" checked class="accent-amber-400 w-3.5 h-3.5"> AC</label>
          <label class="flex items-center gap-1.5 text-xs text-stone-400 cursor-pointer bg-white/5 px-2.5 py-1.5 rounded-lg"><input type="checkbox" checked class="accent-amber-400 w-3.5 h-3.5"> WiFi</label>
          <label class="flex items-center gap-1.5 text-xs text-stone-400 cursor-pointer bg-white/5 px-2.5 py-1.5 rounded-lg"><input type="checkbox" checked class="accent-amber-400 w-3.5 h-3.5"> CCTV</label>
          <label class="flex items-center gap-1.5 text-xs text-stone-400 cursor-pointer bg-white/5 px-2.5 py-1.5 rounded-lg"><input type="checkbox" class="accent-amber-400 w-3.5 h-3.5"> Locker</label>
          <label class="flex items-center gap-1.5 text-xs text-stone-400 cursor-pointer bg-white/5 px-2.5 py-1.5 rounded-lg"><input type="checkbox" class="accent-amber-400 w-3.5 h-3.5"> Parking</label>
          <label class="flex items-center gap-1.5 text-xs text-stone-400 cursor-pointer bg-white/5 px-2.5 py-1.5 rounded-lg"><input type="checkbox" class="accent-amber-400 w-3.5 h-3.5"> Cafeteria</label>
        </div>
      </div>
      <div class="flex gap-3 pt-1">
        <button onclick="closeModal()" class="flex-1 py-3 rounded-xl text-sm text-stone-400" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1)">Cancel</button>
        <button onclick="saveBranch()" class="flex-1 py-3 rounded-xl text-sm font-semibold text-stone-900" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">Save Branch</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══ DELETE CONFIRM MODAL ═══ -->
<div id="del-modal" class="modal-bg hidden">
  <div class="modal-card" style="max-width:360px">
    <div class="w-10 h-1 bg-stone-600 rounded-full mx-auto mb-4 sm:hidden"></div>
    <div class="text-center py-2">
      <div class="text-4xl mb-3">🗑</div>
      <h3 class="display text-lg text-stone-100 font-bold mb-2">Delete Branch?</h3>
      <p class="text-sm text-stone-400 mb-5">This will permanently remove the branch and all related data. This action cannot be undone.</p>
      <div class="flex gap-3">
        <button onclick="document.getElementById('del-modal').classList.add('hidden');document.body.style.overflow=''" class="flex-1 py-3 rounded-xl text-sm text-stone-400" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1)">Cancel</button>
        <button onclick="confirmDelete()" class="flex-1 py-3 rounded-xl text-sm font-semibold text-white" style="background:linear-gradient(135deg,#ef4444,#dc2626)">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ── Sidebar ── */
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('overlay').classList.add('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show')}
function checkBtn(){document.getElementById('menu-btn').style.display=window.innerWidth>=1024?'none':'flex'}
checkBtn();window.addEventListener('resize',checkBtn);

/* ── Branch data ── */
let branches=[
  {id:1,name:'Nagpur Main',city:'Nagpur',address:'Plot 12, Dharampeth, Near NIT, Nagpur – 440010',phone:'+91 98765 43210',email:'main@readspace.in',open:'06:00',close:'22:00',halls:3,seats:120,occ:102,status:'active',fac:['AC','WiFi','CCTV','Locker']},
  {id:2,name:'Civil Lines',city:'Nagpur',address:'6, Civil Lines, Near High Court, Nagpur – 440001',phone:'+91 98765 43211',email:'civillines@readspace.in',open:'06:00',close:'22:00',halls:2,seats:120,occ:89,status:'active',fac:['AC','WiFi','CCTV']},
  {id:3,name:'Sitabuldi',city:'Nagpur',address:'MG Road, Sitabuldi, Nagpur – 440012',phone:'+91 98765 43212',email:'sitabuldi@readspace.in',open:'07:00',close:'21:00',halls:1,seats:50,occ:50,status:'active',fac:['WiFi','Water']},
];
let deleteTarget=null;
let editTarget=null;

/* ── Render ── */
function renderBranches(){
  const g=document.getElementById('branches-grid');
  g.innerHTML=branches.map(b=>{
    const pct=Math.round(b.occ/b.seats*100);
    const bc=pct>=95?'#ef4444':pct>=70?'#f59e0b':'#22c55e';
    return`<div class="card hcard p-4 sm:p-5" style="transition:all 0.3s">
      <div class="flex items-start justify-between mb-3">
        <div>
          <div class="flex items-center gap-2 mb-0.5">
            <span class="text-xl">📍</span>
            <div class="display text-base sm:text-lg font-bold text-stone-100">${b.name}</div>
          </div>
          <div class="text-xs text-stone-500">${b.city}</div>
        </div>
        <span class="${b.status==='active'?'bg-g':'bg-r'}">${b.status==='active'?'Active':'Inactive'}</span>
      </div>
      <div class="text-xs text-stone-500 mb-3 leading-relaxed">📫 ${b.address}</div>
      <div class="flex gap-3 mb-3">
        <div class="flex-1 text-center p-2 rounded-lg" style="background:rgba(255,255,255,0.04)">
          <div class="text-base font-bold text-stone-100">${b.halls}</div>
          <div class="text-xs text-stone-600">Halls</div>
        </div>
        <div class="flex-1 text-center p-2 rounded-lg" style="background:rgba(255,255,255,0.04)">
          <div class="text-base font-bold text-stone-100">${b.seats}</div>
          <div class="text-xs text-stone-600">Seats</div>
        </div>
        <div class="flex-1 text-center p-2 rounded-lg" style="background:rgba(255,255,255,0.04)">
          <div class="text-base font-bold" style="color:${bc}">${pct}%</div>
          <div class="text-xs text-stone-600">Occ.</div>
        </div>
      </div>
      <div class="pb h-1.5 mb-3"><div class="h-full rounded-full" style="width:${pct}%;background:${bc}"></div></div>
      <div class="text-xs text-stone-500 mb-1">📞 ${b.phone} &nbsp;·&nbsp; ⏰ ${b.open}–${b.close}</div>
      <div class="flex flex-wrap gap-1 mb-4 mt-2">
        ${b.fac.map(f=>`<span class="text-xs px-2 py-0.5 rounded-full text-stone-500" style="background:rgba(255,255,255,0.04)">✓ ${f}</span>`).join('')}
      </div>
      <div class="flex gap-2">
        <button onclick="openEditModal(${b.id})" class="flex-1 py-2 rounded-xl text-xs font-semibold text-stone-900" style="background:linear-gradient(135deg,#c9a84c,#e8b84b)">Edit</button>
        <button onclick="openDeleteModal(${b.id})" class="flex-1 py-2 rounded-xl text-xs font-semibold text-red-400" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.15)">Delete</button>
      </div>
    </div>`;
  }).join('');
}

/* ── Modal helpers ── */
function openAddModal(){
  editTarget=null;
  document.getElementById('modal-title').textContent='Add New Branch';
  ['name','city','address','phone','email'].forEach(f=>document.getElementById('f-'+f).value='');
  document.getElementById('f-open').value='06:00';
  document.getElementById('f-close').value='22:00';
  document.getElementById('f-status').value='active';
  document.getElementById('branch-modal').classList.remove('hidden');
  document.body.style.overflow='hidden';
}
function openEditModal(id){
  editTarget=id;
  const b=branches.find(x=>x.id===id);
  document.getElementById('modal-title').textContent='Edit Branch';
  document.getElementById('f-name').value=b.name;
  document.getElementById('f-city').value=b.city;
  document.getElementById('f-address').value=b.address;
  document.getElementById('f-phone').value=b.phone;
  document.getElementById('f-email').value=b.email;
  document.getElementById('f-open').value=b.open;
  document.getElementById('f-close').value=b.close;
  document.getElementById('f-status').value=b.status;
  document.getElementById('branch-modal').classList.remove('hidden');
  document.body.style.overflow='hidden';
}
function closeModal(){document.getElementById('branch-modal').classList.add('hidden');document.body.style.overflow=''}

function saveBranch(){
  const name=document.getElementById('f-name').value.trim();
  if(!name){toast('Branch name is required','err');return;}
  if(editTarget){
    const b=branches.find(x=>x.id===editTarget);
    b.name=name;b.city=document.getElementById('f-city').value;
    b.address=document.getElementById('f-address').value;
    b.phone=document.getElementById('f-phone').value;
    b.email=document.getElementById('f-email').value;
    b.open=document.getElementById('f-open').value;
    b.close=document.getElementById('f-close').value;
    b.status=document.getElementById('f-status').value;
    toast('Branch updated!');
  } else {
    branches.push({id:Date.now(),name,city:document.getElementById('f-city').value,address:document.getElementById('f-address').value,phone:document.getElementById('f-phone').value,email:document.getElementById('f-email').value,open:document.getElementById('f-open').value,close:document.getElementById('f-close').value,halls:0,seats:0,occ:0,status:document.getElementById('f-status').value,fac:['WiFi']});
    toast('Branch added!');
  }
  closeModal();renderBranches();
}

function openDeleteModal(id){deleteTarget=id;document.getElementById('del-modal').classList.remove('hidden');document.body.style.overflow='hidden'}
function confirmDelete(){branches=branches.filter(b=>b.id!==deleteTarget);deleteTarget=null;document.getElementById('del-modal').classList.add('hidden');document.body.style.overflow='';renderBranches();toast('Branch deleted')}

/* ── Toast ── */
function toast(msg,type){
  const t=document.createElement('div');
  t.className='fixed bottom-6 left-1/2 -translate-x-1/2 sm:left-auto sm:right-6 sm:translate-x-0 px-5 py-3 rounded-xl text-sm font-semibold z-50 shadow-lg';
  if(type==='err'){t.style='background:#7f1d1d;color:#fca5a5;border:1px solid rgba(239,68,68,0.3)'}
  else{t.style='background:linear-gradient(135deg,#c9a84c,#e8b84b);color:#12122a'}
  t.textContent=(type==='err'?'✕ ':'✓ ')+msg;
  document.body.appendChild(t);setTimeout(()=>t.remove(),2500);
}

renderBranches();
</script>
</body>
</html>
