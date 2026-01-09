<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SimulationVideoController;


Route::get('/ping', function () {
  return response()->json([
    'status' => 'ok',
    'message' => 'API v1 jalan'
  ]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
  Route::post('/completed-profile', [AuthController::class, 'completedprofile']);
  Route::get('/profile', function (Request $request) {
    return $request->user();
  });
});


