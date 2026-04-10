<?php

// ============================================================
// AdminModel.php
// ============================================================
// WHAT CHANGED FROM AdminUserModel.php:
//
//   OLD: INSERT into `users` table + join `user_roles` + join `roles`
//   NEW: INSERT into `admins` table directly (role is implicit by table)
//
//   OLD: createAdmin() also called assignRole() — role lookup in roles table
//   NEW: No role table. Being in `admins` table IS the role.
//
//   OLD: defaultSubscription() inserted into `user_subscriptions`
//   NEW: defaultSubscription() inserts into `admin_subscriptions`
//        with status='pending' (manual approval flow by super admin)
//        and links to plan slug='free'
//
//   OLD: creatBranch() used owner_id column
//   NEW: creatBranch() uses admin_id column (renamed in new schema)
//
//   OLD: findByEmail() searched `users`
//   NEW: findByEmail() searches `admins`
//
//   OLD: isAdmin() did a JOIN on user_roles + roles
//   NEW: isAdmin() just checks if row exists in `admins` — being there = admin
//
//   OLD: password column was `password`
//   NEW: password column is `password_hash`
// ============================================================

class AdminModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connect();
    }

    // =========================================================
    // CREATE ADMIN
    // =========================================================
    // UPDATED: Inserts into `admins` table (not `users`)
    // UPDATED: Generates UUID for the new `uuid` column
    // UPDATED: password column renamed to password_hash
    // UPDATED: status starts as 'pending_verification'
    // UPDATED: plan_id set to free plan id on registration
    // =========================================================
    public function createAdmin($data, $passwordHash)
    {
        $uuid = $this->generateUuid();

        $stmt = $this->conn->prepare("
            INSERT INTO admins 
            (uuid, first_name, last_name, email, contact, password_hash, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending_verification')
        ");

        $stmt->bind_param(
            "ssssss",
            $uuid,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['mobile'],
            $passwordHash
        );

        $stmt->execute();

        $adminId = $this->conn->insert_id;

        // ✅ UPDATED: create default subscription (free plan, pending approval)
        $this->defaultSubscription($adminId);

        // ✅ UPDATED: create first branch — admin_id instead of owner_id
        $this->createBranch($data['reading_name'], $data['city'], null, $adminId);

        // ✅ UPDATED: mark email as verified since OTP was already checked before calling this
        $this->conn->query("UPDATE admins SET email_verified=1 WHERE id=$adminId");

        return $adminId;
    }

    // =========================================================
    // FIND BY EMAIL
    // =========================================================
    // UPDATED: queries `admins` table (not `users`)
    // UPDATED: password column is now `password_hash`
    // =========================================================
    public function findByEmail($email)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM admins WHERE email = ? AND deleted_at IS NULL
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // FIND BY MOBILE
    // =========================================================
    // UPDATED: queries `admins` (not `users`)
    // =========================================================
    public function findByMobile($contact)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM admins WHERE contact = ? AND deleted_at IS NULL
        ");

        $stmt->bind_param("s", $contact);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // IS ADMIN CHECK
    // =========================================================
    // UPDATED: No more user_roles JOIN.
    //          If the row exists in `admins` table = they are admin.
    //          Also checks status is not blocked.
    // =========================================================
    public function isAdmin($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT id FROM admins 
            WHERE id = ? 
            AND status IN ('active', 'pending_verification')
            AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    // =========================================================
    // FIND BY ID
    // =========================================================
    // NEW: needed for token-based auth checks
    // =========================================================
    public function findById($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT id, uuid, first_name, last_name, email, contact, 
                   status, plan_id, plan_expires_at, is_read_only
            FROM admins 
            WHERE id = ? AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // DEFAULT SUBSCRIPTION (FREE PLAN)
    // =========================================================
    // UPDATED: inserts into `admin_subscriptions` (not `user_subscriptions`)
    // UPDATED: status = 'active' for free plan (no payment needed)
    // UPDATED: approved_by = NULL (free plan — no super admin approval)
    // =========================================================
    private function defaultSubscription($adminId)
    {
        // Get free plan id
        $plan = $this->conn->query("
            SELECT id FROM subscription_plans WHERE slug = 'free' LIMIT 1
        ")->fetch_assoc();

        if (!$plan) return;

        $planId    = $plan['id'];
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+100 years')); // free plan never expires

        $stmt = $this->conn->prepare("
            INSERT INTO admin_subscriptions 
            (admin_id, plan_id, start_date, end_date, amount_paid, payment_method, status)
            VALUES (?, ?, ?, ?, 0.00, 'manual', 'active')
        ");

        $stmt->bind_param("iiss", $adminId, $planId, $startDate, $endDate);
        $stmt->execute();

        // ✅ Also update admins.plan_id and plan_expires_at
        $this->conn->query("
            UPDATE admins 
            SET plan_id = $planId, 
                plan_expires_at = '$endDate',
                is_read_only = 0,
                status = 'active'
            WHERE id = $adminId
        ");
    }

    // =========================================================
    // CREATE BRANCH
    // =========================================================
    // UPDATED: column `owner_id` renamed to `admin_id` in new schema
    // UPDATED: added `slug` column (required, unique per admin)
    // =========================================================
    private function createBranch($name, $city, $address, $adminId)
    {
        // Generate a URL-safe slug from branch name
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');

        $stmt = $this->conn->prepare("
            INSERT INTO branches (admin_id, name, slug, city, address)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issss",
            $adminId,
            $name,
            $slug,
            $city,
            $address
        );

        $stmt->execute();
    }

    // =========================================================
    // UUID GENERATOR
    // =========================================================
    // NEW: helper to generate UUID v4 for new uuid column
    // =========================================================
    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
