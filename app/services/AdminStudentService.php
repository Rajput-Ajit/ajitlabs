<?php

// ============================================================
// AdminStudentService.php
// ============================================================
// WHAT CHANGED:
//   OLD: list() called userModel methods (UserModel)
//   NEW: list() calls studentModel methods (StudentModel)
//
//   OLD: no adminId passed — all students visible
//   NEW: adminId extracted from $user token, passed to model for tenant isolation
// ============================================================

class AdminStudentService
{
    private $studentModel;

    public function __construct()
    {
        // ✅ UPDATED: 'userModel' → 'studentModel'
        App::use('studentModel');
        $this->studentModel = new StudentModel();
    }

    public function list($params, $user)
    {
        // ✅ NEW: extract adminId for tenant isolation
        $adminId = $user['user_id'];

        // ✅ UPDATED: all three methods now receive $adminId
        $students = $this->studentModel->getStudents($params, $adminId);
        $total    = $this->studentModel->getStudentsCount($params, $adminId);
        $stats    = $this->studentModel->getStudentStats($params, $adminId);

        return [
            "total"  => $total,
            "limit"  => isset($params['limit'])  ? (int)$params['limit']  : 10,
            "offset" => isset($params['offset']) ? (int)$params['offset'] : 0,
            "stats"  => [
                "total"    => (int)($stats['total']    ?? 0),
                "active"   => (int)($stats['active']   ?? 0),
                "expiring" => (int)($stats['expiring'] ?? 0),
                "new_week" => (int)($stats['new_week'] ?? 0)
            ],
            "data" => $students
        ];
    }
}
