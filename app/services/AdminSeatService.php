<?php

// ============================================================
// AdminSeatService.php
// ============================================================
// WHAT CHANGED:
//   OLD: list() determined seat status from seat.morning_occupied,
//        evening_occupied, full_day_occupied columns
//   NEW: list() reads seat.active_shifts array (populated from seat_allocations JOIN)
//        Reason: those columns are removed from seats table
//
//   OLD: assign() used seats.morning_occupied columns to check availability
//        and UPDATE seats SET morning_occupied=userId
//   NEW: assign() uses isShiftActiveOnSeat() to check seat_allocations for overlap
//        and creates a seat_allocation record (no UPDATE to seats table)
//
//   OLD: assign() looked up shift via hardcoded map (morning→'morning_occupied')
//   NEW: assign() looks up shift via hallModel->getShiftByCode() → returns shift_id
//
//   OLD: feeModel->create() passed string shift name
//   NEW: feeModel->create() passes shift_id FK
//
//   OLD: student created in UserModel (users table)
//   NEW: student created in StudentModel (students table)
//        also requires admin_id for tenant ownership
//
//   OLD: userModel->assignRole() assigned student role in user_roles table
//   NEW: no role assignment needed (being in students table = student)
//
//   OLD: getAllSimple() had no adminId scope
//   NEW: getAllSimple() passes adminId
// ============================================================

class AdminSeatService
{
    private $seatModel;

    public function __construct()
    {
        App::use('seatModel');
        $this->seatModel = new SeatModel();
    }

    // =========================================================
    // LIST SEATS (seat map view)
    // =========================================================
    // UPDATED: seat status determined from active_shifts array (not column values)
    // UPDATED: hallModel->getAllSimple() gets adminId scope
    // =========================================================
    public function list($data, $user)
    {
        $adminId = $user['user_id'];

        App::use('adminHallModel');
        $hallModel = new HallModel();

        // ✅ UPDATED: pass adminId to scope halls to this admin only
        $halls = $hallModel->getAllSimple($adminId);

        $hallOptions = [];
        foreach ($halls as $h) {
            $hallOptions[] = [
                'id'   => $h['id'],
                'name' => $h['hall_name'] . " (" . $h['branch_name'] . ")"
            ];
        }

        $hallId = $data['hall_id'] ?? null;
        if (!$hallId) {
            $hallId = $halls[0]['id'] ?? null;
        }

        if (!$hallId) {
            return [
                'selected_hall_id' => null,
                'halls'            => [],
                'stats'            => [],
                'data'             => []
            ];
        }

        // ✅ UPDATED: getSeatsByHall() now returns active_shifts array per seat
        $seats = $this->seatModel->getSeatsByHall($hallId);

        $empty    = 0;
        $occupied = 0;
        $half     = 0;

        foreach ($seats as &$seat) {
            $activeShifts = $seat['active_shifts'] ?? [];
            $shiftCodes   = array_column($activeShifts, 'shift_code');

            // ✅ UPDATED: determine status from active_shifts array
            //   Old: used seat.morning_occupied / evening_occupied column values
            //   New: check which shift codes are active
            if (empty($activeShifts)) {
                $seat['status'] = 'empty';
                $empty++;
            }
            elseif (in_array('fullday', $shiftCodes)) {
                $seat['status'] = 'fullday';
                $occupied++;
            }
            elseif (in_array('morning', $shiftCodes) && in_array('evening', $shiftCodes)) {
                $seat['status'] = 'fullday'; // both half shifts = effectively full
                $occupied++;
            }
            elseif (in_array('morning', $shiftCodes)) {
                $seat['status'] = 'morning';
                $occupied++;
                $half++;
            }
            elseif (in_array('evening', $shiftCodes)) {
                $seat['status'] = 'evening';
                $occupied++;
                $half++;
            }
            else {
                $seat['status'] = 'empty';
                $empty++;
            }

            $seat['label'] = $seat['custom_name'] ?? $seat['seat_number'];
            $seat['paid']  = true;

            // ✅ NEW: include who is occupying each shift (for tooltip on seat map)
            $seat['occupants'] = $activeShifts;
        }


        // shifts selected hall
        $hallShifts = $this->seatModel->getShifts($hallId);

        return [
            'selected_hall_id' => $hallId,
            'halls'            => $hallOptions,
            "hall_shifts"      => $hallShifts,
            'stats'            => [
                'empty'    => $empty,
                'occupied' => $occupied,
                'half'     => $half,
                'total'    => count($seats)
            ],
            'data' => $seats
        ];
    }

