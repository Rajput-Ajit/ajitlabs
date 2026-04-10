<?php

// ============================================================
// AdminFeeService.php
// ============================================================
// WHAT CHANGED:
//   OLD: list() called feeModel methods with no adminId
//   NEW: list() extracts adminId from $user token and passes it
//        to every feeModel method for tenant isolation
//
//   OLD: getFeeStats() / getPayments() queried global data (all admins)
//   NEW: all queries scoped by admin_id via students.admin_id JOIN
// ============================================================

class AdminFeeService
{
    private $feeModel;

    public function __construct()
    {
        App::use('feeModel');
        $this->feeModel = new FeeModel();
    }

    public function list($params, $user)
    {
        // ✅ NEW: extract adminId for tenant isolation
        $adminId = $user['user_id'];

        return [
            // ✅ UPDATED: all methods now receive $adminId
            'stats'   => $this->feeModel->getFeeStats($adminId),
            'overdue' => $this->feeModel->getOverduePayments($adminId),
            'payments' => [
                'total'  => $this->feeModel->getPaymentsCount($params, $adminId),
                'data'   => $this->feeModel->getPayments($params, $adminId),
                'limit'  => (int)($params['limit']  ?? 10),
                'offset' => (int)($params['offset'] ?? 0),
            ]
        ];
    }
}
