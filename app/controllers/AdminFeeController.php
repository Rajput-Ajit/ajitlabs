<?php
// ============================================================
// AdminFeeController.php
// ============================================================
// WHAT CHANGED:
//   OLD: list($data) — no user context, global fee data
//   NEW: list($data) passes $user to service for admin_id scoping
// ============================================================

class AdminFeeController
{
    private $service;

    public function __construct()
    {
        App::use('adminFeeService');
        $this->service = new AdminFeeService();
    }

    public function list($data)
    {
        // ✅ UPDATED: pass $user so service scopes fees by admin_id
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->list($data, $user)
        );
    }
}
