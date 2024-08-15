<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/hello', function () {
  return response()->json(['message' => 'Hello World!']);
});

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/user', function (Request $request) {
    return $request->user();
  });
  Route::post('/verify', [VerificationController::class, 'verify']);
});
