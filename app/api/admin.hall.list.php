<?php

// ============================================================
// admin.hall.list.php
// ============================================================
// WHAT CHANGED:
//   OLD: returned halls for ALL admins — no tenant isolation.
//        Any authenticated admin could see halls from other
//        admins if they knew branch IDs.
//
//   NEW: AdminHallService->list() receives $user (from JWT via
//        AuthMiddleware). It extracts admin_id and passes it to
//        HallModel->getAll() which filters:
//          WHERE b.admin_id = ?   (b = branches table)
//        This guarantees each admin only sees their own halls.
//
//   ALSO CHANGED:
//        Response now includes `shifts` array per hall
//        (was 6 flat fee/time columns: morning_fees, morning_from, etc.)
//        Shifts are parsed from GROUP_CONCAT in PHP (MariaDB 10.4 safe).
//
//   OPTIONAL FILTERS (passed as JSON body):
//        branch_id, hall_name, branch_name, status, page, limit
// ============================================================

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'adminHallController',
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

$input = Request::json();

(new AdminHallController())->list($input);
