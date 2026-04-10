<?php

// ============================================================
// verify-email-otp.php
// ============================================================
// WHAT CHANGED:
//   OLD: OtpModel->findValidEmailOtp() compared plain OTP string:
//        WHERE otp = ? AND type = 'email'
//
//   NEW: OtpModel->findValidEmailOtp() compares sha256 hash:
//        WHERE otp_hash = ? AND channel = 'email'
//        The incoming OTP is hashed in the model before comparing.
//        The raw OTP is never stored or compared in plain text.
//
//   FLOW (unchanged):
//     1. Find valid unhashed OTP record for this email
//     2. Mark record as is_verified = 1 (NOT is_used yet)
//     3. is_used = 1 only after successful registration
//        This allows the registration API to confirm verification
//        without consuming the OTP prematurely.
//
//   NO CHANGE: rate limit (10/min), required fields, validation
// ============================================================

require_once '../config/bootstrap.php';

// 10 verify attempts per minute per IP
Middleware::run([
    ['RateLimitMiddleware', 10, 60]
]);

App::useMany(['otpController', 'userValidator']);

$input = Request::json();

if (!Request::validate(['email', 'otp'], $input)) {
    Response::error('Email and OTP are required', 400);
}

UserValidator::validateVerifyEmail($input);

(new OtpController())->verifyOtp($input);
