<?php

// ============================================================
// Middleware.php — Core Middleware Runner
// ============================================================
// NO CHANGE from original.
// Supports both parameterized [ClassName, param1, param2]
// and plain ClassName middleware entries.
// ============================================================

class Middleware
{
    public static function run($middlewares = [])
    {
        foreach ($middlewares as $m) {

            if (is_array($m)) {
                $class  = $m[0];
                $params = array_slice($m, 1);

                $reflection = new ReflectionClass($class);
                $instance   = $reflection->newInstanceArgs($params);
                $instance->handle();

            } else {
                (new $m())->handle();
            }
        }
    }
}
