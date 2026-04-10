<?php

// ============================================================
// Response.php — JSON Response Sender
// ============================================================
// NO CHANGE from original.
// ============================================================

class Response
{
    public static function success(array $data = [], int $code = 200): void
    {
        http_response_code($code);
        echo json_encode(array_merge(['status' => 'success'], $data));
        exit;
    }

    public static function error(string $message, int $code = 400, array $extra = []): void
    {
        http_response_code($code);
        echo json_encode(array_merge(['status' => 'error', 'message' => $message], $extra));
        exit;
    }
}
