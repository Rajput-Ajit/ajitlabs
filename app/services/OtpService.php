<?php

// ============================================================
// OtpService.php
// ============================================================
// WHAT CHANGED:
//   OLD: createEmail() / createMobile() stored plain OTP string
//   NEW: OtpModel hashes the OTP before storing (sha256)
//        OtpService still generates the plain OTP, sends it,
//        then passes it to the model which hashes it
//
//   OLD: otpModel->createEmail() took (email, otp, expires)
//   NEW: otpModel->createEmail() takes (email, otp, expires, actorType, actorId, purpose)
//        For registration flow actorType='admin', actorId=0 (not yet created)
//
//   OLD: OTP verified by comparing plain text in DB
//   NEW: OTP verified by comparing sha256 hash (inside OtpModel)
//
//   NO CHANGE: sendEmail / sendMobileOtp logic (SMS API stays same)
//   NO CHANGE: cooldown / resend logic
//   NO CHANGE: verifyOtp / verifyMobileOtp flow
// ============================================================

class OtpService
{
    private $adminModel;
    private $otpModel;

    public function __construct()
    {
        // ✅ UPDATED: 'adminUserModel' → 'adminModel'
        App::useMany(['adminModel', 'otpModel']);
        $this->adminModel = new AdminModel();
        $this->otpModel   = new OtpModel();
    }

    // =========================================================
    // SEND EMAIL OTP
    // =========================================================
    // NO LOGIC CHANGE — OtpModel now hashes before storing
    // =========================================================
    public function sendEmailOtp($email)
    {
        // check not already registered
        if ($this->adminModel->findByEmail($email)) {
            Response::error("Email already registered", 409);
        }

        // cooldown: 1 OTP per 60 seconds
        if ($this->otpModel->recentOtpExists($email)) {
            Response::error("Please wait 1 minute before requesting another OTP", 429);
        }

        // generate OTP (use rand in production, '000000' for localhost dev)
        $otp     = '000000'; // TODO: replace with rand(100000, 999999) in production
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $sent = $this->sendEmail($email, $otp);
        if (!$sent) {
            Response::error("Failed to send OTP email", 500);
        }

        // delete old OTPs for this email before inserting new
        $this->otpModel->deleteOld($email);

        // ✅ UPDATED: actorType='admin', actorId=0 (not registered yet), purpose='email_verify'
        $this->otpModel->createEmail($email, $otp, $expires, 'admin', 0, 'email_verify');

        return "OTP sent successfully";
    }

    // =========================================================
    // VERIFY EMAIL OTP
    // =========================================================
    // NO CHANGE in flow — model handles hash comparison internally
    // =========================================================
    public function verifyOtp($email, $otp)
    {
        $this->otpModel->deleteExpired();

        $record = $this->otpModel->findValidEmailOtp($email, $otp);

        if (!$record) {
            Response::error("Invalid or expired OTP", 400);
        }

        // mark as verified (not used yet — used only after successful register)
        $this->otpModel->markVerified($record['id']);

        return "OTP verified successfully";
    }

    // =========================================================
    // SEND MOBILE OTP
    // =========================================================
    // NO LOGIC CHANGE
    // =========================================================
    public function sendMobileOtp($mobile)
    {
        if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
            Response::error("Invalid mobile number", 400);
        }

        if ($this->adminModel->findByMobile($mobile)) {
            Response::error("Mobile number already registered", 409);
        }

        if ($this->otpModel->recentOtpExists($mobile)) {
            Response::error("Please wait before requesting another OTP", 429);
        }

        $this->otpModel->deleteOld($mobile);

        $otp     = '000000'; // TODO: rand(100000, 999999) in production
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // SMS sending (uncomment in production)
        // $response = $this->sendSms($mobile, $otp);
        $response = ['status' => 'success', 'message' => 'OTP sent'];

        if ($response['status'] !== 'success') {
            Response::error($response['message'], 500);
        }

        // ✅ UPDATED: actorType='admin', actorId=0, purpose='contact_verify'
        $this->otpModel->createMobile($mobile, $otp, $expires, 'admin', 0, 'contact_verify');

        return "OTP sent to mobile";
    }

    // =========================================================
    // VERIFY MOBILE OTP
    // =========================================================
    // NO CHANGE in flow
    // =========================================================
    public function verifyMobileOtp($mobile, $otp)
    {
        $this->otpModel->deleteExpired();

        $record = $this->otpModel->findValidMobileOtp($mobile, $otp);

        if (!$record) {
            Response::error("Invalid or expired OTP", 400);
        }

        $this->otpModel->markVerified($record['id']);

        return "Mobile OTP verified successfully";
    }

    // =========================================================
    // SEND EMAIL (PHPMailer wrapper — unchanged)
    // =========================================================
    private function sendEmail($email, $otp)
    {
        try {
            require_once __DIR__ . '/../../vendor/phpmailer/send_email.php';
            $response = sendOtp($otp, $email);
            if ($response['status'] === 'success') {
                return true;
            }
            Response::error($response['message'], 500);
        } catch (Exception $e) {
            Response::error("Email sending failed", 500);
        }
    }

    // =========================================================
    // SEND SMS via Fast2SMS (unchanged — commented out in dev)
    // =========================================================
    private function sendSms($mobile, $otp)
    {
        try {
            $apiKey = 'YOUR_FAST2SMS_KEY'; // move to .env

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => "https://www.fast2sms.com/dev/bulkV2",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_POSTFIELDS     => json_encode([
                    "route"     => "otp",
                    "sender_id" => "TXTIND",
                    "message"   => "Your OTP is $otp",
                    "numbers"   => $mobile
                ]),
                CURLOPT_HTTPHEADER => [
                    "authorization: $apiKey",
                    "content-type: application/json"
                ]
            ]);

            $response = curl_exec($curl);
            $err      = curl_error($curl);
            curl_close($curl);

            if ($err) return ['status' => 'error', 'message' => $err];

            $res = json_decode($response, true);

            return isset($res['return']) && $res['return'] === true
                ? ['status' => 'success', 'message' => 'Sent']
                : ['status' => 'error',   'message' => $res['message'][0] ?? 'SMS failed'];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'SMS exception'];
        }
    }
}
