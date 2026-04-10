<?php

// ============================================================
// App.php — Class Autoloader / Dependency Map
// ============================================================
// WHAT CHANGED from old App.php (.env file):
//
//   REMOVED keys:
//     'adminUserModel'  → replaced by 'adminModel'  (admins table)
//     'roleModel'       → REMOVED entirely (no roles table in v2)
//     'hallModel'       → renamed to 'adminHallModel' (consistent)
//     'hallService'     → renamed to 'adminHallService'
//
//   ADDED keys:
//     'adminModel'          → models/AdminModel.php
//     'studentModel'        → models/StudentModel.php
//     'adminDashboardModel' → models/AdminDashboardModel.php
//     'adminFeeService'     → services/AdminFeeService.php
//     'adminDashboardService' → services/AdminDashboardService.php
//     'adminStudentService' → services/AdminStudentService.php
//     'adminFeeController'  → controllers/AdminFeeController.php
//     'adminStudentController' → controllers/AdminStudentController.php
//
//   COMPAT aliases kept:
//     'userModel'      → StudentModel (old services used 'userModel')
//     'dashboardModel' → AdminDashboardModel (old service used this key)
// ============================================================

class App
{
    private static $map = [
        // --- config ---
        'db' => 'config/config.php',

        // --- core ---
        'runMiddlware'        => 'core/Middleware.php',
        'request'             => 'core/Request.php',
        'response'            => 'core/Response.php',

        // --- middlewares ---
        'rateLimitMiddleware' => 'middlewares/RateLimitMiddleware.php',
        'authMiddleware'      => 'middlewares/AuthMiddleware.php',
        'roleMiddleware'      => 'middlewares/RoleMiddleware.php',

        // --- models ---
        'rateLimitModel'      => 'models/RateLimitModel.php',

        // ✅ UPDATED: was 'adminUserModel' → users table
        //    Now 'adminModel' → admins table
        'adminModel'          => 'models/AdminModel.php',
        'adminUserModel'      => 'models/AdminModel.php',   // compat alias

        // ✅ NEW: students now have their own table + model
        'studentModel'        => 'models/StudentModel.php',
        'userModel'           => 'models/StudentModel.php', // compat alias

        'otpModel'            => 'models/OtpModel.php',
        'adminHallModel'      => 'models/AdminHallModel.php',
        'hallModel'           => 'models/AdminHallModel.php', // compat alias
        'seatModel'           => 'models/SeatModel.php',
        'feeModel'            => 'models/FeeModel.php',

        // ✅ UPDATED: was 'dashboardModel' inconsistently
        'adminDashboardModel' => 'models/AdminDashboardModel.php',
        'dashboardModel'      => 'models/AdminDashboardModel.php', // compat alias

        // ✅ REMOVED: 'roleModel' — roles table is gone
        //    Role = which table the user row lives in

        // --- controllers ---
        'otpController'            => 'controllers/OtpController.php',
        'adminAuthController'      => 'controllers/AdminAuthController.php',
        'adminHallController'      => 'controllers/AdminHallController.php',
        'adminSeatController'      => 'controllers/AdminSeatController.php',
        'adminStudentController'   => 'controllers/AdminStudentController.php',
        'adminFeeController'       => 'controllers/AdminFeeController.php',
        'adminDashboardController' => 'controllers/AdminDashboardController.php',

        // --- services ---
        'otpService'            => 'services/OtpService.php',
        'adminAuthService'      => 'services/AdminAuthService.php',
        'adminHallService'      => 'services/AdminHallService.php',
        'hallService'           => 'services/AdminHallService.php', // compat alias
        'adminSeatService'      => 'services/AdminSeatService.php',
        'adminStudentService'   => 'services/AdminStudentService.php',
        'adminFeeService'       => 'services/AdminFeeService.php',
        'adminDashboardService' => 'services/AdminDashboardService.php',

        // --- validators ---
        'userValidator' => 'validators/UserValidator.php',
        'hallValidator' => 'validators/HallValidator.php',
        'seatValidator' => 'validators/SeatValidator.php',

        // --- helpers ---
        'validator' => 'helpers/Validator.php',
        'token'     => 'helpers/Token.php',
    ];

    public static function use(string $key): void
    {
        if (!isset(self::$map[$key])) {
            die(json_encode([
                'status'  => 'error',
                'message' => "App::use() — unknown key: '$key'"
            ]));
        }
        require_once __DIR__ . '/../' . self::$map[$key];
    }

    public static function useMany(array $keys): void
    {
        foreach ($keys as $key) {
            self::use($key);
        }
    }
}
