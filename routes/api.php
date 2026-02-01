<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SimulationVideoController;
use App\Http\Controllers\Api\VideoController;


Route::get('/ping', function () {
  return response()->json([
    'status' => 'ok',
    'message' => 'API v1 jalan'
  ]);
});

Route::post('/login', [AuthController::class, 'login']);

// Public video routes
Route::get('/videos', [VideoController::class, 'index']);
Route::get('/videos/{id}', [VideoController::class, 'show']);
Route::post('/videos/{id}/view', [VideoController::class, 'incrementView']);

Route::middleware('auth:sanctum')->group(function () {
  Route::post('/completed-profile', [AuthController::class, 'completedprofile']);
  Route::get('/profile', function (Request $request) {
    return $request->user();
  });
  
  // Protected video routes
  Route::post('/videos', [VideoController::class, 'store']);
  Route::put('/videos/{id}', [VideoController::class, 'update']);
  Route::delete('/videos/{id}', [VideoController::class, 'destroy']);
  
  // Test endpoint untuk debug upload foto
  Route::post('/test-photo', function (Request $request) {
    $debug = [
      'has_file' => $request->hasFile('photo'),
      'file_info' => null,
    ];
    
    if ($request->hasFile('photo')) {
      $file = $request->file('photo');
      $debug['file_info'] = [
        'is_valid' => $file->isValid(),
        'original_name' => $file->getClientOriginalName(),
        'mime_type' => $file->getMimeType(),
        'size' => $file->getSize(),
        'extension' => $file->getClientOriginalExtension(),
      ];
      
      if ($file->isValid()) {
        $path = $file->store('photos', 'public');
        $debug['stored_path'] = $path;
        $debug['full_url'] = url('storage/' . $path);
      }
    }
    
    return response()->json($debug);
  });
});


