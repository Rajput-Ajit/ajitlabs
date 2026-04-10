<?php
// ============================================================
// AdminAuthController.php
// ============================================================
// NO CHANGE in controller logic — thin pass-through layer
// ============================================================

class AdminAuthController
{
    private $service;

    public function __construct()
    {
        App::use('adminAuthService');
        $this->service = new AdminAuthService();
    }

    public function register($data)
    {
        $result = $this->service->register($data);
        Response::success($result);
    }

    public function login($data)
    {
        $result = $this->service->login($data);
        Response::success($result);
    }
}
