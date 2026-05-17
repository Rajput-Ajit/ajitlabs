<?php

// ============================================================
// AdminDashboardService.php
// ============================================================
// WHAT CHANGED:
//   OLD: all model methods had no admin scope — counted data globally
//   NEW: adminId passed to every model method for tenant isolation
//
//   OLD: stats.occupied_seats came from seats.status='occupied' column
//   NEW: stats.occupied_seats comes from seat_allocations count
//
//   OLD: model was loaded as 'dashboardModel' key
//   NEW: loaded as 'adminDashboardModel' key (consistent naming)
//        'dashboardModel' alias still works via App map
// ============================================================

class AdminDashboardService
{
    private $model;
    private $feeModel;

    public function __construct()
    {
        // ✅ UPDATED: key name 'adminDashboardModel' (was 'dashboardModel')
        App::useMany(['adminDashboardModel', 'feeModel']);
        $this->model = new DashboardModel();
        $this->feeModel = new FeeModel();
    }

    public function data($user)
    {
        // ✅ NEW: extract adminId for scoping all queries
        $adminId = $user['user_id'];

        // ✅ UPDATED: all model methods now receive $adminId
        $stats    = $this->model->getStats($adminId);
        $revenue  = $this->model->getRevenueChart($adminId);
        $seats    = $this->model->getSeatStats($adminId);
        $activity = $this->model->getRecentActivity($adminId);
        $alerts   = $this->feeModel->getOverduePayments($adminId);

        return [
            'stats' => [
                'total_halls'      => (int)($stats['total_halls']      ?? 0),
                'occupied_seats'   => (int)($stats['occupied_seats']   ?? 0),
                'total_seats'      => (int)($stats['total_seats']      ?? 0),
                'total_students'   => (int)($stats['total_students']   ?? 0),
                'monthly_revenue'  => (float)($stats['monthly_revenue'] ?? 0),
            ],
            'charts' => [
                'revenue' => $revenue,
                'seats'   => $seats,
            ],
            'activity'   => $activity,
            'fee_alerts' => $alerts,
        ];
    }
}
