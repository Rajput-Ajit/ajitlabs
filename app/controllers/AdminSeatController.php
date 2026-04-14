<?php
// ============================================================
// AdminSeatController.php
// ============================================================
// WHAT CHANGED:
//   OLD: list() passed no user context
//   NEW: list($data, $user) — user passed so service can scope to admin's halls
// ============================================================

class AdminSeatController
{
    private $service;

    public function __construct()
    {
        App::use('adminSeatService');
        $this->service = new AdminSeatService();
    }

    public function list($data)
    {
        // ✅ UPDATED: pass $user for tenant-scoped hall list
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->list($data, $user)
        );
    }

    public function assign($data)
    {
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->assign($data, $user)
        );
    }

    public function release($data)
    {
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->release($data, $user)
        );
    }
}
