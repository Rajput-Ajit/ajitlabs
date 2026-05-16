//- --------------------------------   SUPPORTED  Filters  --------------
let filters = {
  search: "",
  hall_id: "",
  slot: "",
  status: "",
  limit: 10,
  offset: 0
};


document.addEventListener('DOMContentLoaded', async () => {

  Guard.requireAuth('admin');

  // Initial load — auto-selects first hall
  await loadFees();
  
  // Logout
  Dom.on('#logout-btn', 'click', () => Api.logout('admin'));

});

// ─── Load seats for a hall ────────────────────────────────────────────────────

async function loadFees(){

  try {
    
    const res = await Api.post('/app/api/admin.fees.data.php', filters, {
      loader: true,
      toast:  false,
    });
    
    const feesData = res.payments.data;

    // render fees
    printDesktopFees(feesData);

  } catch (err) {
    
  }
}

//--------------  HELPER    -----------------------------
function printDesktopFees(payData){
  document.getElementById('pay-tbl').innerHTML=payData.map(p=>`<tr>
  <td class="px-4 py-3 text-sm text-stone-200">${p.student_name}</td>
  <td class="px-4 py-3 text-sm text-stone-400">${p.hall_name} · <span class="font-mono text-amber-400">${p.seat_number}</span></td>
  <td class="px-4 py-3 text-sm text-stone-200 font-medium">₹${p.amount}</td>
  <td class="px-4 py-3 text-sm text-stone-400">${p.payment_method}</td>
  <td class="px-4 py-3 text-sm text-stone-400">${formatDate(p.created_at)}</td>
  <td class="px-4 py-3"><span class="${p.paid?'bg-g':'bg-r'}">${p.paid?'Paid':'Pending'}</span></td>
</tr>`).join('');

// after desktop run mobile
printMobileFees(payData);
}

function printMobileFees(payData){
 // Mobile payment cards
  document.getElementById('mobile-pays').innerHTML=payData.map(p=>`
    <div class="pay-card flex items-center gap-3">
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm font-medium text-stone-200 truncate">${p.student_name}</span>
          <span class="${p.paid?'bg-g':'bg-r'}">${p.paid?'Paid':'Pending'}</span>
        </div>
        <div class="flex items-center gap-3 text-xs text-stone-400">
          <span>${p.hall_name} · <span class="font-mono text-amber-400">${p.seat_number}</span></span>
          <span>₹${p.amount}</span>
          <span>${p.payment_method}</span>
          <span class="ml-auto">${formatDate(p.created_at)}</span>
        </div>
      </div>
    </div>`).join('');
}

// date format
function formatDate(dateStr) {
  const d = new Date(dateStr.replace(' ', 'T')); // fix Safari issue

  return d.toLocaleString('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}