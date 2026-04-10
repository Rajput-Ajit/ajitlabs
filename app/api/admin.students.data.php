<?php

// ============================================================
// admin.students.data.php
// ============================================================
// WHAT CHANGED:
//   OLD: StudentModel->getStudents() queried `users` table with
//        no admin_id filter — returned all students from all
//        reading halls on the platform.
//
//   NEW: StudentModel->getStudents($params, $adminId) filters
//        WHERE st.admin_id = ?   (st = students table)
//        Each admin sees ONLY their own registered students.
//        Students from Admin A are completely invisible to Admin B.
//
//   ALSO CHANGED:
//        slot/shift field now returns both:
//          slot       → shift code  (e.g. 'morning')
//          shift_name → human label (e.g. 'Morning')
//        Reason: shift is now a FK join, not an ENUM stored on
//        the allocation row.
//
//   OPTIONAL FILTERS (JSON body):
//        search (name/contact), hall_id, slot, status, limit, offset
// ============================================================

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'adminStudentController',
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

$input = Request::json();

(new AdminStudentController())->list($input);
