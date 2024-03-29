<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware(['jwtAuth', 'role:user'])->get('/user/profile', function (Request $request) {
    return response()->json(['message' => 'User Profile']);
});

Route::middleware(['jwtAuth', 'role:officer'])->get('/officer/dashboard', function (Request $request) {
    return response()->json(['message' => 'Officer Dashboard']);
});

Route::middleware(['jwtAuth', 'role:business'])->get('/business/dashboard', function (Request $request) {
    return response()->json(['message' => 'Business Dashboard']);
});

Route::middleware(['jwtAuth', 'role:admin'])->get('/admin/dashboard', function (Request $request) {
    return response()->json(['message' => 'Admin Dashboard']);
});
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->middleware("jwtAuth");
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware("jwtAuth");
Route::get('/user-profile', [AuthController::class, 'getUser'])->middleware("jwtAuth");

