<?php
// ============================================================
// AdminHallController.php
// ============================================================
// WHAT CHANGED:
//   OLD: service->list($data) had no user context
//   NEW: service->list($data, $user) — user passed for admin_id scope
//
//   OLD: create/update — $user was passed, ownership used hall['owner_id']
//   NEW: create/update — same, but ownership now checks hall['admin_id']
//        (handled inside service/model, controller unchanged)
// ============================================================

class AdminHallController
{
    private $service;

    public function __construct()
    {
        App::use('adminHallService');
        $this->service = new AdminHallService();
    }

    public function create($data)
    {
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->create($data, $user)
        );
    }

    public function update($data)
    {
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->update($data['hall_id'] ?? null, $data, $user)
        );
    }

    public function list($data)
    {
        // ✅ UPDATED: pass $user so service can scope halls by admin_id
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->list($data, $user)
        );
    }

    public function delete($data)
    {
        $user = $_REQUEST['user'];
        Response::success(
            $this->service->delete($data, $user)
        );
    }
}
