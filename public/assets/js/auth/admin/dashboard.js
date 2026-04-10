
/**
 * auth/admin/dashboard.js
 * Admin dashboard API with JWT token
 */

document.addEventListener("DOMContentLoaded", async () => {

  // ============================
  // Protect page
  // ============================
  //Guard.redirectIfNotLoggedIn("admin");
  

  // ============================
  // Elements
  // ============================
  //const statsWrap = document.getElementById("dashboardStats");

  //if (!statsWrap) return;

  try {

    /*
      ==========================================
      DASHBOARD API
      ==========================================
      auth:true => JWT token auto sent
      loader:true => show loader
      toast:false => handle manually if needed
    */
    const res = await Api.get("/app/api/admin.dashboard.php", {
      auth: true,
      loader: true,
      toast: false
    });
    
    console.log(res);
    
    /*
      ==========================================
      Example API response:
      {
        success: true,
        data: {
          total_students: 120,
          active_students: 95,
          total_halls: 4,
          revenue: 50000
        }
      }
    */

    const data = res.data || {};

    // update UI safely

  } catch (err) {

    console.error("Dashboard API Error:", err);

    if (err?.message) {
      UI.toast.error(err.message);
    }
  }

});