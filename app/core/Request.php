<?php

// ============================================================
// Request.php — HTTP Request Reader
// ============================================================
// NO CHANGE from original.
// ============================================================

class Request
{
    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function validate(array $fields, array $input): bool
    {
        foreach ($fields as $field) {
            if (!isset($input[$field])) {
                return false;
            }
        }
        return true;
    }

    public static function InputRequirements(array $fields, array $input)
    {
        foreach ($fields as $field) {
            if (!isset($input[$field])) {
                Response::error("Missing required field: $field", 400);
            }
        }
        return true;
    }
}
