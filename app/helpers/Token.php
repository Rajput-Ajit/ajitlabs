<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ============================================================
// Token.php
// ============================================================
// WHAT CHANGED:
//   OLD: payload had: user_id, email, type, company_id, exp
//        company_id was a leftover from a different project (unused)
//   NEW: payload has: user_id, email, type, exp
//        company_id removed — not relevant to this project
//
//   OLD: create() param was $companyId (int|null) — confusing name
//   NEW: create() param removed entirely — cleaner signature
//
//   NO CHANGE: secret, ttl, HS256, fromHeader(), verify() logic
//
//   SECURITY NOTE:
//        Secret key must be moved to .env in production.
//        Minimum 32 random characters recommended.
//        Current value "developer Ajit" is for development only.
// ============================================================

class Token
{
    /** @var string Secret signing key — move to .env in production */
    private static string $secret = "developer Ajit";

    /** @var int Token lifetime: 30 days in seconds */
    private static int $ttl = 2592000;

    // =========================================================
    // CREATE TOKEN
    // =========================================================
    // UPDATED: removed $companyId param (was unused leftover)
    // UPDATED: payload comment cleaned up
    // =========================================================
    public static function create(int $userId, string $type, ?string $email): string
    {
        $payload = [
            'user_id' => $userId,
            'email'   => $email,
            'type'    => $type,  // 'admin' | 'sub_admin' | 'student' | 'super_admin'
            'exp'     => time() + self::$ttl,
        ];

        return JWT::encode($payload, self::$secret, 'HS256');
    }

    // =========================================================
    // VERIFY TOKEN
    // =========================================================
    // NO CHANGE in logic
    // =========================================================
    public static function verify(string $token): object
    {
        try {
            return JWT::decode($token, new Key(self::$secret, 'HS256'));
        } catch (\Exception $e) {
            Response::error('Invalid or expired token', 401);
        }
    }

    // =========================================================
    // EXTRACT TOKEN FROM AUTHORIZATION HEADER
    // =========================================================
    // NO CHANGE in logic
    // =========================================================
    public static function fromHeader(): string
    {
        $headers = getallheaders();
        $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        $token   = str_replace('Bearer ', '', $auth);

        if (!$token) {
            Response::error('Authorization token missing', 401);
        }

        return $token;
    }
}
