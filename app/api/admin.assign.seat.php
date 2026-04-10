<?php

// ============================================================
// admin.assign.seat.php
// ============================================================
// WHAT CHANGED (most impacted API in the entire codebase):
//
//   OLD FLOW:
//     1. Check seat.morning_occupied / evening_occupied columns
//     2. Find or create user in `users` table
//     3. assignRole() → insert into user_roles table
//     4. UPDATE seats SET morning_occupied=userId, morning_expiry=date
//     5. INSERT into fees (with string shift name)
//     6. INSERT into seat_allocations (secondary / "future use")
//
//   NEW FLOW:
//     1. Verify seat belongs to THIS admin (tenant check)
//     2. Lookup shift_id from `shifts` table by hall_id + shift code
//     3. Check seat_allocations for overlap (not column flags)
//     4. Find or create student in `students` table (not `users`)
//        — student gets admin_id so they belong to this tenant
//        — no role assignment needed (table = role)
//     5. INSERT into seat_allocations (THIS is now the primary record)
//     6. INSERT into fees with allocation_id FK, shift_id FK, branch_id
//        — no UPDATE to seats table at all
//
//   TENANT ISOLATION (multiple reading halls on one platform):
//     • JWT admin_id verified in AuthMiddleware against `admins` table
//     • seat_id verified: seat → hall → branch → admin_id must match JWT
//     • Students created with admin_id — belong exclusively to this tenant
//     • hall_id verified: getShiftByCode() checks hall belongs to this admin
//     • No cross-tenant data access possible at any step
//
//   REQUIRED INPUT (same field names as before):
//     seat_id, hall_id, student_name, mobile, shift (code string),
//     duration (months: 1/3/6/12), start_date, collected_fees,
//     payment_method, note
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

// Validate all required fields present
Request::InputRequirements([
    'seat_id',
    'hall_id',
    'student_name',
    'mobile',
    'shift',
    'duration',
    'start_date',
    'collected_fees',
    'payment_method',
], $input);

// Validate field formats
SeatValidator::assignSeat($input);

(new AdminSeatController())->assign($input);
