<?php

    // ============================================================
    // refresh-token.php
    // ============================================================
    // PURPOSE:
    //   Creates NEW access token using refresh token cookie
    //
    // FLOW:
    //   1. Get refresh token from cookie
    //   2. Verify refresh token
    //   3. Check admin still exists
    //   4. Check blocked/deleted
    //   5. Create new access token
    //   6. Return access token
    // ============================================================

    require_once '../config/bootstrap.php';

    App::useMany([
        'token',
        'db'
    ]);

    // ============================================================
    // GET REFRESH TOKEN FROM COOKIE
    // ============================================================
    $refreshToken = Token::refreshToken();

    // ============================================================
    // VERIFY REFRESH TOKEN
    // ============================================================
    $user = Token::verifyRefreshToken(
        $refreshToken
    );

    // ============================================================
    // EXTRACT USER DATA
    // ============================================================
    $userId = $user->user_id ?? null;
    $type   = $user->type ?? null;

    // ============================================================
    // VALIDATE TOKEN PAYLOAD
    // ============================================================
    if (!$userId || $type !== 'admin') {

        Response::error(
            'Invalid refresh token',
            401
        );
    }

    // ============================================================
    // LIVE DB CHECK
    // ============================================================
    $conn = DB::connect();

    $stmt = $conn->prepare("
        SELECT
            id,
            email,
            status,
            deleted_at,
            is_read_only
        FROM admins
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $admin = $stmt
        ->get_result()
        ->fetch_assoc();

    // ============================================================
    // ACCOUNT NOT FOUND
    // ============================================================
    if (
        !$admin ||
        $admin['deleted_at'] !== null
    ) {

        Response::error(
            'Account not found',
            401
        );
    }

    // ============================================================
    // BLOCKED / SUSPENDED
    // ============================================================
    if (
        $admin['status'] === 'blocked' ||
        $admin['status'] === 'suspended'
    ) {

        Response::error(
            "Account is {$admin['status']}",
            403
        );
    }

    // ============================================================
    // CREATE NEW ACCESS TOKEN
    // ============================================================
    $newAccessToken =
        Token::createAccessToken(
            $admin['id'],
            'admin',
            $admin['email']
        );

    // ============================================================
    // RESPONSE
    // ============================================================
    Response::success([

        'message' => 'Access token refreshed',

        'token' => $newAccessToken,

        'is_read_only' =>
            (bool)$admin['is_read_only']
    ]);
?>