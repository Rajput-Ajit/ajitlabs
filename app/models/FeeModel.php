<?php

// ============================================================
// FeeModel.php
// ============================================================
// WHAT CHANGED FROM OLD FeeModel.php:
//
//   OLD: create() params: userId, seat_id, hall_id, amount, method, duration, shift, start_date, expiry, admin_id
//   NEW: create() params restructured:
//        - Added allocation_id (FK to seat_allocations — required, not optional)
//        - Added shift_id (FK to shifts, replaces string shift name)
//        - Added branch_id (denormalized for fast filtering)
//        - Added discount + final_amount columns
//        - Added collected_by_type (admin or sub_admin)
//        - Added receipt_number (auto-generated)
//        - duration column: was ENUM('1month','3month',...) — now tinyint months count
//        - shift column: REMOVED (now shift_id FK)
//
//   OLD: getFeeStats() — global, no admin_id scope
//   NEW: getFeeStats() — scoped by admin_id via students.admin_id JOIN
//
//   OLD: getOverduePayments() JOINed `users` (not `students`)
//   NEW: getOverduePayments() JOINs `students`
//
//   OLD: getPayments() JOINed `users` and had `shift` column in SELECT
//   NEW: getPayments() JOINs `students` + `shifts`
//        shift name from shifts.name (not old fees.shift column)
//        amount from fees.final_amount (not fees.amount)
//
//   OLD: no admin_id scope on any query
//   NEW: all queries scoped by admin_id for tenant isolation
// ============================================================

class FeeModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connect();
    }

    // =========================================================
    // CREATE FEE RECORD
    // =========================================================
    // UPDATED: new column list matches new schema
    // UPDATED: shift_id FK instead of shift string
    // UPDATED: allocation_id required (links to seat_allocations)
    // UPDATED: discount + final_amount columns added
    // UPDATED: collected_by_type + collected_by_id (not just collected_by)
    // UPDATED: duration_months is INT (not ENUM string)
    // =========================================================
    public function create($data, $adminId)
    {
        // ✅ UPDATED: generate receipt number (auto-increment style)
        $receiptNumber = $this->generateReceiptNumber($adminId);

        $discount    = $data['discount'] ?? 0;
        $finalAmount = $data['amount'] - $discount;

        $stmt = $this->conn->prepare("
            INSERT INTO fees 
            (student_id, allocation_id, seat_id, shift_id, hall_id, branch_id,
             amount, discount, final_amount, payment_method, payment_ref,
             duration_months, start_date, expiry_date, status,
             collected_by_type, collected_by_id, receipt_number, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?)
        ");

        $collectedByType = $data['collected_by_type'] ?? 'admin';
        $collectedById   = $adminId;
        $paymentRef      = $data['payment_ref'] ?? null;
        $notes           = $data['notes'] ?? null;

        $stmt->bind_param(
            "iiiiiidddssisssiss",
            $data['student_id'],
            $data['allocation_id'],
            $data['seat_id'],
            $data['shift_id'],        // ✅ UPDATED: shift_id FK (not string)
            $data['hall_id'],
            $data['branch_id'],       // ✅ NEW: branch_id
            $data['amount'],
            $discount,
            $finalAmount,
            $data['payment_method'],
            $paymentRef,
            $data['duration_months'], // ✅ UPDATED: INT months (not ENUM string)
            $data['start_date'],
            $data['expiry_date'],
            $collectedByType,
            $collectedById,
            $receiptNumber,
            $notes
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        return null;
    }

    // =========================================================
    // GET FEE STATS (dashboard cards)
    // =========================================================
    // UPDATED: scoped by admin_id via students table JOIN
    // UPDATED: uses final_amount (not amount) for accurate revenue
    // =========================================================
    public function getFeeStats($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(f.final_amount) as total_revenue,

                SUM(CASE WHEN f.status = 'paid' THEN f.final_amount ELSE 0 END) as collected,

                SUM(CASE WHEN f.status = 'pending' THEN f.final_amount ELSE 0 END) as pending,

                ROUND(
                    (SUM(CASE WHEN f.status='paid' THEN f.final_amount ELSE 0 END) 
                    / NULLIF(SUM(f.final_amount), 0)) * 100,
                2) as collection_percent

            FROM fees f
            -- ✅ UPDATED: JOIN students (not users) for tenant isolation
            JOIN students st ON st.id = f.student_id
            WHERE st.admin_id = ?
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // GET OVERDUE PAYMENTS
    // =========================================================
    // UPDATED: JOINs students (not users)
    // UPDATED: scoped by admin_id
    // =========================================================
    public function getOverduePayments($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                st.first_name,
                st.last_name,
                se.seat_number,
                f.final_amount as amount,
                DATEDIFF(CURDATE(), f.expiry_date) as overdue_days
            FROM fees f
            -- ✅ UPDATED: JOIN students (not users)
            JOIN students st ON st.id = f.student_id
            JOIN seats se ON se.id = f.seat_id
            WHERE f.status = 'pending'
            AND f.expiry_date < CURDATE()
            AND st.admin_id = ?
            ORDER BY overdue_days DESC
            LIMIT 10
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    // GET PAYMENTS (main fee table with filters)
    // =========================================================
    // UPDATED: JOINs students (not users)
    // UPDATED: JOINs shifts to get shift name (replaces old fees.shift column)
    // UPDATED: uses final_amount (not amount)
    // UPDATED: scoped by admin_id
    // =========================================================
    public function getPayments($params, $adminId)
    {
        $sql = "
            SELECT 
                CONCAT(st.first_name, ' ', COALESCE(st.last_name, '')) as student_name,
                st.contact,
                h.name as hall_name,
                se.seat_number,
                sh.name as shift_name, -- ✅ NEW: from shifts table (was fees.shift string)
                f.final_amount as amount, -- ✅ UPDATED: final_amount (post-discount)
                f.discount,
                f.payment_method,
                f.receipt_number,      -- ✅ NEW: receipt number
                f.created_at,
                f.status
            FROM fees f
            -- ✅ UPDATED: JOIN students (not users)
            JOIN students st ON st.id = f.student_id
            JOIN seats se ON se.id = f.seat_id
            JOIN halls h ON h.id = f.hall_id
            -- ✅ NEW: JOIN shifts for shift name
            LEFT JOIN shifts sh ON sh.id = f.shift_id
            WHERE st.admin_id = ?
        ";

        $types  = "i";
        $values = [$adminId];
        $conditions = [];

        if (!empty($params['search'])) {
            $conditions[] = "(st.first_name LIKE ? OR st.contact LIKE ?)";
            $types  .= "ss";
            $values[] = "%" . $params['search'] . "%";
            $values[] = "%" . $params['search'] . "%";
        }

        if (!empty($params['status'])) {
            $conditions[] = "f.status = ?";
            $types  .= "s";
            $values[] = $params['status'];
        }

        if (!empty($params['hall_id'])) {
            $conditions[] = "f.hall_id = ?";
            $types  .= "i";
            $values[] = $params['hall_id'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $limit  = $params['limit']  ?? 10;
        $offset = $params['offset'] ?? 0;

        $sql   .= " ORDER BY f.id DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $values[] = $limit;
        $values[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    // GET PAYMENTS COUNT (pagination)
    // =========================================================
    // UPDATED: same as getPayments — students JOIN + admin_id scope
    // =========================================================
    public function getPaymentsCount($params, $adminId)
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM fees f
            JOIN students st ON st.id = f.student_id
            WHERE st.admin_id = ?
        ";

        $types  = "i";
        $values = [$adminId];
        $conditions = [];

        if (!empty($params['search'])) {
            $conditions[] = "(st.first_name LIKE ? OR st.contact LIKE ?)";
            $types  .= "ss";
            $values[] = "%" . $params['search'] . "%";
            $values[] = "%" . $params['search'] . "%";
        }

        if (!empty($params['status'])) {
            $conditions[] = "f.status = ?";
            $types  .= "s";
            $values[] = $params['status'];
        }

        if (!empty($params['hall_id'])) {
            $conditions[] = "f.hall_id = ?";
            $types  .= "i";
            $values[] = $params['hall_id'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    // =========================================================
    // GENERATE RECEIPT NUMBER
    // =========================================================
    // NEW: auto-generates a receipt number per admin
    //      Format: ADM{admin_id}-{YEAR}-{5-digit-sequence}
    //      e.g. ADM2-2026-00042
    // =========================================================
    private function generateReceiptNumber($adminId)
    {
        $year = date('Y');

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as cnt FROM fees f
            JOIN students st ON st.id = f.student_id
            WHERE st.admin_id = ? AND YEAR(f.created_at) = ?
        ");
        $stmt->bind_param("ii", $adminId, $year);
        $stmt->execute();

        $count  = $stmt->get_result()->fetch_assoc()['cnt'] + 1;
        return "ADM{$adminId}-{$year}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
?>
