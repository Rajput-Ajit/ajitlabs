<?php

// ============================================================
// send-mobile-otp.php
// ============================================================
// WHAT CHANGED:
//   OLD: OtpModel stored plain OTP in `otp` column,
//        type = 'mobile' ENUM value
//
//   NEW: OtpModel stores sha256 hash in `otp_hash` column,
//        channel = 'sms' (renamed from 'mobile')
//        actor_type = 'admin', purpose = 'contact_verify'
//
//   NO CHANGE: rate limit (5/min), validation, SMS service call
// ============================================================

require_once '../config/bootstrap.php';

// Strict limit — 5 OTP requests per minute per IP
Middleware::run([
    ['RateLimitMiddleware', 5, 60]
]);

App::useMany(['otpController', 'userValidator']);

$input = Request::json();

if (!Request::validate(['mobile'], $input)) {
    Response::error('Mobile number is required', 400);
}

UserValidator::validateMobile($input);

(new OtpController())->sendMobileOtp($input);
