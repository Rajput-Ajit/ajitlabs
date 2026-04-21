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
  await loadStudents();
  
  // Logout
  Dom.on('#logout-btn', 'click', () => Api.logout('admin'));

});

// ─── Load seats for a hall ────────────────────────────────────────────────────

async function loadStudents(){

  try {
    
    const res = await Api.post('/app/api/admin.students.data.php', filters, {
      loader: true,
      toast:  false,
    });
    
    console.log(res);
    window._total = res.total;
    // load table
    const mapped = mapStudents(res.data ?? {});
    renderAll(mapped);

    // load pagination
    renderPagination({
      total: window._total ?? 0,
      limit: 10,
      offset: filters.offset
    });

  } catch (err) {
    
  }
}

//------------          PAGINATION      -------------------
function renderPagination({ total, limit, offset }) {
  const info = document.getElementById("tbl-info");
  const container = document.querySelector("#tbl-info + div"); // buttons container

  const totalPages = Math.ceil(total / limit);
  const currentPage = Math.floor(offset / limit) + 1;

  // ✅ Update text
  const start = offset + 1;
  const end = Math.min(offset + limit, total);
  info.innerText = `Showing ${end} of ${total} students`;

  container.innerHTML = "";

  if (totalPages <= 1) return;

  // Button helper
  function btn(page, text, active = false) {
    return `
      <button onclick="changePage(${page})"
        class="px-2.5 py-1.5 rounded-lg ${
          active 
            ? 'text-amber-400' 
            : 'hover:text-amber-400'
        }"
        style="${
          active
            ? 'background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2)'
            : 'background:rgba(255,255,255,0.05)'
        }">
        ${text}
      </button>
    `;
  }

  // ✅ Prev
  if (currentPage > 1) {
    container.innerHTML += btn(currentPage - 1, "← Prev");
  }

  // ✅ First page
  container.innerHTML += btn(1, 1, currentPage === 1);

  // range logic
  let startPage = Math.max(2, currentPage - 1);
  let endPage = Math.min(totalPages - 1, currentPage + 1);

  // dots after first
  if (startPage > 2) {
    container.innerHTML += `<span class="px-2 text-stone-400">...</span>`;
  }

  // middle pages
  for (let i = startPage; i <= endPage; i++) {
    container.innerHTML += btn(i, i, i === currentPage);
  }

  // dots before last
  if (endPage < totalPages - 1) {
    container.innerHTML += `<span class="px-2 text-stone-400">...</span>`;
  }

  // last page
  if (totalPages > 1) {
    container.innerHTML += btn(totalPages, totalPages, currentPage === totalPages);
  }

  // ✅ Next
  if (currentPage < totalPages) {
    container.innerHTML += btn(currentPage + 1, "Next →");
  }
}

// --------          PAGE   --------------
function changePage(page) {
  const offset = (page - 1) * filters.limit;
  console.log(offset);
  filters.offset = offset;
  loadStudents();
}


// map to UI friendly 
function mapStudents(apiData) {
  return apiData.map(s => {
    const name = `${s.first_name} ${s.last_name || ""}`.trim();

    return {
      id: s.student_id,
      name: name || "N/A",
      phone: s.contact,
      seat: s.seat_number || "-",
      hall: s.branch_name || "-", // you used hall before
      shift: s.shift_name || "-",
      validTill: s.end_date || "-",
      fee: s.amount || 0,
      paid: s.status === "active", // adjust if needed
      status: capitalize(s.status),
      color: "#c9a84c", // you can randomize later
      initials: getInitials(name)
    };
  });
}

function getInitials(name) {
  return name
    .split(" ")
    .map(w => w[0])
    .join("")
    .toUpperCase();
}

function capitalize(str) {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : "";
}


// search input call 
function filterStudentsSearch(){
  const input = document.getElementById("search-inp").value;
        filters.offset = 0;
        filters.input = input;
  loadStudents();
}