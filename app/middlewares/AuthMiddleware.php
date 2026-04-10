<?php

// ============================================================
// AuthMiddleware.php
// ============================================================
// WHAT CHANGED:
//   OLD: Token verified, user stored in $_REQUEST['user'] — no DB check
//   NEW: Same token verify flow PLUS a live DB check against the
//        `admins` table to confirm the account still exists,
//        is not blocked/deleted, and to attach is_read_only flag.
//
//   WHY: With old single `users` table there was no soft delete.
//        Now admins can be blocked or soft-deleted by super admin
//        after a token was issued. We must verify the token payload
//        against the current DB state on every request.
//
//   ALSO NEW: $_REQUEST['user']['is_read_only'] is attached so
//        any controller/service can check if the admin is in
//        read-only mode (expired subscription) without a separate
//        DB query.
// ============================================================

class AuthMiddleware
{
    public function handle()
    {
        App::useMany(['token', 'db']);

        // 1. Extract + verify JWT
        $token = Token::fromHeader();
        $user  = Token::verify($token); // exits with 401 if invalid/expired

        // 2. Live DB check — confirm admin still active
        // ✅ NEW: was not present in old AuthMiddleware
        $userId = $user->user_id ?? null;
        $type   = $user->type   ?? null;

        if ($userId && $type === 'admin') {
            $conn = DB::connect();

            $stmt = $conn->prepare("
                SELECT id, status, is_read_only, deleted_at
                FROM admins
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            if (!$admin || $admin['deleted_at'] !== null) {
                Response::error("Account not found", 401);
            }

            if ($admin['status'] === 'blocked' || $admin['status'] === 'suspended') {
                Response::error("Account is {$admin['status']}", 403);
            }

            // ✅ NEW: attach is_read_only to user payload for downstream checks
            $userArray                = (array)$user;
            $userArray['is_read_only'] = (bool)$admin['is_read_only'];
            $_REQUEST['user']          = $userArray;

        } else {
            // sub_admin or student token — store as-is
            $_REQUEST['user'] = (array)$user;
        }
    }
}
