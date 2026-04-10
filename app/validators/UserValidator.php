<?php

// ============================================================
// UserValidator.php
// ============================================================
// NO CHANGE — validation rules for admin registration and OTP
// are unaffected by the DB schema change.
// ============================================================

class UserValidator
{
    protected static $v = null;

    protected static function validator()
    {
        if (self::$v === null) {
            self::$v = new Validator();
        }
        return self::$v;
    }

    public static function validateRegister($data)
    {
        $v = self::validator();

        $v->required('First Name', $data['first_name'] ?? null)
          ->minLength('First Name', $data['first_name'] ?? null, 3)
          ->maxLength('First Name', $data['first_name'] ?? null, 15)

          ->required('Last Name', $data['last_name'] ?? null)
          ->minLength('Last Name', $data['last_name'] ?? null, 3)
          ->maxLength('Last Name', $data['last_name'] ?? null, 15)

          ->required('Reading Hall Name', $data['reading_name'] ?? null)
          ->minLength('Reading Hall Name', $data['reading_name'] ?? null, 3)
          ->maxLength('Reading Hall Name', $data['reading_name'] ?? null, 60)

          ->required('Email', $data['email'] ?? null)
          ->email('Email', $data['email'] ?? null)

          ->required('Mobile Number', $data['mobile'] ?? null)
          ->numeric('Mobile Number', $data['mobile'] ?? null)
          ->exactLength('Mobile Number', $data['mobile'] ?? null, 10)

          ->required('City', $data['city'] ?? null)
          ->minLength('City', $data['city'] ?? null, 3)
          ->maxLength('City', $data['city'] ?? null, 30)

          ->required('Password', $data['password'] ?? null)
          ->minLength('Password', $data['password'] ?? null, 6);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }

    public static function validateEmail($data)
    {
        $v = self::validator();

        $v->required('Email', $data['email'] ?? null)
          ->email('Email', $data['email'] ?? null);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }

    public static function validateVerifyEmail($data)
    {
        $v = self::validator();

        $v->required('Email', $data['email'] ?? null)
          ->email('Email', $data['email'] ?? null)
          ->required('OTP', $data['otp'] ?? null)
          ->numeric('OTP', $data['otp'] ?? null)
          ->exactLength('OTP', $data['otp'] ?? null, 6);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }

    public static function validateMobile($data)
    {
        $v = self::validator();

        $v->required('Mobile Number', $data['mobile'] ?? null)
          ->numeric('Mobile Number', $data['mobile'] ?? null)
          ->exactLength('Mobile Number', $data['mobile'] ?? null, 10);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }

    public static function validateVerifyMobile($data)
    {
        $v = self::validator();

        $v->required('Mobile Number', $data['mobile'] ?? null)
          ->numeric('Mobile Number', $data['mobile'] ?? null)
          ->exactLength('Mobile Number', $data['mobile'] ?? null, 10)
          ->required('OTP', $data['otp'] ?? null)
          ->numeric('OTP', $data['otp'] ?? null)
          ->exactLength('OTP', $data['otp'] ?? null, 6);

        if ($v->hasErrors()) {
            Response::error($v->firstError(), 422);
        }

        return true;
    }
}
