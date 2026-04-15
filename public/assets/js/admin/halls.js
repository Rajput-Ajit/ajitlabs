function mapHallData(apiData){

  return apiData.map(h => {

    // get fee by shift code
    const getFee = (code) => {
      const s = h.shifts?.find(x => x.code === code);
      return s ? '₹' + s.monthly_fee : '—';
    };

    return {
      id: h.id,
      branch_id : h.branch_id,
      name: h.name,
      branch: h.branch_name,
      seats: h.total_seat_count,
      occ: h.occupied_seats,
      shifts: (h.shifts || []).map(s => s.name),

      // convert fees
      morning: getFee('morning'),
      evening: getFee('evening'),
      fullday: getFee('fullday'),

      status: h.occupancy_percent >= 100 ? 'full' : 'open',

      // optional
      fac: [], // API not giving → keep empty
      floor: h.city // or replace with real field later
    };

  });

}

document.addEventListener('DOMContentLoaded', async () => {
  await initHall();
  loadBranchDropdown();

  // event deligation to add hall
  const form = document.getElementById("addHallForm");
  form.addEventListener("submit", addHall);

});

// INITIALLY LOAD HALL DATA
async function initHall(){
  const res = await Api.post('/app/api/admin.hall.list.php');
    console.log(res);
    halls = mapHallData(res.data);
    const branches = res.branches;
      branches.unshift("");
    
    window._branches = branches;
    console.log(window._branches);
    // now use SAME OLD FUNCTION
    renderHalls(halls);
    
    const container = document.getElementById("branchesList");
    container.innerHTML = `
      <button class="branch-btn on" onclick="setBranch(0,this)">
        All Branches
      </button>
    ` + branches.map((b, i) => {
      if (i === 0) return ""; // 🚫 skip empty first item

      return `
        <button class="branch-btn" onclick="setBranch(${i}, this)">
          ${b?.name}
        </button>
      `;
    }).join("");
}

// ─── Assign seat form submit ──────────────────────────────────────────────────

async function addHall(e) {
  e.preventDefault();
  Form.clearErrors(Dom.el('#addHallForm'));

  const form = Dom.el('#addHallForm');
  const btn  = Dom.el('#addHallFormBtn');
  const body = Form.serialize(form);

  
  Form.disable(btn, 'Adding...');

  try {
    await Api.post('/app/api/admin.hall.create.php', body, {
      toast:      true,
      successMsg: 'Hall Added successfully!',
    });

    // rest form
    Form.reset(form);
    Form.enable(btn, 'Creat Hall');
    
    // close model
    closeModal();

    // Reload the hall
    await initHall();

  } catch (error) {
    console.log(error);
    Form.enable(btn, 'Creat Hall');
  }
}

// confirm delete
async function confirmDeleteHall() {
  try {
    const body = {
                  hall_id: deleteHallId,
                  branch_id: deleteBranchId
                };
    console.log(body);
    await Api.delete('/app/api/admin.hall.delete.php', body, {
      toast:      true,
      load : true,
      successMsg: 'Hall Deleted successfully!',
    });
    
    closeDeleteModal();
    
    // reload the hall
    await initHall();

  } catch (err) {
    console.error(err);
  }
}





// --------------------------    HELPER FUCNTIONs  ------------------
  // load branches in form
  function loadBranchDropdown() {
  const select = document.getElementById("branch_id");
  const branches = window._branches || [];

  // clear old (keep first default option)
  //select.innerHTML = `<option value="">Select Branch</option>`;
  select.innerHTML = ``;

  branches.forEach(branch => {

    // ❌ skip empty item
    if (!branch || typeof branch !== "object") return;

    const option = document.createElement("option");
    option.value = branch.id;      // ✅ value = id
    option.textContent = branch.name; // ✅ show name

    select.appendChild(option);
  });
}


  /// ----------- DELETE HALL SCRIPT      -------------------
let deleteHallId = null;
let deleteBranchId = null;

// open modal
function openDeleteModal(hallId, branchId) {
  deleteHallId =    hallId;
  deleteBranchId =  branchId;
  document.getElementById("deleteHallModal").classList.remove("hidden");
}

// close modal
function closeDeleteModal() {
  deleteHallId = null;
  deleteBranchId = null;
  document.getElementById("deleteHallModal").classList.add("hidden");
}
