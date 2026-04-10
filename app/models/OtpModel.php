<?php

// ============================================================
// OtpModel.php
// ============================================================
// WHAT CHANGED FROM OLD OtpModel.php:
//
//   OLD: columns were: type ENUM('email','mobile'), target, otp (plain text), expires_at
//   NEW: columns are:  channel ENUM('email','sms'), target, otp_hash, expires_at
//                      + actor_type ENUM('admin','sub_admin','student')
//                      + actor_id INT
//                      + purpose ENUM('login','email_verify','contact_verify','password_reset')
//
//   OLD: stored OTP as plain text string (e.g. '000000') — security risk
//   NEW: stores sha256 hash of OTP — never store raw OTP in DB
//
//   OLD: type was ENUM('email','mobile')
//   NEW: channel is ENUM('email','sms','whatsapp') — renamed + expanded
//
//   OLD: verified_at, is_verified were separate columns needing special handling
//   NEW: same columns kept, same logic
//
//   OLD: no actor context — OTPs were anonymous
//   NEW: actor_type + actor_id links OTP to the specific user who requested it
//        (needed because now we have admins, sub_admins, students in separate tables)
//
//   WHY: Security improvement + supports multi-role OTP verification
// ============================================================

class OtpModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connect();
    }

    // =========================================================
    // CREATE EMAIL OTP
    // =========================================================
    // UPDATED: stores otp_hash (sha256) instead of plain OTP
    // UPDATED: column `type` → `channel`, value 'email' stays 'email'
    // UPDATED: actor_type + actor_id for context (default: admin for registration)
    // =========================================================
    public function createEmail($email, $otp, $expires, $actorType = 'admin', $actorId = 0, $purpose = 'email_verify')
    {
        // ✅ UPDATED: hash the OTP before storing — never store plain OTP
        $otpHash = hash('sha256', $otp);

        $stmt = $this->conn->prepare("
            INSERT INTO otps (actor_type, actor_id, purpose, channel, target, otp_hash, expires_at)
            VALUES (?, ?, ?, 'email', ?, ?, ?)
        ");

        $stmt->bind_param("sissss", $actorType, $actorId, $purpose, $email, $otpHash, $expires);
        $stmt->execute();
    }

    // =========================================================
    // CREATE MOBILE OTP
    // =========================================================
    // UPDATED: stores otp_hash, column type → channel, 'mobile' → 'sms'
    // =========================================================
    public function createMobile($mobile, $otp, $expires, $actorType = 'admin', $actorId = 0, $purpose = 'contact_verify')
    {
        // ✅ UPDATED: hash the OTP
        $otpHash = hash('sha256', $otp);

        $stmt = $this->conn->prepare("
            INSERT INTO otps (actor_type, actor_id, purpose, channel, target, otp_hash, expires_at)
            VALUES (?, ?, ?, 'sms', ?, ?, ?)
        ");

        $stmt->bind_param("sissss", $actorType, $actorId, $purpose, $mobile, $otpHash, $expires);
        $stmt->execute();
    }

    // =========================================================
    // COOLDOWN CHECK
    // =========================================================
    // NO CHANGE: logic same, column `target` unchanged
    // =========================================================
    public function recentOtpExists($target)
    {
        $stmt = $this->conn->prepare("
            SELECT id FROM otps
            WHERE target = ?
            AND created_at >= NOW() - INTERVAL 60 SECOND
        ");

        $stmt->bind_param("s", $target);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    // =========================================================
    // FIND VALID EMAIL OTP
    // =========================================================
    // UPDATED: compare sha256 hash (not plain OTP)
    // UPDATED: channel = 'email' (was type = 'email' — same value, column renamed)
    // =========================================================
    public function findValidEmailOtp($email, $otp)
    {
        // ✅ UPDATED: hash the incoming OTP to compare against stored hash
        $otpHash = hash('sha256', $otp);

        $stmt = $this->conn->prepare("
            SELECT * FROM otps
            WHERE target = ?
            AND otp_hash = ?
            AND channel = 'email'
            AND is_used = 0
            AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->bind_param("ss", $email, $otpHash);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // FIND VALID MOBILE OTP
    // =========================================================
    // UPDATED: hash comparison, channel = 'sms' (was type = 'mobile')
    // =========================================================
    public function findValidMobileOtp($mobile, $otp)
    {
        // ✅ UPDATED: hash the incoming OTP
        $otpHash = hash('sha256', $otp);

        $stmt = $this->conn->prepare("
            SELECT * FROM otps
            WHERE target = ?
            AND otp_hash = ?
            AND channel = 'sms'
            AND is_used = 0
            AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->bind_param("ss", $mobile, $otpHash);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // MARK VERIFIED
    // =========================================================
    // NO CHANGE: column names same (is_verified, verified_at)
    // =========================================================
    public function markVerified($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE otps 
            SET is_verified = 1, verified_at = NOW() 
            WHERE id = ? AND is_used = 0
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    // =========================================================
    // MARK USED
    // =========================================================
    // NO CHANGE
    // =========================================================
    public function markUsed($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE otps SET is_used = 1 WHERE id = ? AND is_used = 0
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    // =========================================================
    // DELETE OLD OTPs FOR TARGET
    // =========================================================
    // NO CHANGE
    // =========================================================
    public function deleteOld($target)
    {
        $stmt = $this->conn->prepare("DELETE FROM otps WHERE target = ?");
        $stmt->bind_param("s", $target);
        $stmt->execute();
    }

    // =========================================================
    // DELETE EXPIRED OTPs
    // =========================================================
    // NO CHANGE
    // =========================================================
    public function deleteExpired()
    {
        $this->conn->query("DELETE FROM otps WHERE expires_at < NOW()");
    }

    // =========================================================
    // GET VERIFIED EMAIL OTP (for registration check)
    // =========================================================
    // UPDATED: channel = 'email' (was type check — same logic)
    // =========================================================
    public function getVerifiedEmailOtp($email)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM otps 
            WHERE target = ? 
            AND channel = 'email'
            AND is_verified = 1 
            AND is_used = 0 
            ORDER BY id DESC 
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // GET VERIFIED MOBILE OTP
    // =========================================================
    // UPDATED: channel = 'sms' (was type = 'mobile')
    // =========================================================
    public function getVerifiedMobileOtp($mobile)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM otps 
            WHERE target = ? 
            AND channel = 'sms'
            AND is_verified = 1 
            AND is_used = 0 
            ORDER BY id DESC 
            LIMIT 1
        ");

        $stmt->bind_param("s", $mobile);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}
?>
