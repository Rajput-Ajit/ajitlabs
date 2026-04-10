<?php
// ============================================================
// AdminDashboardController.php
// ============================================================
// WHAT CHANGED:
//   OLD: service->data() took no args — queried all data globally
//   NEW: service->data($user) receives the JWT user payload
//        so service can extract admin_id for tenant-scoped queries
// ============================================================

class AdminDashboardController
{
    private $service;

    public function __construct()
    {
        App::use('adminDashboardService');
        $this->service = new AdminDashboardService();
    }

    public function data()
    {
        // ✅ UPDATED: pass $user so service can scope by admin_id
        $user = $_REQUEST['user'];

        Response::success(
            $this->service->data($user)
        );
    }
}
