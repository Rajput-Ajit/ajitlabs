<?php

// ============================================================
// admin.dashboard.php
// ============================================================
// WHAT CHANGED:
//   OLD: AdminDashboardController->data() queried all data globally
//   NEW: AdminDashboardController->data() reads $_REQUEST['user']
//        (set by AuthMiddleware) and passes admin_id to all queries
//        so dashboard shows ONLY this admin's halls, seats, revenue.
//
// No change to this file — controller handles the $user passing.
// ============================================================

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'adminDashboardController',
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

(new AdminDashboardController())->data();
