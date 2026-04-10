<?php

// ============================================================
// verify-mobile-otp.php
// ============================================================
// WHAT CHANGED:
//   OLD: OtpModel->findValidMobileOtp() compared plain OTP:
//        WHERE otp = ? AND type = 'mobile'
//
//   NEW: OtpModel->findValidMobileOtp() compares sha256 hash:
//        WHERE otp_hash = ? AND channel = 'sms'
//        channel value renamed from 'mobile' → 'sms' in new schema.
//
//   FLOW (unchanged):
//     1. Find valid OTP record for this mobile number
//     2. Mark as is_verified = 1 (not is_used yet)
//     3. Registration API marks it is_used = 1 after success
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

if (!Request::validate(['mobile', 'otp'], $input)) {
    Response::error('Mobile number and OTP are required', 400);
}

UserValidator::validateVerifyMobile($input);

(new OtpController())->verifyMobileOtp($input);
