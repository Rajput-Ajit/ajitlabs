<?php

// ============================================================
// admin.register.php
// ============================================================
// NO CHANGE in this file.
// All DB-layer changes are handled inside:
//   AdminAuthController → AdminAuthService → AdminModel
//
// Flow:
//   1. Rate limit check (10 req/min — prevent bot registrations)
//   2. Validate required fields
//   3. Run UserValidator (name, email, mobile, password rules)
//   4. AdminAuthService->register():
//      - Checks OTPs verified (email + mobile)
//      - Inserts into `admins` table (not `users`)
//      - Creates default free subscription in `admin_subscriptions`
//      - Creates first branch in `branches` with admin_id
//      - Returns JWT token
// ============================================================

require_once '../config/bootstrap.php';

Middleware::run([
    ['RateLimitMiddleware', 10, 60]
]);

App::useMany(['adminAuthController', 'userValidator']);

$input = Request::json();

if (!Request::validate(['first_name', 'last_name', 'email', 'mobile', 'password', 'reading_name', 'city'], $input)) {
    Response::error('All fields are required', 400);
}

UserValidator::validateRegister($input);

(new AdminAuthController())->register($input);
