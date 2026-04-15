<?php

require_once '../config/bootstrap.php';

App::useMany([
    'authMiddleware',
    'roleMiddleware',
    'hallValidator',
    'adminHallController'
]);

Middleware::run([
    AuthMiddleware::class,
    [RoleMiddleware::class, 'admin'],
]);

$input = Request::json();

// Required core fields
Request::InputRequirements([
    'hall_id',
    'branch_id'
], $input);

// Validate hall fields + at least one shift timing
HallValidator::validateDeleteHall($input);

(new AdminHallController())->delete($input);

?>