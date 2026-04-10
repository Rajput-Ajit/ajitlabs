<?php

// ============================================================
// AdminAuthService.php
// ============================================================
// WHAT CHANGED:
//   OLD: used AdminUserModel → users table
//   NEW: uses AdminModel → admins table
//
//   OLD: Token::create(userId, 'admin', email, null)
//   NEW: Token::create(adminId, 'admin', email, null) — same, just clarity
//
//   OLD: login checked isAdmin() via user_roles JOIN
//   NEW: login checks isAdmin() via admins table existence (no roles table)
//
//   OLD: users.password column verified
//   NEW: admins.password_hash column verified (renamed)
//
//   OLD: returned user['first_name'] + user['last_name']
//   NEW: same, column names unchanged in admins table
// ============================================================

class AdminAuthService
{
    private $adminModel;
    private $otpModel;

    public function __construct()
    {
        // ✅ UPDATED: 'adminUserModel' → 'adminModel' (new key in App map)
        App::useMany(['adminModel', 'otpModel', 'token']);
        $this->adminModel = new AdminModel();
        $this->otpModel   = new OtpModel();
    }

    // =========================================================
    // REGISTER
    // =========================================================
    // UPDATED: uses adminModel (targets admins table)
    // NO CHANGE to register flow logic
    // =========================================================
    public function register($data)
    {
        // check existing email
        if ($this->adminModel->findByEmail($data['email'])) {
            Response::error("Email already exists", 409);
        }

        // check verified email OTP
        $emailOtp = $this->otpModel->getVerifiedEmailOtp($data['email']);
        if (!$emailOtp) {
            Response::error("Email not verified", 400);
        }

        // check verified mobile OTP
        $mobileOtp = $this->otpModel->getVerifiedMobileOtp($data['mobile']);
        if (!$mobileOtp) {
            Response::error("Mobile not verified", 400);
        }

        // hash password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // ✅ UPDATED: createAdmin() now inserts into `admins` table
        $adminId = $this->adminModel->createAdmin($data, $passwordHash);

        // mark OTPs as used
        $this->otpModel->markUsed($emailOtp['id']);
        $this->otpModel->markUsed($mobileOtp['id']);

        // JWT token
        $token = Token::create($adminId, 'admin', $data['email'], null);

        return [
            'message' => 'Admin registered successfully',
            'token'   => $token,
            'user_id' => $adminId
        ];
    }

    // =========================================================
    // LOGIN
    // =========================================================
    // UPDATED: findByEmail() now queries admins table
    // UPDATED: password column is now password_hash
    // UPDATED: isAdmin() no longer does user_roles JOIN
    // =========================================================
    public function login($data)
    {
        // ✅ UPDATED: searches admins table
        $admin = $this->adminModel->findByEmail($data['email']);

        if (!$admin) {
            Response::error("User not found", 404);
        }

        // ✅ UPDATED: column is password_hash (was password)
        if (!password_verify($data['password'], $admin['password_hash'])) {
            Response::error("Invalid password", 401);
        }

        // ✅ UPDATED: checks admins table status directly (no role join)
        if (!$this->adminModel->isAdmin($admin['id'])) {
            Response::error("Access denied — account blocked or inactive", 403);
        }

        // ✅ NEW: check if admin is in read-only mode (expired plan)
        $isReadOnly = (bool)($admin['is_read_only'] ?? false);

        $token = Token::create($admin['id'], 'admin', $admin['email'], null);

        return [
            'message'     => 'Login successful',
            'token'       => $token,
            'is_read_only' => $isReadOnly, // ✅ NEW: frontend can show warning if true
            'user' => [
                'id'    => $admin['id'],
                'uuid'  => $admin['uuid'], // ✅ NEW: expose uuid for public references
                'name'  => $admin['first_name'] . ' ' . $admin['last_name'],
                'email' => $admin['email'],
            ]
        ];
    }
}
?>
