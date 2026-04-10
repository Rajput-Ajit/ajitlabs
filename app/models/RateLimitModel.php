<?php

// ============================================================
// RateLimitModel.php
// ============================================================
// WHAT CHANGED:
//   OLD: used by RateLimitMiddleware via 4 separate methods:
//        find(), create(), increment(), reset()
//   NEW: RateLimitMiddleware now uses a single atomic
//        INSERT ... ON DUPLICATE KEY UPDATE query directly.
//        This model is kept ONLY so App::use('rateLimitModel')
//        does not throw a missing-key error if called elsewhere.
//
//   The find/create/increment/reset methods are kept intact
//   in case any future code needs them, but the middleware
//   no longer calls them.
// ============================================================

class RateLimitModel
{
    private $conn;

    public function __construct()
    {
        App::use('db');
        $this->conn = DB::connect();
    }

    public function find($key, $endpoint)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM rate_limits
            WHERE key_name = ? AND endpoint = ?
        ");
        $stmt->bind_param("ss", $key, $endpoint);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($key, $endpoint)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO rate_limits (key_name, endpoint, window_start)
            VALUES (?, ?, NOW())
        ");
        $stmt->bind_param("ss", $key, $endpoint);
        $stmt->execute();
    }

    public function increment($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE rate_limits
            SET request_count = request_count + 1
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    public function reset($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE rate_limits
            SET request_count = 1, window_start = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
