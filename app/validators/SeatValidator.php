<?php

// ============================================================
// SeatValidator.php
// ============================================================
// WHAT CHANGED:
//   OLD: shift() validator checked against hardcoded allowed values
//        from old ENUM('morning','evening','fullday') on fees table
//   NEW: shift is still validated as a non-empty string BUT the
//        allowed values are no longer hardcoded here — they are
//        checked dynamically in AdminSeatService by querying
//        the `shifts` table for the hall.
//
//   WHY: With the new `shifts` table, a hall can have custom shifts
//        (e.g. 'night', 'afternoon'). Hardcoding 3 values in the
//        validator would prevent assigning those custom shifts.
//        Service-layer validation (against DB) replaces enum-check.
//
//   ALSO CHANGED:
//        Old payment method validator: ['cash','upi']
//        New: ['cash','upi','card','bank_transfer','cheque','online']
//        Matches new fees.payment_method ENUM in schema.
// ============================================================

class SeatValidator
{
    protected static $v = null;

    protected static function validator()
    {
        if (self::$v === null) {
            self::$v = new Validator();
        }
        return self::$v;
    }

    // =========================================================
    // VALIDATE hall_id for seat list request
    // =========================================================
    // NO CHANGE
    // =========================================================
    public static function hallIdValidate($data)
    {
        $v = self::validator();

        $v->numeric('Hall ID', $data['hall_id'] ?? null)
          ->greaterThan('Hall ID', $data['hall_id'] ?? null, 0);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }

    // =========================================================
    // VALIDATE assign seat request
    // =========================================================
    // UPDATED: shift validation changed (see class comment above)
    // UPDATED: payment method list expanded
    // =========================================================
    public static function assignSeat($data)
    {
        $v = self::validator();

        $v->required('Seat ID', $data['seat_id'] ?? null)
          ->numeric('Seat ID', $data['seat_id'] ?? null)
          ->greaterThan('Seat ID', $data['seat_id'] ?? null, 0)

          ->required('Hall ID', $data['hall_id'] ?? null)
          ->numeric('Hall ID', $data['hall_id'] ?? null)
          ->greaterThan('Hall ID', $data['hall_id'] ?? null, 0)

          ->required('Student Name', $data['student_name'] ?? null)
          ->minLength('Student Name', $data['student_name'] ?? null, 2)
          ->maxLength('Student Name', $data['student_name'] ?? null, 60)

          ->required('Mobile Number', $data['mobile'] ?? null)
          ->numeric('Mobile Number', $data['mobile'] ?? null)
          ->exactLength('Mobile Number', $data['mobile'] ?? null, 10)

          // ✅ UPDATED: shift is required + non-empty string
          //    Actual value checked against shifts table in service layer
          ->required('Shift', $data['shift'] ?? null)
          ->minLength('Shift', $data['shift'] ?? null, 2)
          ->maxLength('Shift', $data['shift'] ?? null, 30)

          ->required('Duration (months)', $data['duration'] ?? null)
          ->numeric('Duration (months)', $data['duration'] ?? null)
          ->greaterThan('Duration (months)', $data['duration'] ?? null, 0)
          ->planDuration('Duration (months)', $data['duration'] ?? null, [1, 3, 6, 12])

          ->required('Start Date', $data['start_date'] ?? null)
          ->notOlderThanDays('Start Date', $data['start_date'] ?? null, 5)
          ->notFutureDate('Start Date', $data['start_date'] ?? null)

          ->required('Collected Fees', $data['collected_fees'] ?? null)
          ->numeric('Collected Fees', $data['collected_fees'] ?? null)
          ->greaterThan('Collected Fees', $data['collected_fees'] ?? null, 0)

          // ✅ UPDATED: payment method list expanded to match new schema ENUM
          ->required('Payment Method', $data['payment_method'] ?? null)
          ->paymentMethod('Payment Method', $data['payment_method'] ?? null, [
              'cash', 'upi', 'card', 'bank_transfer', 'cheque', 'online'
          ])

          ->minLength('Note', $data['note'] ?? '', 0)
          ->maxLength('Note', $data['note'] ?? '', 100);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }

    // release seat validation
    public static function releaseSeat($data){
        $v = self::validator();

        $v->required('Allocation ID', $data['allocation_id'] ?? null)
          ->numeric('Allocation ID', $data['allocation_id'] ?? null)
          ->greaterThan('Allocation ID', $data['allocation_id'] ?? null, 0)

          ->required('Student ID', $data['student_id'] ?? null)
          ->numeric('Student ID', $data['student_id'] ?? null)
          ->greaterThan('Student ID', $data['student_id'] ?? null, 0);
        
        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }
}