    // =========================================================
    // ASSIGN SEAT
    // =========================================================
    // UPDATED: shift lookup uses hallModel->getShiftByCode() → shift_id FK
    // UPDATED: availability check uses isShiftActiveOnSeat() on seat_allocations
    // UPDATED: no UPDATE to seats table (columns removed)
    // UPDATED: student created in StudentModel (not UserModel) with admin_id
    // UPDATED: no role assignment needed
    // UPDATED: feeModel->create() receives shift_id (not string shift name)
    // UPDATED: seatAllocation() now THE primary record (not secondary)
    // =========================================================
    public function assign($data, $admin)
    {
        App::useMany(['seatModel', 'studentModel', 'feeModel', 'adminHallModel']);

        $seatModel    = new SeatModel();
        $studentModel = new StudentModel();
        $feeModel     = new FeeModel();
        $hallModel    = new HallModel();

        $adminId  = $admin['user_id'];
        $seatId   = $data['seat_id'];
        $hallId   = $data['hall_id'];
        $name     = $data['student_name'];
        $phone    = $data['mobile'];
        $shift    = $data['shift'];      // code: 'morning' | 'evening' | 'fullday'
        $duration = (int)$data['duration'];
        $startDate = $data['start_date'];

        // =========================================================
        // 1. GET SEAT + VALIDATE
        // =========================================================
        $seat = $seatModel->getSeatById($seatId, $hallId);

        if (!$seat) {
            Response::error("Seat not found", 404);
        }

        if ($seat['status'] === 'removed') {
            Response::error("This seat has been removed", 400);
        }

        // ✅ UPDATED: ownership check — seat belongs to this admin
        if ($seat['admin_id'] != $adminId) {
            Response::error("Seat does not belong to your library", 403);
        }

        // =========================================================
        // 2. GET SHIFT BY CODE (NEW — old code used hardcoded column map)
        // =========================================================
        // ✅ NEW: look up shift row from shifts table to get shift_id
        $shiftRow = $hallModel->getShiftByCode($hallId, $shift);

        if (!$shiftRow) {
            Response::error("Invalid shift '$shift' for this hall", 400);
        }

        $shiftId = $shiftRow['id'];

        // =========================================================
        // 3. CHECK AVAILABILITY (via seat_allocations)
        // =========================================================
        // ✅ UPDATED: old code checked seat.morning_occupied column
        //   New code queries seat_allocations for overlap
        $endDate = (new DateTime($startDate))->modify("+$duration months")->format('Y-m-d');

        // Check if fullday shift already active
        $fulldayShift = $hallModel->getShiftByCode($hallId, 'fullday');
        if ($fulldayShift && $seatModel->isShiftActiveOnSeat($seatId, $fulldayShift['id'], $startDate, $endDate)) {
            Response::error("Seat is already assigned for Full Day", 400);
        }

        // Check the requested shift itself
        if ($seatModel->isShiftActiveOnSeat($seatId, $shiftId, $startDate, $endDate)) {
            Response::error("Seat is already assigned for $shift shift", 400);
        }

        // If booking fullday, check morning and evening too
        if ($shift === 'fullday') {
            $morningShift = $hallModel->getShiftByCode($hallId, 'morning');
            if ($morningShift && $seatModel->isShiftActiveOnSeat($seatId, $morningShift['id'], $startDate, $endDate)) {
                Response::error("Seat already assigned for Morning shift", 400);
            }

            $eveningShift = $hallModel->getShiftByCode($hallId, 'evening');
            if ($eveningShift && $seatModel->isShiftActiveOnSeat($seatId, $eveningShift['id'], $startDate, $endDate)) {
                Response::error("Seat already assigned for Evening shift", 400);
            }
        }

        // =========================================================
        // 4. FIND OR CREATE STUDENT
        // =========================================================
        // ✅ UPDATED: studentModel (not userModel), checks admin_id scope
        $student = $studentModel->findByPhone($phone, $adminId);

        if (!$student) {
            $parts    = explode(' ', $name, 2);
            $firstName = $parts[0];
            $lastName  = $parts[1] ?? '';

            $studentId = $studentModel->create([
                'admin_id'      => $adminId,      // ✅ NEW: tenant ownership
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'contact'       => $phone,
                'password_hash' => password_hash($phone, PASSWORD_BCRYPT),
                'branch_id'     => $seat['branch_id'] ?? null
            ]);

            // ✅ REMOVED: no assignRole() needed — students table = student role
        } else {
            $studentId = $student['id'];
        }

        // =========================================================
        // 5. CREATE SEAT ALLOCATION (THE primary booking record)
        // =========================================================
        // ✅ UPDATED: seatAllocation() now takes shift_id, hall_id, branch_id
        //   Old: was secondary "for future use"
        //   New: IS the primary booking record
        $allocationId = $seatModel->seatAllocation(
            $studentId,
            $seatId,
            $shiftId,         // ✅ NEW: FK to shifts table
            $hallId,
            $seat['branch_id'],
            $startDate,
            $endDate,
            'admin',
            $adminId
        );

        // =========================================================
        // 6. RECORD FEE
        // =========================================================
        // ✅ UPDATED: feeModel->create() now takes structured array
        //   Added: allocation_id, shift_id, branch_id
        //   Removed: string shift name (now shift_id FK)
        $feeId = $feeModel->create([
            'student_id'      => $studentId,
            'allocation_id'   => $allocationId,  // ✅ NEW: links fee to booking
            'seat_id'         => $seatId,
            'shift_id'        => $shiftId,        // ✅ NEW: FK
            'hall_id'         => $hallId,
            'branch_id'       => $seat['branch_id'], // ✅ NEW: denormalized
            'amount'          => $data['collected_fees'],
            'discount'        => 0,
            'payment_method'  => $data['payment_method'],
            'duration_months' => $duration,       // ✅ UPDATED: INT (not enum string)
            'start_date'      => $startDate,
            'expiry_date'     => $endDate,
        ], $adminId);

        // ✅ REMOVED: seatModel->assignSeat() — that updated shift columns on seats
        //   Those columns no longer exist. The allocation IS the occupancy record.

        return [
            'message'     => 'Seat assigned successfully',
            'student_id'  => $studentId,
            'seat_id'     => $seatId,
            'shift'       => $shift,
            'expiry_date' => $endDate,
            'receipt'     => "ADM{$adminId}-" . date('Y') . '-...' // receipt_number from fee
        ];
    }
}
?>
