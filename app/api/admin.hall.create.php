<?php

// ============================================================
// admin.hall.create.php
// ============================================================
// WHAT CHANGED:
//   OLD: service created hall with shift fees stored as columns
//        directly on the halls table row.
//   NEW: service creates hall row (core data only), then creates
//        shift rows in the `shifts` table (one per shift type).
//        Reason: shifts are now normalised — each hall can have
//        any number of custom shifts with independent fees/times.
//
//   TENANT ISOLATION:
//        AuthMiddleware attaches admin_id from JWT to $_REQUEST['user'].
//        AdminHallService->create() uses admin_id to verify the
//        branch_id sent in the request actually belongs to THIS admin
//        before creating the hall under it.
//        A rogue admin cannot create a hall under another admin's branch.
//
//   REQUIRED FIELDS: same as before
//        hall_name, branch_id, total_seats, prefix, count_start
//        + at least one shift group (morning/evening/fullday start+end+fees)
// ============================================================

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'hallValidator',
    'adminHallController'
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

$input = Request::json();

// Required core fields
Request::InputRequirements([
    'hall_name',
    'branch_id',
    'total_seats',
    'count_start',
    'prefix',
], $input);

// Validate hall fields + at least one shift timing
HallValidator::validateCreatHall($input);

(new AdminHallController())->create($input);
