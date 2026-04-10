<?php

// ============================================================
// send-email-otp.php
// ============================================================
// WHAT CHANGED:
//   OLD: OtpModel stored plain OTP string in `otp` column
//        on the old `otps` table (type ENUM('email','mobile'))
//
//   NEW: OtpModel stores sha256 hash in `otp_hash` column
//        on new `otps` table (channel ENUM('email','sms','whatsapp'))
//        Column rename: type → channel, 'mobile' → 'sms'
//        Also added: actor_type, actor_id, purpose columns
//
//   TENANT NOTE:
//        OTP is for admin registration only at this stage.
//        actor_type = 'admin', actor_id = 0 (not yet created).
//        After registration, actor_id is updated via AdminModel.
//
//   NO CHANGE: rate limit (5/min), required fields, flow
// ============================================================

require_once '../config/bootstrap.php';

// Strict limit — 5 OTP requests per minute per IP
Middleware::run([
    ['RateLimitMiddleware', 5, 60]
]);

App::useMany(['otpController', 'userValidator']);

$input = Request::json();

if (!Request::validate(['email'], $input)) {
    Response::error('Email is required', 400);
}

UserValidator::validateEmail($input);

(new OtpController())->sendEmailOtp($input);
