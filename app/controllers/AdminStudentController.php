<?php
// ============================================================
// AdminStudentController.php
// ============================================================
// WHAT CHANGED:
//   OLD: list($data) — no user context, all students returned globally
//   NEW: list($data) passes $user to service for admin_id scoping
// ============================================================

class AdminStudentController
{
    private $service;

    public function __construct()
    {
        App::use('adminStudentService');
        $this->service = new AdminStudentService();
    }

    public function list($data)
    {
        // ✅ UPDATED: pass $user so service scopes students by admin_id
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->list($data, $user)
        );
    }
}
