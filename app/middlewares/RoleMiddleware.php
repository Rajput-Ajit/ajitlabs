<?php

// ============================================================
// RoleMiddleware.php
// ============================================================
// WHAT CHANGED:
//   OLD: checked $user['type'] against expected role string
//        Role came from the JWT payload 'type' field
//   NEW: Same check, BUT also enforces read_only restriction.
//
//   NEW FEATURE — read_only guard:
//        If admin has is_read_only=1 (expired subscription) and
//        the request is a write operation (POST/PUT/DELETE),
//        it is blocked with a 403 and a clear upgrade message.
//        GET requests are always allowed even in read-only mode.
//
//   WHY: Business rule — expired admins can view data but cannot
//        add students, assign seats, collect fees, etc.
//
//   ACCEPTED ROLES: 'admin', 'sub_admin', 'student', 'super_admin'
//        (matches the `type` value set in Token::create())
// ============================================================

class RoleMiddleware
{
    private $role;

    public function __construct($role = 'admin')
    {
        $this->role = $role;
    }

    public function handle()
    {
        $user = $_REQUEST['user'] ?? null;
        $type = $user['type']    ?? null;

        if (!$user || $type !== $this->role) {
            Response::error("Access denied — insufficient role", 403);
        }

        // ✅ NEW: read-only guard for write operations
        // Admins with expired plans can only do GET requests
        if ($type === 'admin' && !empty($user['is_read_only'])) {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                Response::error(
                    "Your subscription has expired. " .
                    "You can view existing data but cannot make changes. " .
                    "Please renew your plan to continue.",
                    403
                );
            }
        }
    }
}
