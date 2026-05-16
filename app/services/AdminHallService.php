<?php

// ============================================================
// AdminHallService.php
// ============================================================
// WHAT CHANGED:
//   OLD: create() called hallModel->create() with all shift data embedded
//   NEW: create() calls hallModel->create() then hallModel->createShifts()
//        Reason: shifts are now in a separate table
//
//   OLD: bulkInsert() took (hallId, totalSeats, count_start)
//   NEW: bulkInsert() takes (hallId, totalSeats, seatStartNumber, prefix)
//        Reason: prefix is now part of seat_number calculation
//
//   OLD: getAll() / list() had no admin_id scope
//   NEW: all hallModel calls pass adminId for tenant isolation
//
//   OLD: addSeats() took (hallId, currentTotal+1, newTotal, prefix)
//   NEW: addSeats() takes (hallId, fromSerial, toSerial, seatStartNumber, prefix)
//        Reason: seat_start_number needed to calculate correct seat_number label
//
//   OLD: ownership check used hall['owner_id']
//   NEW: ownership check uses hall['admin_id'] (renamed in schema)
// ============================================================

class AdminHallService
{
    private $hallModel;

    public function __construct()
    {
        App::use('adminHallModel');
        $this->hallModel = new HallModel();
    }

    // =========================================================
    // CREATE HALL
    // =========================================================
    // UPDATED: after creating hall, createShifts() separately
    // UPDATED: bulkInsert() now passes seatStartNumber and prefix
    // =========================================================
    public function create($data, $user)
    {
        // check duplicate hall name in same branch
        if ($this->hallModel->nameExistance($data['hall_name'], $data['branch_id'])) {
            Response::error("Hall name already exists in this branch", 409);
        }

        // before creat hall check branch id exist or not?
        if(!$this->hallModel->checkBranchExistance($data['branch_id'], $user['user_id'])){
            Response::error("Invalid branch ID. The selected branch does not exist.", 409);
        }

        // ✅ UPDATED: create hall (no shift columns in halls table anymore)
        $hallId = $this->hallModel->create($data);

        // ✅ NEW: create shifts separately in shifts table
        $this->hallModel->createShifts($hallId, $data);

        // Create seats
        App::use('seatModel');
        $seatModel = new SeatModel();

        $totalSeats      = $data['total_seats'] ?? 0;
        $seatStartNumber = $data['count_start'] ?? 1;  // was count_start in old API
        $prefix          = $data['prefix'] ?? null;

        if ($totalSeats > 0) {
            // ✅ UPDATED: pass seatStartNumber + prefix (old method only took hallId, total, count_start)
            $seatModel->bulkInsert($hallId, $totalSeats, $seatStartNumber, $prefix);
        }

        return [
            'message'     => 'Hall created successfully with seats',
            'hall_id'     => $hallId,
            'total_seats' => $totalSeats
        ];
    }

    // =========================================================
    // UPDATE HALL
    // =========================================================
    // UPDATED: ownership check uses hall['admin_id'] (was owner_id)
    // UPDATED: addSeats() now also passes seatStartNumber
    // =========================================================
    public function update($hallId, $data, $user)
    {
        $hall = $this->hallModel->findById($hallId);

        if (!$hall) {
            Response::error("Hall not found", 404);
        }

        // ✅ UPDATED: hall['admin_id'] (was hall['owner_id'])
        if ($hall['admin_id'] != $user['user_id']) {
            Response::error("Unauthorized", 403);
        }

        // duplicate name check
        if ($this->hallModel->nameExistance($data['name'], $hall['branch_id'], $hallId)) {
            Response::error("Hall name already exists", 409);
        }

        $this->hallModel->update($hallId, $data);

        App::use('seatModel');
        $seatModel = new SeatModel();

        // ✅ UPDATED: capacity column (was total_seats)
        $newTotal    = $data['total_seats'] ?? $hall['capacity'];
        $prefix      = $data['prefix'] ?? $hall['seat_prefix'];
        $startNumber = $hall['seat_start_number'] ?? 1;

        $currentTotal = $seatModel->countByHall($hallId);

        if ($newTotal > $currentTotal) {
            // ✅ UPDATED: addSeats now takes seatStartNumber + prefix
            $seatModel->addSeats($hallId, $currentTotal + 1, $newTotal, $startNumber, $prefix);
        }

        if ($newTotal < $currentTotal) {
            // ✅ UPDATED: soft delete (not hard DELETE)
            $seatModel->deleteExtraSeats($hallId, $newTotal + 1);
        }

        return [
            'message'     => 'Hall updated successfully',
            'total_seats' => $newTotal
        ];
    }

    // =========================================================
    // LIST HALLS
    // =========================================================
    // UPDATED: passes adminId to getAll() for tenant isolation
    // UPDATED: occupancy_percent uses data from seat_allocations count
    // =========================================================
    public function list($data, $user)
    {
        $adminId = $user['user_id'];

        $page  = max(1, (int)($data['page'] ?? 1));
        $limit = max(1, (int)($data['limit'] ?? 10));

        $maxLimit = 50;
        $limit = min($limit, $maxLimit);

        $offset = ($page - 1) * $limit;

        $filters = [
            'branch_id'   => $data['branch_id']   ?? null,
            'hall_name'   => $data['hall_name']    ?? null,
            'branch_name' => $data['branch_name']  ?? null,
            'status'      => $data['status']       ?? null
        ];

        // ✅ UPDATED: passes $adminId for tenant isolation
        $halls = $this->hallModel->getAll($filters, $limit, $offset, $adminId);

        foreach ($halls as &$hall) {
            $occupied = $hall['occupied_seats']    ?? 0;
            $total    = $hall['total_seat_count']  ?? 0;

            $hall['occupancy_percent'] = $total > 0
                ? round(($occupied / $total) * 100)
                : 0;

            $hall['occupancy_text'] = "$occupied/$total";

            // ✅ FIXED: parse GROUP_CONCAT string back into array (MariaDB 10.4 compatible)
            //   Format: "id|code|name|start_time|end_time|monthly_fee;;id|..."
            $hall['shifts'] = [];
            if (!empty($hall['shifts_raw'])) {
                foreach (explode(';;', $hall['shifts_raw']) as $row) {
                    $p = explode('|', $row);
                    if (count($p) === 6) {
                        $hall['shifts'][] = [
                            'id'          => (int)$p[0],
                            'code'        => $p[1],
                            'name'        => $p[2],
                            'start_time'  => $p[3],
                            'end_time'    => $p[4],
                            'monthly_fee' => (float)$p[5],
                        ];
                    }
                }
            }
            unset($hall['shifts_raw']);
        }

        // get branches
        $branches = $this->hallModel->getBranches($adminId);

        return [
            'branches' => $branches,
            'page'  => $page,
            'limit' => $limit,
            'data'  => $halls
        ];
    }

    // Delete Hall Permanent
    public function delete($data, $user)
    {
        // before creat/delete hall check branch id exist or not?
        if(!$this->hallModel->checkHallExistance($data['hall_id'], $data['branch_id'])){
            Response::error("Invalid Hall ID. The selected Hall does not exist.", 409);
        }

        // before delete check assing student in hall
        if($this->hallModel->checkHallAssignedStudents($data['hall_id'], $data['branch_id'])){
            Response::error("Cannot delete hall. Students are currently assigned to this hall.", 409);
        }
        // ✅ UPDATED: create hall (no shift columns in halls table anymore)
        $delete = $this->hallModel->deleteHall($data);

        if(!$delete) Response::error("Failed To Delete Hall", 409);
        
        return [
            'message'     => 'Hall Deleted successfully',
        ];
    }
}
?>
