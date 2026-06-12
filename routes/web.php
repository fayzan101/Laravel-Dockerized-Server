<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/api/documentation'));

Route::get('/reset-password/{token}', function (string $token) {
    return response()->json([
        'message' => 'Password reset token received.',
        'token' => $token,
        'email' => request()->query('email'),
    ]);
})->name('password.reset');
