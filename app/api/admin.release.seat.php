<?php

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'adminSeatController',
    'seatValidator',
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

$input = Request::json();

// Validate all required fields present
Request::InputRequirements([
    'allocation_id',
    'student_id'
], $input);

// Validate field formats
SeatValidator::releaseSeat($input);

(new AdminSeatController())->release($input);
?>