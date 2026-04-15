<?php

// ============================================================
// HallValidator.php
// ============================================================
// WHAT CHANGED:
//   OLD: validated fixed 9 time fields:
//        morning_start, morning_end, evening_start, evening_end,
//        full_day_start, full_day_end + 3 fee fields
//        This was hardcoded for exactly 3 shifts.
//
//   NEW: still accepts the same flat input for backward compatibility
//        (morning_start/end, evening_start/end, full_day_start/end)
//        BUT each shift group is only validated if its data is present.
//        This allows future support for custom shift names/counts.
//
//   ALSO CHANGED:
//        Old: all 3 shifts were REQUIRED
//        New: at least 1 complete shift (start+end+fee) is required.
//             Frontend always sends all 3 — this is a future-proofing change.
//
//   count_start (seat_start_number) validation: unchanged
// ============================================================

class HallValidator
{
    protected static $v = null;

    protected static function validator()
    {
        if (self::$v === null) {
            self::$v = new Validator();
        }
        return self::$v;
    }

    public static function validateCreatHall($data)
    {
        $v = self::validator();

        // --- Core hall fields (unchanged) ---
        $v->required('Hall Name', $data['hall_name'] ?? null)
          ->minLength('Hall Name', $data['hall_name'] ?? null, 3)
          ->maxLength('Hall Name', $data['hall_name'] ?? null, 100)

          ->required('Branch ID', $data['branch_id'] ?? null)
          ->numeric('Branch ID', $data['branch_id'] ?? null)
          ->greaterThan('Branch ID', $data['branch_id'] ?? null, 0)

          ->required('Total Seats', $data['total_seats'] ?? null)
          ->numeric('Total Seats', $data['total_seats'] ?? null)
          ->greaterThan('Total Seats', $data['total_seats'] ?? null, 0)
          ->lessThan('Total Seats', $data['total_seats'] ?? null, 1001)

          ->numeric('Count Start', $data['count_start'] ?? null)
          ->greaterThan('Count Start', $data['count_start'] ?? 1, 0);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        // --- Shift validation (at least 1 shift must be complete) ---
        // ✅ UPDATED: each shift validated only if provided (not all 3 hardcoded required)
        $shiftsProvided = 0;

        $shiftGroups = [
            'morning'  => ['morning_start',   'morning_end',   'morning_fees'],
            'evening'  => ['evening_start',   'evening_end',   'evening_fees'],
            'fullday'  => ['full_day_start',  'full_day_end',  'full_day_fees'],
        ];

        foreach ($shiftGroups as $label => $fields) {
            [$startKey, $endKey, $feeKey] = $fields;

            // skip entirely if none of the 3 fields are sent
            if (empty($data[$startKey]) && empty($data[$endKey]) && empty($data[$feeKey])) {
                continue;
            }

            $shiftsProvided++;
            $labelName = ucfirst($label);

            $v->required("$labelName Start Time", $data[$startKey] ?? null)
              ->validTimeFlexible("$labelName Start Time", $data[$startKey] ?? null)
              ->required("$labelName End Time", $data[$endKey] ?? null)
              ->validTimeFlexible("$labelName End Time", $data[$endKey] ?? null)
              ->endTimeAfterStartFlexible(
                    "$labelName Start Time", $data[$startKey] ?? null,
                    "$labelName End Time",   $data[$endKey]   ?? null
               )
              ->required("$labelName Fees", $data[$feeKey] ?? null)
              ->numeric("$labelName Fees", $data[$feeKey] ?? null)
              ->greaterThan("$labelName Fees", $data[$feeKey] ?? null, 0);

            if ($v->hasErrors()) {
                Response::error($v->firstError(), 422);
            }
        }

        // ✅ UPDATED: at least one complete shift required
        if ($shiftsProvided === 0) {
            Response::error("At least one shift (morning, evening, or full day) must be configured", 422);
        }

        return true;
    }

    public static function validateDeleteHall($data){
        $v = self::validator();

        // --- Core hall fields (unchanged) ---
        $v->required('Hall Id', $data['hall_id'] ?? null)
          ->numeric('Hall Id', $data['hall_id'] ?? null)
          ->greaterThan('Hall Id', $data['hall_id'] ?? null, 0)
          
          ->required('Branch ID', $data['branch_id'] ?? null)
          ->numeric('Branch ID', $data['branch_id'] ?? null)
          ->greaterThan('Branch ID', $data['branch_id'] ?? null, 0);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }
    }
}
