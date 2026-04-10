<?php

// ============================================================
// admin.fees.data.php
// ============================================================
// WHAT CHANGED:
//   OLD: FeeModel queries joined `users` table with no admin_id
//        scope — stats and payment history was global across
//        all admins on the platform.
//
//   NEW: All FeeModel methods join `students` table and filter:
//          WHERE st.admin_id = ?
//        So Admin A sees only their own revenue, overdue list,
//        and payment history. Admin B's data is invisible.
//
//   ALSO CHANGED:
//     • amount → final_amount (post-discount actual amount)
//     • shift column → shift_name from shifts JOIN
//     • fees.user_id → fees.student_id (renamed column)
//     • receipt_number field added to payment history response
//     • discount field added to payment history response
//
//   OPTIONAL FILTERS (JSON body):
//        search (student name/contact), status, hall_id, limit, offset
// ============================================================

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'adminFeeController',
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

$input = Request::json();

(new AdminFeeController())->list($input);
