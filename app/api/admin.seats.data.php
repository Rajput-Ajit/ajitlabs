<?php

// ============================================================
// admin.seats.data.php
// ============================================================
// WHAT CHANGED:
//   OLD: returned seat list with occupancy from columns on the
//        seats table: morning_occupied, evening_occupied,
//        full_day_occupied, morning_expiry, evening_expiry,
//        full_day_expiry, user_id, expiry_date.
//
//   NEW: seats table has NONE of those columns anymore.
//        Occupancy is fetched from seat_allocations table by
//        SeatModel->getSeatsByHall() which JOINs:
//          seat_allocations → students → shifts
//        Each seat row now has an `active_shifts` array showing
//        who is booked for which shift and until when.
//        AdminSeatService determines status (empty/morning/evening/
//        fullday) from that array — not from column flags.
//
//   TENANT ISOLATION:
//        AdminSeatService->list() receives $user from JWT.
//        HallModel->getAllSimple($adminId) only returns halls
//        belonging to this admin — so a hall_id from another
//        tenant returns empty, not their data.
//
//   OPTIONAL INPUT:
//        { "hall_id": 7 }  — if omitted, defaults to first hall
// ============================================================

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'adminSeatController',
    'seatValidator',
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

$input = Request::json();

// hall_id is optional — service picks first hall if absent
if (!empty($input['hall_id'])) {
    SeatValidator::hallIdValidate($input);
}

(new AdminSeatController())->list($input);
