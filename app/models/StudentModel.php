<?php

// ============================================================
// StudentModel.php
// ============================================================
// WHAT CHANGED FROM UserModel.php + RoleModel.php:
//
//   OLD: UserModel.create() inserted into `users` table
//   OLD: UserModel.assignRole() inserted into `user_roles` table
//   NEW: StudentModel.create() inserts into `students` table
//        No role assignment needed — being in `students` table = student role
//
//   OLD: getStudents() JOINed `users` + `seat_allocations` + `seats` + `halls`
//   NEW: getStudents() JOINs `students` + `seat_allocations` + `seats` + `halls`
//        also adds `admin_id` scope filter (tenant isolation)
//
//   OLD: seat_allocations.student_id → users.id
//   NEW: seat_allocations.student_id → students.id (same column, different table)
//
//   OLD: seat_allocations.slot column (shift name stored directly)
//   NEW: seat_allocations.shift_id FK → shifts.name (JOIN needed for shift name)
//
//   OLD: RoleModel.getStudentRoleId() — looked up roles table
//   NEW: Not needed. No role table. Removed entirely.
//
//   OLD: users.status was ENUM('active','blocked')
//   NEW: students.status is ENUM('active','blocked','inactive')
//
//   OLD: No admin_id scope — all students visible to any admin
//   NEW: students.admin_id ensures tenant isolation — each admin sees only their students
// ============================================================

class StudentModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connect();
    }

    // =========================================================
    // FIND BY PHONE
    // =========================================================
    // UPDATED: queries `students` (not `users`)
    // UPDATED: added admin_id scope for tenant isolation
    // =========================================================
    public function findByPhone($phone, $adminId = null)
    {
        if ($adminId) {
            $stmt = $this->conn->prepare("
                SELECT * FROM students 
                WHERE contact = ? AND admin_id = ? AND deleted_at IS NULL
            ");
            $stmt->bind_param("si", $phone, $adminId);
        } else {
            $stmt = $this->conn->prepare("
                SELECT * FROM students WHERE contact = ? AND deleted_at IS NULL
            ");
            $stmt->bind_param("s", $phone);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // CREATE STUDENT
    // =========================================================
    // UPDATED: inserts into `students` table (not `users`)
    // UPDATED: requires admin_id (tenant ownership)
    // UPDATED: generates qr_token for QR-based identification
    // UPDATED: password column is `password_hash`
    // REMOVED: no assignRole() call needed
    // =========================================================
    public function create($data)
    {
        // Generate unique QR token for student identification
        $qrToken = bin2hex(random_bytes(16));

        $stmt = $this->conn->prepare("
            INSERT INTO students 
            (admin_id, first_name, last_name, contact, password_hash, qr_token, registered_via, branch_id)
            VALUES (?, ?, ?, ?, ?, ?, 'form', ?)
        ");

        // not pass reference id set variables
        $admin_id = $data['admin_id'];
        $first_name = $data['first_name'];
        $last_name =   $data['last_name'];
        $contact =   $data['contact'];
        $password_hash =   $data['password_hash'];
        $branch_id =  $data['branch_id'] ?? null;

        $stmt->bind_param(
            "isssssi",
            $admin_id,
            $first_name,
            $last_name,
            $contact,
            $password_hash,
            $qrToken,
            $branch_id
        );

        $stmt->execute();

        return $this->conn->insert_id;
    }

    // =========================================================
    // ASSIGN ROLE (stub kept for compatibility)
    // =========================================================
    // REMOVED: role concept — being in `students` table = student
    // This method is now a NO-OP kept so old callers don't break
    // =========================================================
    public function assignRole($userId, $roleId)
    {
        // ✅ UPDATED: No-op. Role is determined by which table the user is in.
        // Kept for backward compatibility — safe to remove later.
        return true;
    }

    // =========================================================
    // GET STUDENTS (paginated, filtered, with stats)
    // =========================================================
    // UPDATED: JOINs `students` (not `users`)
    // UPDATED: JOINs `shifts` to get shift name (replaces old slot ENUM column)
    // UPDATED: added admin_id scope — admin only sees their own students
    // UPDATED: seat_allocations.shift_id FK replaces old `slot` column
    // =========================================================
    public function getStudents($params, $adminId)
    {
        $sql = "
            SELECT 
                sa.id as allocation_id,
                sa.student_id,
                sa.seat_id,
                sh.code  as slot,       -- ✅ UPDATED: shift code from shifts table (was sa.slot)
                sh.name  as shift_name, -- ✅ NEW: human readable shift name
                sa.start_date,
                sa.end_date,
                sa.status,
                st.first_name,
                st.last_name,
                st.contact,
                st.qr_token,            -- ✅ NEW: QR token for student identification
                se.seat_number as seat_number,
                h.name AS hall_name,
                b.name AS branch_name,  -- ✅ NEW: branch name for context
                f.final_amount as amount -- ✅ UPDATED: was f.amount, now f.final_amount
            FROM seat_allocations sa
            JOIN (
                SELECT student_id, MAX(id) AS max_id
                FROM seat_allocations
                GROUP BY student_id
            ) latest 
                ON latest.student_id = sa.student_id 
            AND latest.max_id = sa.id
            -- ✅ UPDATED: JOIN students (not users)
            LEFT JOIN students st ON st.id = sa.student_id AND st.deleted_at IS NULL
            LEFT JOIN seats se ON se.id = sa.seat_id AND se.deleted_at IS NULL
            LEFT JOIN halls h ON h.id = sa.hall_id AND h.deleted_at IS NULL
            LEFT JOIN branches b ON b.id = sa.branch_id AND b.deleted_at IS NULL
            -- ✅ UPDATED: JOIN shifts table to get shift name (replaces old slot ENUM)
            LEFT JOIN shifts sh ON sh.id = sa.shift_id
            LEFT JOIN fees f ON f.allocation_id = sa.id -- ✅ UPDATED: was f.id = sa.fees_id
            -- ✅ NEW: tenant isolation — only show this admin's students
            WHERE st.admin_id = ?
        ";

        $types  = "i";
        $values = [$adminId];
        $conditions = [];

        if (!empty($params['search'])) {
            $conditions[] = "(st.first_name LIKE ? OR st.last_name LIKE ? OR st.contact LIKE ?)";
            $types  .= "sss";
            $search = "%" . $params['search'] . "%";
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        if (!empty($params['hall_id'])) {
            $conditions[] = "h.id = ?";
            $types  .= "i";
            $values[] = $params['hall_id'];
        }

        // ✅ UPDATED: filter by shift code (was slot ENUM value)
        if (!empty($params['slot'])) {
            $conditions[] = "sh.code = ?";
            $types  .= "s";
            $values[] = $params['slot'];
        }

        if (!empty($params['status'])) {
            $conditions[] = "sa.status = ?";
            $types  .= "s";
            $values[] = $params['status'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $limit  = isset($params['limit'])  ? (int)$params['limit']  : 10;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

        $sql   .= " ORDER BY sa.id DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $values[] = $limit;
        $values[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    // GET STUDENTS COUNT (for pagination)
    // =========================================================
    // UPDATED: same pattern as getStudents — joins students + shifts, scoped by admin_id
    // =========================================================
    public function getStudentsCount($params, $adminId)
    {
        $sql = "
            SELECT COUNT(DISTINCT sa.student_id) as total
            FROM seat_allocations sa
            JOIN (
                SELECT student_id, MAX(id) AS max_id
                FROM seat_allocations
                GROUP BY student_id
            ) latest 
                ON latest.student_id = sa.student_id 
            AND latest.max_id = sa.id
            LEFT JOIN students st ON st.id = sa.student_id AND st.deleted_at IS NULL
            LEFT JOIN seats se ON se.id = sa.seat_id
            LEFT JOIN halls h ON h.id = sa.hall_id
            LEFT JOIN shifts sh ON sh.id = sa.shift_id
            WHERE st.admin_id = ?
        ";

        $types  = "i";
        $values = [$adminId];
        $conditions = [];

        if (!empty($params['search'])) {
            $conditions[] = "(st.first_name LIKE ? OR st.last_name LIKE ? OR st.contact LIKE ?)";
            $types  .= "sss";
            $search = "%" . $params['search'] . "%";
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        if (!empty($params['hall_id'])) {
            $conditions[] = "h.id = ?";
            $types  .= "i";
            $values[] = $params['hall_id'];
        }

        if (!empty($params['slot'])) {
            $conditions[] = "sh.code = ?";
            $types  .= "s";
            $values[] = $params['slot'];
        }

        if (!empty($params['status'])) {
            $conditions[] = "sa.status = ?";
            $types  .= "s";
            $values[] = $params['status'];
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
    // GET STUDENT STATS (dashboard cards)
    // =========================================================
    // UPDATED: same JOIN changes + admin_id scope
    // =========================================================
    public function getStudentStats($params, $adminId)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT sa.student_id) AS total,
                COUNT(DISTINCT CASE WHEN sa.status = 'active' THEN sa.student_id END) AS active,
                COUNT(DISTINCT CASE 
                    WHEN sa.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                    THEN sa.student_id 
                END) AS expiring,
                COUNT(DISTINCT CASE 
                    WHEN sa.start_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    THEN sa.student_id 
                END) AS new_week
            FROM seat_allocations sa
            JOIN (
                SELECT student_id, MAX(id) AS max_id
                FROM seat_allocations
                GROUP BY student_id
            ) latest 
                ON latest.student_id = sa.student_id 
            AND latest.max_id = sa.id
            LEFT JOIN students st ON st.id = sa.student_id AND st.deleted_at IS NULL
            LEFT JOIN seats se ON se.id = sa.seat_id
            LEFT JOIN halls h ON h.id = sa.hall_id
            LEFT JOIN shifts sh ON sh.id = sa.shift_id
            WHERE st.admin_id = ?
        ";

        $types  = "i";
        $values = [$adminId];
        $conditions = [];

        if (!empty($params['search'])) {
            $conditions[] = "(st.first_name LIKE ? OR st.contact LIKE ?)";
            $types  .= "ss";
            $search = "%" . $params['search'] . "%";
            $values[] = $search;
            $values[] = $search;
        }

        if (!empty($params['hall_id'])) {
            $conditions[] = "h.id = ?";
            $types  .= "i";
            $values[] = $params['hall_id'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}
?>
