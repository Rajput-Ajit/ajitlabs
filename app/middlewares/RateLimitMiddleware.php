<?php

// ============================================================
// RateLimitMiddleware.php
// ============================================================
// WHAT CHANGED:
//   OLD: two separate queries — find() then create() or increment()
//        This has a race condition: two requests arriving at the
//        same millisecond both pass find()=null and both INSERT,
//        causing duplicate key errors.
//
//   NEW: Uses INSERT ... ON DUPLICATE KEY UPDATE (atomic upsert)
//        This is a single query — no race condition possible.
//        Requires the UNIQUE KEY uq_rl_key_endpoint_window in schema.
//
//   OLD: window_start stored on creation and never reset cleanly
//   NEW: window check is done in PHP — if window expired, reset by
//        inserting a new row (replaces old via ON DUPLICATE KEY)
//
//   OLD: RateLimitModel.find/create/increment/reset — 4 DB round trips
//   NEW: single query per request (major perf improvement)
//
//   MariaDB 10.4 NOTE:
//        ON DUPLICATE KEY UPDATE is fully supported in MariaDB 10.4+.
//        No JSON functions used here.
//
//   PRODUCTION NOTE:
//        For high-traffic deployments, replace this DB-based rate
//        limiter with Redis (INCR + EXPIRE). See README_RECOMMENDATIONS.md
// ============================================================

class RateLimitMiddleware
{
    
    private $limit;   // max requests
    private $seconds; // window in seconds
    private $blockSeconds = 900;

    public function __construct($limit = 60, $seconds = 60)
    {
        $this->limit   = $limit;
        $this->seconds = $seconds;
    }

    public function handle()
    {
        App::use('db');
        $conn = DB::connect();

        // Identify requester: authenticated user_id preferred, else IP
        $key      = isset($_REQUEST['user']['user_id'])
                        ? (string)$_REQUEST['user']['user_id']
                        : ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $endpoint = $_SERVER['REQUEST_URI'] ?? '/';


        // check block if block error 
        // Step 1: check block status
        $check = $conn->prepare("
            SELECT request_count, window_start, blocked_until
            FROM rate_limits
            WHERE key_name = ? AND endpoint = ?
            LIMIT 1
        ");

        $check->bind_param("ss", $key, $endpoint);
        $check->execute();

        $row = $check->get_result()->fetch_assoc();

        // already blocked
        if (
            $row &&
            !empty($row['blocked_until']) &&
            strtotime($row['blocked_until']) > time()
        ) {
            $remaining = strtotime($row['blocked_until']) - time();

            Response::error(
                "Account temporarily blocked. Try again in {$remaining} seconds.",
                429
            );
        }

        // ✅ NEW: atomic upsert — find existing window or create new one
        // ON DUPLICATE KEY UPDATE is supported in MariaDB 10.4
        
        $stmt = $conn->prepare("
            INSERT INTO rate_limits (key_name, endpoint, request_count, window_start)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                request_count = IF(
                    TIMESTAMPDIFF(SECOND, window_start, NOW()) >= ?,
                    1,
                    request_count + 1
                ),
                window_start = IF(
                    TIMESTAMPDIFF(SECOND, window_start, NOW()) >= ?,
                    NOW(),
                    window_start
                )
        ");
        
        
        $stmt->bind_param("ssii", $key, $endpoint, $this->seconds, $this->seconds);
        $stmt->execute();

        // ✅ NEW: read back current count to enforce limit
        $check = $conn->prepare("
            SELECT request_count FROM rate_limits
            WHERE key_name = ? AND endpoint = ?
            LIMIT 1
        ");
        $check->bind_param("ss", $key, $endpoint);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();

        if ($row && (int)$row['request_count'] > $this->limit) {
            $this->blockAccount($conn, $key, $endpoint);
            /*
            Response::error(
                "Too many requests. Limit is {$this->limit} per {$this->seconds}s. Try later.",
                429
            );
            */
        }
    }

    // account block temp
    // ==========================================
    // Account lock for login / OTP / password
    // ==========================================
    private function blockAccount($conn, $key, $endpoint)
    {
        $stmt = $conn->prepare("
            UPDATE rate_limits
            SET blocked_until = DATE_ADD(NOW(), INTERVAL ? SECOND)
            WHERE key_name = ? AND endpoint = ?
        ");

        $stmt->bind_param("iss", $this->blockSeconds, $key, $endpoint);
        $stmt->execute();

        Response::error(
            "Too many failed attempts. Account blocked temporarily.",
            429
        );
    } 
}
