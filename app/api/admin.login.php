<?php

// ============================================================
// admin.login.php
// ============================================================
// WHAT CHANGED:
//   OLD: Token::create($userId, 'admin', $email, null)
//        — 4th param $companyId was unused, now removed from Token class
//   NEW: Token::create() call is inside AdminAuthService->login()
//        — no change needed in this file, service handles it
//
//   NO CHANGE: rate limit, validation, controller routing
// ============================================================

require_once '../config/bootstrap.php';

// 🔒 Strict rate limit for login (brute force protection)
Middleware::run([
    ['RateLimitMiddleware', 10, 60]
]);

App::use('adminAuthController');

$input = Request::json();

if (!Request::validate(['email', 'password'], $input)) {
    Response::error('Email and password are required', 400);
}

(new AdminAuthController())->login($input);
