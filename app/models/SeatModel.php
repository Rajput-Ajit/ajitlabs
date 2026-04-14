<?php

// ============================================================
// SeatModel.php
// ============================================================
// WHAT CHANGED FROM OLD SeatModel.php:
//
//   OLD: getSeatsByHall() selected morning_occupied, evening_occupied,
//        full_day_occupied, morning_expiry, evening_expiry, full_day_expiry
//        (occupancy stored as columns on the seat row)
//   NEW: getSeatsByHall() JOINs seat_allocations to get current occupancy per shift
//        Reason: seats table no longer has those columns — occupancy = booking records
//
//   OLD: assignSeat() did UPDATE seats SET morning_occupied=userId, morning_expiry=date
//   NEW: assignSeat() is REMOVED — occupation is created as a seat_allocation row instead
//        Reason: seats table has no occupancy columns anymore
//
//   OLD: seatAllocation() was a secondary "for future use" INSERT
//   NEW: seatAllocation() is now THE primary booking mechanism
//        Also now requires shift_id (FK) instead of slot string
//        Also requires hall_id + branch_id (denormalized for fast queries)
//        Also requires booked_by_type + booked_by_id (audit trail)
//
//   OLD: bulkInsert() stored seat_number as just the loop counter (integer $i)
//   NEW: bulkInsert() computes seat_number from prefix + seat_start_number + serial
//        e.g. prefix='A', start=1, serial=1 → 'A-1'
//
//   OLD: getSeatById() returned all columns including old shift columns
//   NEW: getSeatById() returns core seat fields only
//
//   OLD: deleteExtraSeats() did hard DELETE
//   NEW: deleteExtraSeats() does soft delete (deleted_at = NOW())
//        Reason: preserves historical booking/fee records that reference seat_id
//
//   OLD: updateExpiry() updated seats.expiry_date (column removed)
//   NEW: updateExpiry() is REMOVED — expiry lives in seat_allocations.end_date
//
//   OLD: No overlap check — concurrent same-shift booking was possible
//   NEW: isShiftActiveOnSeat() checks seat_allocations for active booking on same shift+date range
// ============================================================

class SeatModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = DB::connect();
    }

    // =========================================================
    // BULK INSERT SEATS
    // =========================================================
    // UPDATED: seat_number now computed with prefix + seat_start_number
    //          Old: seat_number = $i (just the loop counter, e.g. "1")
    //          New: seat_number = prefix + '-' + (seat_start_number + serial - 1)
    //               e.g. prefix='A', start=51, serial=1 → seat_number='A-51'
    //               If no prefix: just '51'
    // =========================================================
    public function bulkInsert($hallId, $totalSeats, $seatStartNumber, $prefix = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO seats (hall_id, seat_number, seat_serial)
            VALUES (?, ?, ?)
        ");

        for ($serial = 1; $serial <= $totalSeats; $serial++) {
            // ✅ UPDATED: seat_number now properly calculated
            $displayNumber = $seatStartNumber + $serial - 1;

            if ($prefix) {
                $seatNumber = $prefix . '-' . $displayNumber;
            } else {
                $seatNumber = (string)$displayNumber;
            }

            $stmt->bind_param("isi", $hallId, $seatNumber, $serial);
            $stmt->execute();
        }
    }

    // =========================================================
    // COUNT SEATS BY HALL
    // =========================================================
    // UPDATED: added deleted_at IS NULL (soft delete aware)
    // =========================================================
    public function countByHall($hallId)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total FROM seats 
            WHERE hall_id = ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("i", $hallId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc()['total'];
    }

    // =========================================================
    // ADD SEATS (for hall capacity increase)
    // =========================================================
    // UPDATED: same seat_number logic as bulkInsert
    // UPDATED: soft delete aware (no change needed, it's INSERT)
    // =========================================================
    public function addSeats($hallId, $fromSerial, $toSerial, $seatStartNumber, $prefix = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO seats (hall_id, seat_number, seat_serial)
            VALUES (?, ?, ?)
        ");

        for ($serial = $fromSerial; $serial <= $toSerial; $serial++) {
            $displayNumber = $seatStartNumber + $serial - 1;

            if ($prefix) {
                $seatNumber = $prefix . '-' . $displayNumber;
            } else {
                $seatNumber = (string)$displayNumber;
            }

            $stmt->bind_param("isi", $hallId, $seatNumber, $serial);
            $stmt->execute();
        }
    }

    // =========================================================
    // DELETE EXTRA SEATS (for hall capacity decrease)
    // =========================================================
    // UPDATED: soft delete instead of hard DELETE
    //          Reason: seats may be referenced by seat_allocations/fees history
    // =========================================================
    public function deleteExtraSeats($hallId, $fromSerial)
    {
        $stmt = $this->conn->prepare("
            UPDATE seats 
            SET deleted_at = NOW(), status = 'removed'
            WHERE hall_id = ? AND seat_serial >= ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("ii", $hallId, $fromSerial);
        $stmt->execute();
    }

    // =========================================================
    // GET SEATS BY HALL (with per-shift occupancy)
    // =========================================================
    // UPDATED: removed all old shift columns from SELECT
    //          (morning_occupied, evening_occupied, etc. no longer exist)
    // NEW: for each seat, JOINs seat_allocations to get active bookings per shift
    //      Returns one row per seat with arrays of active_shifts
    // =========================================================
    public function getSeatsByHall($hallId)
    {
        // Get all seats
        $stmt = $this->conn->prepare("
            SELECT 
                s.id,
                s.seat_number,
                s.seat_serial,
                s.custom_name,       -- ✅ NEW: custom seat label (future)
                s.status as seat_status
            FROM seats s
            WHERE s.hall_id = ? AND s.deleted_at IS NULL
            ORDER BY s.seat_serial ASC
        ");

        $stmt->bind_param("i", $hallId);
        $stmt->execute();
        $seats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($seats)) return [];

        // ✅ UPDATED: get active allocations for all seats in this hall in one query
        //   Old: each seat row had morning_occupied, evening_occupied columns
        //   New: we JOIN seat_allocations + students + shifts to build occupancy map
        $hallIdInt = (int)$hallId;
        $allocResult = $this->conn->query("
            SELECT 
                sa.seat_id,
                sh.code as shift_code,
                sh.name as shift_name,
                sa.end_date,
                sa.start_date,
                st.first_name,
                st.last_name,
                st.contact,
                st.id as student_id
            FROM seat_allocations sa
            -- ✅ UPDATED: JOIN students (not users)
            JOIN students st ON st.id = sa.student_id
            -- ✅ UPDATED: JOIN shifts to get shift name (replaces slot string)
            JOIN shifts sh ON sh.id = sa.shift_id
            WHERE sa.hall_id = $hallIdInt
            AND sa.status = 'active'
            AND sa.end_date >= CURDATE()
        ");

        // Build a map: seat_id → array of active shift bookings
        $occupancyMap = [];
        while ($row = $allocResult->fetch_assoc()) {
            $seatId = $row['seat_id'];
            $occupancyMap[$seatId][] = $row;
        }

        // Attach occupancy info to each seat
        foreach ($seats as &$seat) {
            $seat['active_shifts'] = $occupancyMap[$seat['id']] ?? [];
        }

        return $seats;
    }

    // =========================================================
    // GET SEAT BY ID (seat id & hall id)
    // =========================================================
    // UPDATED: removed old shift columns from SELECT
    // UPDATED: added deleted_at IS NULL
    // =========================================================
    public function getSeatById($seatId, $hallId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                s.id,
                s.hall_id,
                s.seat_number,
                s.seat_serial,
                s.custom_name,
                s.status,
                s.deleted_at,
                h.branch_id,
                b.admin_id
            FROM seats s
            JOIN halls h ON h.id = s.hall_id
            JOIN branches b ON b.id = h.branch_id
            WHERE s.id = ? AND s.hall_id = ? AND s.deleted_at IS NULL
        ");
        $stmt->bind_param("ii", $seatId, $hallId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================
    // CHECK IF SHIFT IS ACTIVE ON SEAT
    // =========================================================
    // NEW: proper overlap check using seat_allocations
    //      Old code checked seats.morning_occupied column (removed)
    //      New code checks: any active allocation for this seat+shift overlapping requested dates
    // =========================================================
    public function isShiftActiveOnSeat($seatId, $shiftId, $startDate, $endDate)
    {
        $stmt = $this->conn->prepare("
            SELECT id FROM seat_allocations
            WHERE seat_id = ?
            AND shift_id = ?
            AND status = 'active'
            AND (
                -- existing booking overlaps with requested range
                start_date <= ? AND end_date >= ?
            )
            LIMIT 1
        ");

        $stmt->bind_param("iiss", $seatId, $shiftId, $endDate, $startDate);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    // =========================================================
    // ASSIGN SEAT (was: UPDATE seats SET morning_occupied...)
    // =========================================================
    // REMOVED: Old assignSeat() updated shift columns on seats table
    //          Those columns no longer exist
    // NEW:     seatAllocation() below is the ONLY booking mechanism now
    // =========================================================

    // =========================================================
    // SEAT ALLOCATION (CREATE BOOKING RECORD)
    // =========================================================
    // UPDATED: was a "future use" secondary record, now THE primary record
    // UPDATED: added shift_id (FK) instead of slot string
    // UPDATED: added hall_id + branch_id (denormalized for fast queries)
    // UPDATED: added booked_by_type + booked_by_id for audit
    // REMOVED: fees_id column (now fees.allocation_id → this record instead)
    // =========================================================
    public function seatAllocation($studentId, $seatId, $shiftId, $hallId, $branchId, $startDate, $endDate, $bookedByType = 'admin', $bookedById)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO seat_allocations 
            (student_id, seat_id, shift_id, hall_id, branch_id, start_date, end_date, status, booked_by_type, booked_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ");

        $stmt->bind_param(
            "iiiiiissi",
            $studentId,
            $seatId,
            $shiftId,
            $hallId,
            $branchId,
            $startDate,
            $endDate,
            $bookedByType,
            $bookedById
        );

        $stmt->execute();

        return $this->conn->insert_id;
    }

    public function getShifts($hallId){
        $query = "SELECT 
                    id,
                    hall_id,
                    name,
                    code,
                    start_time,
                    end_time,
                    monthly_fee
                FROM shifts
                WHERE hall_id = ?
                AND is_active = 1
                ORDER BY display_order ASC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return ['error' => $this->conn->error];
        }

        // Bind parameter (i = integer)
        $stmt->bind_param("i", $hallId);

        // Execute
        $stmt->execute();

        // Get result
        $result = $stmt->get_result();

        // Fetch all data
        $data = $result->fetch_all(MYSQLI_ASSOC);

        // Close statement
        $stmt->close();

        return $data;
    }
}
?>
