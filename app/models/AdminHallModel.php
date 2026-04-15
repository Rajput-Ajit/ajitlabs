<?php

// ============================================================
// AdminHallModel.php
// ============================================================
// WHAT CHANGED FROM OLD AdminHallModel.php:
//
//   OLD: halls had morning_fees, evening_fees, full_day_fees as direct columns
//        and morning_from/to, evening_from/to, full_day_from/to as time columns
//   NEW: these are moved to the `shifts` table as separate rows
//        halls.id → shifts.hall_id (one hall has many shifts)
//
//   OLD: create() inserted 14 columns including all 3 shift times + fees
//   NEW: create() inserts only core hall data (name, branch_id, capacity, prefix, etc.)
//        + createShifts() creates shift rows separately
//
//   OLD: nameExistance() had no soft-delete check
//   NEW: nameExistance() adds `deleted_at IS NULL`
//
//   OLD: findById() joined branches using `owner_id`
//   NEW: findById() joins branches using `admin_id`
//
//   OLD: getAll() selected morning_fees/evening_fees/full_day_fees columns
//   NEW: getAll() uses subquery to get shifts JSON array per hall
//        occupied_seats now derived from seat_allocations (not seats.status='occupied')
//
//   OLD: total_seats was a stored column in halls
//   NEW: total_seats is a stored column in halls AND also countable from seats table
//        capacity column renamed from total_seats to capacity in new schema
//
//   OLD: no admin_id scope on hall list — all halls visible
//   NEW: admin_id scope via branches.admin_id — each admin sees only their halls
//
//   OLD: no soft delete check
//   NEW: all queries add `deleted_at IS NULL`
// ============================================================

class HallModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connect();
    }

    // =========================================================
    // NAME EXISTENCE CHECK
    // =========================================================
    // UPDATED: added deleted_at IS NULL (soft delete aware)
    // =========================================================
    public function nameExistance($name, $branchId, $excludeId = null)
    {
        if ($excludeId) {
            $stmt = $this->conn->prepare("
                SELECT id FROM halls 
                WHERE name = ? AND branch_id = ? AND id != ? AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->bind_param("sii", $name, $branchId, $excludeId);
        } else {
            $stmt = $this->conn->prepare("
                SELECT id FROM halls 
                WHERE name = ? AND branch_id = ? AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->bind_param("si", $name, $branchId);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // Check Branch Existance Before Creat Hall
    // =========================================================
    public function checkBranchExistance($branchId, $userId){
        $stmt = $this->conn->prepare("
                SELECT id FROM branches 
                WHERE id = ? AND admin_id = ? AND  deleted_at IS NULL
                LIMIT 1
            ");
        $stmt->bind_param("ii", $branchId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // CREATE HALL
    // =========================================================
    // UPDATED: removed all shift/fee columns from halls INSERT
    //          Reason: shifts are now in the `shifts` table
    // UPDATED: `total_seats` renamed to `capacity` in new schema
    // UPDATED: `prefix` renamed to `seat_prefix`
    // UPDATED: added `seat_start_number` (was `count_start` in old input)
    // =========================================================
    public function create($data)
    {
        $features = isset($data['features']) ? json_encode($data['features']) : null;

        $branchId        = $data['branch_id'];
        $hallName        = $data['hall_name'];
        $capacity        = $data['total_seats'];
        $seatPrefix      = $data['prefix'] ?? null;
        // ✅ UPDATED: count_start → seat_start_number
        $seatStartNumber = $data['count_start'] ?? 1;
        $type            = $data['type'] ?? null;

        $stmt = $this->conn->prepare("
            INSERT INTO halls 
            (branch_id, name, capacity, seat_prefix, seat_start_number, type, features, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open')
        ");

        $stmt->bind_param(
            "isisiss",
            $branchId,
            $hallName,
            $capacity,
            $seatPrefix,
            $seatStartNumber,
            $type,
            $features
        );

        $stmt->execute();

        return $this->conn->insert_id;
    }

    // =========================================================
    // CREATE SHIFTS FOR HALL
    // =========================================================
    // NEW: was not a separate method before — shifts were columns in halls
    //      Now each shift (morning/evening/fullday) is a row in shifts table
    // =========================================================
    public function createShifts($hallId, $data)
    {
        // Build shifts array from old-style flat input
        // Old API sent: morning_start, morning_end, morning_fees, evening_start...
        // We translate those into shift rows
        $shifts = [
            [
                'name'        => 'Morning',
                'code'        => 'morning',
                'start_time'  => $data['morning_start'],
                'end_time'    => $data['morning_end'],
                'monthly_fee' => $data['morning_fees'] ?? 0,
                'order'       => 1
            ],
            [
                'name'        => 'Evening',
                'code'        => 'evening',
                'start_time'  => $data['evening_start'],
                'end_time'    => $data['evening_end'],
                'monthly_fee' => $data['evening_fees'] ?? 0,
                'order'       => 2
            ],
            [
                'name'        => 'Full Day',
                'code'        => 'fullday',
                'start_time'  => $data['full_day_start'],
                'end_time'    => $data['full_day_end'],
                'monthly_fee' => $data['full_day_fees'] ?? 0,
                'order'       => 3
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO shifts 
            (hall_id, name, code, start_time, end_time, monthly_fee, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($shifts as $shift) {
            $stmt->bind_param(
                "issssdi",
                $hallId,
                $shift['name'],
                $shift['code'],
                $shift['start_time'],
                $shift['end_time'],
                $shift['monthly_fee'],
                $shift['order']
            );
            $stmt->execute();
        }
    }

    // =========================================================
    // FIND HALL BY ID
    // =========================================================
    // UPDATED: branches.owner_id → branches.admin_id
    // UPDATED: added deleted_at IS NULL
    // =========================================================
    public function findById($hallId)
    {
        $stmt = $this->conn->prepare("
            SELECT h.*, b.admin_id 
            FROM halls h
            JOIN branches b ON h.branch_id = b.id
            WHERE h.id = ? AND h.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->bind_param("i", $hallId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // UPDATE HALL
    // =========================================================
    // UPDATED: `total_seats` → `capacity`, `prefix` → `seat_prefix`
    // =========================================================
    public function update($hallId, $data)
    {
        $features   = isset($data['features']) ? json_encode($data['features']) : null;
        $seatPrefix = $data['prefix'] ?? null;

        $stmt = $this->conn->prepare("
            UPDATE halls 
            SET name = ?, capacity = ?, features = ?, seat_prefix = ?
            WHERE id = ? AND deleted_at IS NULL
        ");

        $stmt->bind_param(
            "sissi",
            $data['name'],
            $data['total_seats'],
            $features,
            $seatPrefix,
            $hallId
        );

        $stmt->execute();

        return true;
    }

    // =========================================================
    // GET ALL HALLS (paginated, filtered)
    // =========================================================
    // UPDATED: removed morning_fees/evening_fees/full_day_fees columns from SELECT
    //          Reason: these are now in the shifts table
    // UPDATED: occupied_seats now derived from seat_allocations WHERE status='active'
    //          Reason: seats no longer store occupancy as a column
    // UPDATED: total_seats now from halls.capacity
    // UPDATED: branches.owner_id → branches.admin_id
    // UPDATED: added admin_id scope filter for tenant isolation
    // UPDATED: added deleted_at IS NULL
    // NEW: shifts subquery returns JSON array of shift data per hall
    // =========================================================
    public function getAll($filters, $limit, $offset, $adminId)
    {
        $where  = ["h.deleted_at IS NULL", "b.deleted_at IS NULL", "b.admin_id = ?"];
        $params = [$adminId];
        $types  = "i";

        if (!empty($filters['branch_id'])) {
            $where[]  = "h.branch_id = ?";
            $params[] = $filters['branch_id'];
            $types   .= "i";
        }

        if (!empty($filters['hall_name'])) {
            $where[]  = "h.name LIKE ?";
            $params[] = "%" . $filters['hall_name'] . "%";
            $types   .= "s";
        }

        if (!empty($filters['branch_name'])) {
            $where[]  = "b.name LIKE ?";
            $params[] = "%" . $filters['branch_name'] . "%";
            $types   .= "s";
        }

        if (!empty($filters['status'])) {
            $where[]  = "h.status = ?";
            $params[] = $filters['status'];
            $types   .= "s";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $sql = "
            SELECT 
                h.id,
                h.branch_id,
                h.name,
                h.type,
                h.capacity as total_seats,         -- ✅ UPDATED: was total_seats column, renamed to capacity
                h.seat_prefix as prefix,            -- ✅ UPDATED: was prefix, renamed to seat_prefix
                h.seat_start_number,               -- ✅ NEW: starting number for seat numbering
                h.status,
                h.features,

                b.name as branch_name,
                b.city,

                -- ✅ FIXED: MariaDB 10.4 does NOT support JSON_ARRAYAGG+JSON_OBJECT
                --   Replaced with GROUP_CONCAT of pipe-delimited values.
                --   AdminHallService::list() splits this string back into an array.
                (
                    SELECT GROUP_CONCAT(
                        CONCAT_WS('|', sh.id, sh.code, sh.name, sh.start_time, sh.end_time, sh.monthly_fee)
                        ORDER BY sh.display_order
                        SEPARATOR ';;'
                    )
                    FROM shifts sh WHERE sh.hall_id = h.id AND sh.is_active = 1
                ) as shifts_raw,

                COUNT(s.id) as total_seat_count,

                -- ✅ UPDATED: occupied_seats from seat_allocations (not seats.status='occupied')
                --   Reason: seats no longer store occupancy state as a column
                (
                    SELECT COUNT(*) FROM seat_allocations sa
                    WHERE sa.hall_id = h.id 
                    AND sa.status = 'active'
                    AND sa.end_date >= CURDATE()
                ) as occupied_seats

            FROM halls h
            JOIN branches b ON h.branch_id = b.id
            LEFT JOIN seats s ON s.hall_id = h.id AND s.deleted_at IS NULL

            $whereSql

            GROUP BY h.id
            ORDER BY h.id DESC
            LIMIT ? OFFSET ?
        ";

        $types   .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    // GET ALL SIMPLE (for dropdowns)
    // =========================================================
    // UPDATED: added admin_id scope + deleted_at IS NULL
    // =========================================================
    public function getAllSimple($adminId = null)
    {
        if ($adminId) {
            $stmt = $this->conn->prepare("
                SELECT 
                    h.id,
                    h.name as hall_name,
                    b.name as branch_name,
                    h.created_at
                FROM halls h
                LEFT JOIN branches b ON b.id = h.branch_id
                WHERE h.deleted_at IS NULL 
                AND b.deleted_at IS NULL
                AND b.admin_id = ?
                ORDER BY h.created_at DESC
            ");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $result = $this->conn->query("
            SELECT h.id, h.name as hall_name, b.name as branch_name, h.created_at
            FROM halls h
            LEFT JOIN branches b ON b.id = h.branch_id
            WHERE h.deleted_at IS NULL AND b.deleted_at IS NULL
            ORDER BY h.created_at DESC
        ");

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    // GET SHIFTS FOR HALL
    // =========================================================
    // NEW: needed for assign seat — find shift_id from shift code
    // =========================================================
    public function getShiftByCode($hallId, $shiftCode)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM shifts 
            WHERE hall_id = ? AND code = ? AND is_active = 1
            LIMIT 1
        ");

        $stmt->bind_param("is", $hallId, $shiftCode);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // GEt Branches
    public function getBranches($adminId){
        $stmt = $this->conn->prepare("SELECT id, name FROM branches WHERE admin_id = ? LIMIT ?");
        $limit = 10;

        $stmt->bind_param("ii", $adminId, $limit);
        $stmt->execute();

        $result = $stmt->get_result();

        $branches = [];

        while ($row = $result->fetch_assoc()) {
            $branches[] = $row;
        }

        return $branches;
    }

    // hall existance with user
    public function checkHallExistance($hallId, $branchId){
        $stmt = $this->conn->prepare("
                SELECT id FROM halls 
                WHERE id = ? AND branch_id = ? AND  deleted_at IS NULL
                LIMIT 1
            ");
        $stmt->bind_param("ii", $hallId, $branchId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function deleteHall($data){
        $hallId = $data['hall_id'];
        $branchId = $data['branch_id'];

        $stmt = $this->conn->prepare("
            UPDATE halls 
            SET deleted_at = NOW() 
            WHERE id = ? 
            AND branch_id = ? 
            AND deleted_at IS NULL
        ");

        $stmt->bind_param("ii", $hallId, $branchId);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}
?>
