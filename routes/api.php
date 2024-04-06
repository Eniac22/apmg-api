<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\OfficerController;
use App\Http\Controllers\Api\AppointmentController;

Route::middleware(['jwtAuth', 'role:user'])->group(function () {    
    Route::post('/user/appointments',[AppointmentController::class, 'store']);
    Route::get('/user/appointments',[AppointmentController::class, 'index']);
});

Route::middleware(['jwtAuth', 'role:officer'])->get('/officer/dashboard', function (Request $request) {
    return response()->json(['message' => 'Officer Dashboard']);
});


Route::middleware(['jwtAuth', 'role:business'])->group(function () {
    Route::get('/business/departments/{department}/officers', [OfficerController::class, 'index']);
    Route::get('/business/departments/super',[DepartmentController::class, 'showAllSuperDepartments']);
    Route::post('/business/departments',[DepartmentController::class, 'store']);
    Route::post('/business/departments/{department}/officers',[OfficerController::class, 'create']);
    Route::put('/business/departments/{department}/officers/{officer}',[OfficerController::class, 'update']);
    Route::delete('/business/departments/{department}/officers/{officer}',[OfficerController::class, 'destroy']);
    Route::get('/business/departments/{department}/sub-departments',[DepartmentController::class, 'listSubDepartments']);
    Route::put('/business/departments/{id}',[DepartmentController::class, 'update']);
    Route::delete('/business/departments/{id}',[DepartmentController::class, 'destroy']);


});


Route::middleware(['jwtAuth', 'role:admin'])->get('/admin/dashboard', function (Request $request) {
    return response()->json(['message' => 'Admin Dashboard']);
});
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->middleware("jwtAuth");
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware("jwtAuth");
Route::get('/user-profile', [AuthController::class, 'getUser'])->middleware("jwtAuth");

