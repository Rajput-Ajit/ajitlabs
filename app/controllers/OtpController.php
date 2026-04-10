<?php

// ============================================================
// OtpController.php
// ============================================================
// NO CHANGE from old version — thin pass-through to OtpService.
// OtpService handles all logic; controller only routes calls.
// ============================================================

class OtpController
{
    private $otpService;

    public function __construct()
    {
        App::use('otpService');
        $this->otpService = new OtpService();
    }

    public function sendEmailOtp($data)
    {
        $result = $this->otpService->sendEmailOtp($data['email']);
        Response::success(['message' => $result]);
    }

    public function verifyOtp($data)
    {
        $message = $this->otpService->verifyOtp($data['email'], $data['otp']);
        Response::success(['message' => $message]);
    }

    public function sendMobileOtp($data)
    {
        $message = $this->otpService->sendMobileOtp($data['mobile']);
        Response::success(['message' => $message]);
    }

    public function verifyMobileOtp($data)
    {
        $message = $this->otpService->verifyMobileOtp($data['mobile'], $data['otp']);
        Response::success(['message' => $message]);
    }
}
