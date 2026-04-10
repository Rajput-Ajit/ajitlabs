<?php

// ============================================================
// Validator.php — Fluent Validation Helper
// ============================================================
// WHAT CHANGED:
//   OLD: shift() method validated against hardcoded
//        ['morning','evening','fullday'] enum values
//   NEW: shift() method REMOVED from this class.
//        Reason: shifts are now dynamic rows in the `shifts` table
//        per hall. Valid shift codes are hall-specific and cannot
//        be hardcoded here. Service layer validates against DB.
//        SeatValidator no longer calls ->shift() on this class.
//
//   ALL OTHER METHODS: identical to original.
// ============================================================

class Validator
{
    private array $errors = [];

    public function required(string $field, $value): self
    {
        if (trim((string)$value) === '') {
            $this->errors[] = "$field is required";
        }
        return $this;
    }

    public function email(string $field, $value): self
    {
        if (trim((string)$value) === '') return $this;
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid $field format";
        }
        return $this;
    }

    public function minLength(string $field, $value, int $length): self
    {
        if (trim((string)$value) === '') return $this;
        if (mb_strlen((string)$value) < $length) {
            $this->errors[] = "$field must be at least $length characters";
        }
        return $this;
    }

    public function maxLength(string $field, $value, int $length): self
    {
        if (trim((string)$value) === '') return $this;
        if (mb_strlen((string)$value) > $length) {
            $this->errors[] = "$field must not exceed $length characters";
        }
        return $this;
    }

    public function numeric(string $field, $value): self
    {
        if (trim((string)$value) === '') return $this;
        if (!ctype_digit((string)$value)) {
            $this->errors[] = "$field must be numeric";
        }
        return $this;
    }

    public function exactLength(string $field, $value, int $length): self
    {
        if (trim((string)$value) === '') return $this;
        if (strlen((string)$value) !== $length) {
            $this->errors[] = "$field must be exactly $length digits";
        }
        return $this;
    }

    public function greaterThan(string $field, $value, int $min = 0): self
    {
        if (trim((string)$value) === '') return $this;
        if (!is_numeric($value) || (float)$value <= $min) {
            $this->errors[] = "$field must be greater than $min";
        }
        return $this;
    }

    public function lessThan(string $field, $value, int $max): self
    {
        if (trim((string)$value) === '') return $this;
        if (!is_numeric($value) || (float)$value >= $max) {
            $this->errors[] = "$field must be less than $max";
        }
        return $this;
    }

    public function between(string $field, $value, int $min, int $max): self
    {
        if (trim((string)$value) === '') return $this;
        if (!is_numeric($value) || $value < $min || $value > $max) {
            $this->errors[] = "$field must be between $min and $max";
        }
        return $this;
    }

    public function validDate(string $field, $value): self
    {
        if (trim((string)$value) === '') return $this;
        if (!strtotime($value)) {
            $this->errors[] = "$field must be a valid date";
        }
        return $this;
    }

    public function notOlderThanDays(string $field, $value, int $days): self
    {
        if (trim((string)$value) === '') return $this;
        if (strtotime($value) < strtotime("-$days days")) {
            $this->errors[] = "$field cannot be older than $days days";
        }
        return $this;
    }

    public function notFutureDate(string $field, $value): self
    {
        if (trim((string)$value) === '') return $this;
        if (strtotime($value) > strtotime(date('Y-m-d'))) {
            $this->errors[] = "$field cannot be a future date";
        }
        return $this;
    }

    public function planDuration(string $field, $value, array $allowed = [1, 3, 6, 12]): self
    {
        if (trim((string)$value) === '') return $this;
        if (!in_array((int)$value, $allowed, true)) {
            $this->errors[] = "$field must be one of: " . implode(', ', $allowed) . " months";
        }
        return $this;
    }

    public function paymentMethod(string $field, $value, array $allowed = ['cash', 'upi']): self
    {
        if (trim((string)$value) === '') return $this;
        if (!in_array($value, $allowed, true)) {
            $this->errors[] = "$field must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    // ✅ KEPT for any callers that still use it directly
    // But SeatValidator no longer calls this — shift is validated against DB
    public function shift(string $field, $value, array $allowed = ['morning', 'evening', 'fullday']): self
    {
        if (trim((string)$value) === '') return $this;
        if (!in_array($value, $allowed, true)) {
            $this->errors[] = "$field must be a valid shift code";
        }
        return $this;
    }

    public function validTimeFlexible(string $field, $value): self
    {
        if (trim((string)$value) === '') return $this;
        $formats = ['h:i A', 'H:i', 'H:i:s'];
        $valid   = false;
        foreach ($formats as $fmt) {
            if (DateTime::createFromFormat($fmt, $value) instanceof DateTime) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $this->errors[] = "$field must be a valid time (e.g. 06:00 or 10:30 AM)";
        }
        return $this;
    }

    public function endTimeAfterStartFlexible(
        string $startField, $startTime,
        string $endField,   $endTime
    ): self {
        if (trim((string)$startTime) === '' || trim((string)$endTime) === '') return $this;
        $start = $this->parseTime($startTime);
        $end   = $this->parseTime($endTime);
        if (!$start || !$end) {
            $this->errors[] = "Invalid time format for $startField or $endField";
            return $this;
        }
        if ($end <= $start) {
            $this->errors[] = "$endField must be after $startField";
        }
        return $this;
    }

    public function endDateAfterStart(string $startField, $startDate, string $endField, $endDate): self
    {
        if (trim((string)$startDate) === '' || trim((string)$endDate) === '') return $this;
        if (strtotime($endDate) <= strtotime($startDate)) {
            $this->errors[] = "$endField must be after $startField";
        }
        return $this;
    }

    public function minNumber(string $field, $value, int $min): self
    {
        if (trim((string)$value) === '') return $this;
        if ((int)$value < $min) {
            $this->errors[] = "$field must be at least $min";
        }
        return $this;
    }

    public function maxNumber(string $field, $value, int $max): self
    {
        if (trim((string)$value) === '') return $this;
        if ((int)$value > $max) {
            $this->errors[] = "$field must be at most $max";
        }
        return $this;
    }

    public function hasErrors(): bool  { return !empty($this->errors); }
    public function getErrors(): array { return $this->errors; }
    public function firstError(): ?string { return $this->errors[0] ?? null; }

    private function parseTime($time): ?DateTime
    {
        foreach (['h:i A', 'H:i', 'H:i:s'] as $fmt) {
            $d = DateTime::createFromFormat($fmt, $time);
            if ($d instanceof DateTime) return $d;
        }
        return null;
    }
}
