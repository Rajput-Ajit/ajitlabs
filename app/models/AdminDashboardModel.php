<?php

// ============================================================
// AdminDashboardModel.php
// ============================================================
// WHAT CHANGED FROM OLD AdminDashboardModel.php:
//
//   OLD: getStats() — no admin_id scope, counted ALL halls/seats/students
//   NEW: getStats() — scoped by admin_id for tenant isolation
//
//   OLD: seats.status='occupied' used for occupied count
//   NEW: seat_allocations WHERE status='active' used for occupied count
//        Reason: seats table no longer stores occupancy state
//
//   OLD: users table JOINed for student count
//   NEW: students table JOINed
//
//   OLD: fees.amount for revenue
//   NEW: fees.final_amount for revenue (post-discount)
//
//   OLD: getRecentActivity() JOINed users
//   NEW: getRecentActivity() JOINs students
//
//   OLD: getFeeAlerts() JOINed users, used fees.user_id
//   NEW: getFeeAlerts() JOINs students, uses fees.student_id
//
//   OLD: getSeatStats() counted seats.status='occupied'/'available'
//   NEW: getSeatStats() counts from seat_allocations (active) vs total seats
// ============================================================

class DashboardModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connect();
    }

    // =========================================================
    // KPI STATS
    // =========================================================
    // UPDATED: all queries scoped by admin_id
    // UPDATED: seats.status='occupied' → seat_allocations count
    // UPDATED: users table → students table
    // UPDATED: fees.amount → fees.final_amount
    // =========================================================
    public function getStats($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                -- ✅ UPDATED: scope by admin's branches
                (SELECT COUNT(*) FROM halls h
                 JOIN branches b ON h.branch_id = b.id
                 WHERE b.admin_id = ? AND h.status = 'open' AND h.deleted_at IS NULL
                ) as total_halls,

                -- ✅ UPDATED: occupied = active seat_allocations (not seats.status column)
                (SELECT COUNT(DISTINCT sa.seat_id) FROM seat_allocations sa
                 JOIN seats s ON s.id = sa.seat_id
                 JOIN halls h ON h.id = sa.hall_id
                 JOIN branches b ON b.id = h.branch_id
                 WHERE b.admin_id = ? AND sa.status = 'active' AND sa.end_date >= CURDATE()
                ) as occupied_seats,

                -- ✅ UPDATED: total seats from seats table, scoped by admin
                (SELECT COUNT(*) FROM seats s
                 JOIN halls h ON h.id = s.hall_id
                 JOIN branches b ON b.id = h.branch_id
                 WHERE b.admin_id = ? AND s.deleted_at IS NULL
                ) as total_seats,

                -- ✅ UPDATED: from students table (not users), scoped by admin_id
                (SELECT COUNT(*) FROM students WHERE admin_id = ? AND status = 'active' AND deleted_at IS NULL
                ) as total_students,

                -- ✅ UPDATED: final_amount (post-discount), scoped by admin
                (SELECT SUM(f.final_amount) FROM fees f
                 JOIN students st ON st.id = f.student_id
                 WHERE st.admin_id = ? AND MONTH(f.created_at) = MONTH(CURDATE())
                 AND YEAR(f.created_at) = YEAR(CURDATE())
                ) as monthly_revenue
        ");

        $stmt->bind_param("iiiii", $adminId, $adminId, $adminId, $adminId, $adminId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // REVENUE CHART (last 6 months)
    // =========================================================
    // UPDATED: scoped by admin_id, uses final_amount
    // =========================================================
    public function getRevenueChart($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                DATE_FORMAT(f.created_at, '%b') as month,
                SUM(f.final_amount) as revenue
            FROM fees f
            JOIN students st ON st.id = f.student_id
            WHERE st.admin_id = ?
            AND f.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY MONTH(f.created_at), YEAR(f.created_at)
            ORDER BY f.created_at ASC
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    // SEAT STATS (for pie/donut chart)
    // =========================================================
    // UPDATED: occupied = active seat_allocations (not seats.status column)
    // UPDATED: scoped by admin_id
    // =========================================================
    public function getSeatStats($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM seats s
                 JOIN halls h ON h.id = s.hall_id
                 JOIN branches b ON b.id = h.branch_id
                 WHERE b.admin_id = ? AND s.deleted_at IS NULL
                ) as total,

                -- ✅ UPDATED: count distinct seats with active allocation today
                (SELECT COUNT(DISTINCT sa.seat_id) FROM seat_allocations sa
                 JOIN halls h ON h.id = sa.hall_id
                 JOIN branches b ON b.id = h.branch_id
                 WHERE b.admin_id = ? AND sa.status = 'active' AND sa.end_date >= CURDATE()
                ) as occupied
        ");

        $stmt->bind_param("ii", $adminId, $adminId);
        $stmt->execute();

        $row  = $stmt->get_result()->fetch_assoc();
        $row['empty'] = ($row['total'] ?? 0) - ($row['occupied'] ?? 0);

        return $row;
    }

    // =========================================================
    // RECENT ACTIVITY (last 5 seat assignments)
    // =========================================================
    // UPDATED: JOINs students (not users)
    // UPDATED: scoped by admin_id
    // =========================================================
    public function getRecentActivity($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                CONCAT(st.first_name, ' ', COALESCE(st.last_name, '')) as student_name,
                'Seat Allocated' as action,
                sa.created_at
            FROM seat_allocations sa
            -- ✅ UPDATED: JOIN students (not users)
            JOIN students st ON st.id = sa.student_id
            WHERE st.admin_id = ?
            ORDER BY sa.created_at DESC
            LIMIT 5
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    // FEE ALERTS (overdue / pending fees)
    // =========================================================
    // UPDATED: fees.user_id → fees.student_id
    // UPDATED: JOINs students (not users)
    // UPDATED: scoped by admin_id
    // UPDATED: final_amount (not amount)
    // =========================================================
    public function getFeeAlerts($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                st.first_name,
                st.last_name,
                f.final_amount as amount,
                DATEDIFF(CURDATE(), f.expiry_date) as overdue_days
            FROM fees f
            -- ✅ UPDATED: fees.student_id (was user_id), JOIN students (not users)
            JOIN students st ON st.id = f.student_id
            WHERE f.status = 'pending'
            AND f.expiry_date < CURDATE()
            AND st.admin_id = ?
            ORDER BY overdue_days DESC
            LIMIT 5
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getHallOverview($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                h.id AS hall_id,
                h.name AS hall_name,

                sh.id AS shift_id,
                sh.name AS shift_name,
                sh.code AS shift_code,
                sh.start_time,
                sh.end_time,

                COUNT(s.id) AS total_seats,

                COUNT(DISTINCT CASE 
                    WHEN sa.status = 'active'
                    AND sa.end_date >= CURDATE()
                    AND sa.shift_id = sh.id
                    THEN sa.seat_id
                END) AS occupied_seats

            FROM halls h

            JOIN branches b 
                ON b.id = h.branch_id

            JOIN shifts sh
                ON sh.hall_id = h.id
                AND sh.code = 'fullday'
                AND sh.is_active = 1

            LEFT JOIN seats s 
                ON s.hall_id = h.id
                AND s.deleted_at IS NULL

            LEFT JOIN seat_allocations sa 
                ON sa.hall_id = h.id
                AND sa.seat_id = s.id

            WHERE b.admin_id = ?
            AND h.deleted_at IS NULL

            GROUP BY h.id, sh.id

            ORDER BY h.id DESC

            LIMIT 3
        ");

        $stmt->bind_param("i", $adminId);
        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while($row = $result->fetch_assoc()){

            $data[] = [
                'hall_id'        => (int)$row['hall_id'],
                'hall_name'      => $row['hall_name'],

                'shift_name'     => $row['shift_name'],
                'shift_code'     => $row['shift_code'],

                'timing'         => date('gA', strtotime($row['start_time'])) 
                                . '-' . 
                                date('gA', strtotime($row['end_time'])),

                'total_seats'    => (int)$row['total_seats'],
                'occupied_seats' => (int)$row['occupied_seats']
            ];
        }

        return $data;
    }
}
?>
