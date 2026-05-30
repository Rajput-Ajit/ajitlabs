<?php

// ============================================================
// bootstrap.php — App Bootstrap (runs on every API request)
// ============================================================
// WHAT CHANGED:
//   OLD: loaded 'adminUserModel' (users table)
//   NEW: App map uses 'adminModel' (admins table) — updated in App.php
//
//   OLD: loaded 'roleMiddleware' from helpers/RoleMiddleware.php
//        (this was the recruiter/designation version from another project)
//   NEW: loads 'roleMiddleware' from middlewares/RoleMiddleware.php
//        (the correct one for this project — checks type + read_only)
//
//   NO CHANGE: rate limit, request/response, validator auto-load
//   NO CHANGE: default rate limit 60 req/min
// ============================================================

date_default_timezone_set('Asia/Kolkata');

require_once dirname(__DIR__) . '/core/App.php';

// Load universally-needed classes on every request
App::useMany([
    'rateLimitMiddleware',
    'runMiddlware',
    'request',
    'response',
    'validator',
]);

// 🔒 Global rate limit: 60 requests per minute per IP/user
// Specific API files override this with stricter limits (e.g. OTP: 5/min)
Middleware::run([
    ['RateLimitMiddleware', 60, 60]
]);

// Default JSON response header
header('Content-Type: application/json');

// Auto-connect DB (available as $conn everywhere after bootstrap)
App::use('db');
$conn = DB::connect();

// set cors headers for local development (adjust in production as needed)
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}