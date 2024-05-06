<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\OfficerController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\LeaveController;

Route::middleware(['jwtAuth', 'role:user'])->group(function () {    
    Route::post('/user/appointments',[AppointmentController::class, 'store']);
    Route::get('/user/appointments',[AppointmentController::class, 'index']);
    Route::get('/user/all-business',[BusinessController::class, 'getAllBusiness']);
    Route::get('/user/all-departments',[DepartmentController::class, 'getAllDepartments']);
    Route::get('/user/all-officers',[OfficerController::class, 'getAllOfficers']);
    Route::get('/user/officers/{depId}',[OfficerController::class, 'getOfficers']);
    Route::get('/user/appointments/{id}/edit', [AppointmentController::class, 'editAppointment']);
    Route::put('/user/appointments/{id}', [AppointmentController::class, 'updateAppointment']);
    Route::delete('/user/appointments/{id}', [AppointmentController::class, 'deleteAppointment']);
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/get-selected/{id}/{type}', [SearchController::class, 'getSelected']);
    Route::get('/get-business-officer-leaves/{business_id}', [LeaveController::class, 'getBusinessOfficerLeavesForUser']);
});

Route::middleware(['jwtAuth', 'role:officer'])->group(function () {    
    Route::get('/officer/departments',[OfficerController::class, 'showAssignedDepartments']);
    Route::get('/officer/department/{departmentId}',[OfficerController::class, 'getSpecificDepartment']);
    Route::get('/officer/department/{departmentId}/appointments', [OfficerController::class, 'getAllAppointments']);
    Route::put('/officer/department/{departmentId}/token', [OfficerController::class, 'updateToken']);
    Route::get('/officer/search', [SearchController::class, 'businessDepartmentSearch']);
    Route::get('/officer/get-selected/{id}/{type}', [SearchController::class, 'getSelected']);
    Route::get('/officer/leaves',[LeaveController::class, 'getOfficerLeaves']);
    Route::post('/officer/leaves',[LeaveController::class, 'addOfficerLeave']);
    Route::delete('/officer/leaves/{id}',[LeaveController::class, 'deleteLeave']);
});


Route::middleware(['jwtAuth', 'role:business'])->group(function () {
    Route::get('/business/departments/{department}/officers', [OfficerController::class, 'index']);
    Route::get('/business/departments/super',[DepartmentController::class, 'showAllSuperDepartments']);
    Route::post('/business/departments',[DepartmentController::class, 'store']);
    Route::post('/business/departments/{department}/officers',[OfficerController::class, 'create']);
    Route::put('/business/departments/{department}/officers/{officer}',[OfficerController::class, 'update']);
    Route::delete('/business/departments/{department}/officers/{officer}',[OfficerController::class, 'destroy']);
    Route::get('/business/departments/{department}/officers/{officer}',[OfficerController::class, 'officerAppointments']);
    Route::get('/business/departments/{department}/sub-departments',[DepartmentController::class, 'listSubDepartments']);
    Route::put('/business/departments/{id}',[DepartmentController::class, 'update']);
    Route::delete('/business/departments/{id}',[DepartmentController::class, 'destroy']);
    Route::get('/business/departments/{department}/officers/{officer}',[DepartmentController::class, 'destroy']);
    Route::post('/business/leaves',[LeaveController::class, 'addBusinessLeave']);
    Route::get('/business/leaves',[LeaveController::class, 'getBusinessLeaves']);
    Route::get('/business/officer-leaves',[LeaveController::class, 'getBusinessOfficerLeaves']);
    Route::delete('/business/leaves/{id}',[LeaveController::class, 'deleteLeave']);
    Route::post('/business/link-officer', [OfficerController::class, 'linkOfficer']);
    

});


Route::middleware(['jwtAuth', 'role:admin'])->get('/admin/dashboard', function (Request $request) {
    return response()->json(['message' => 'Admin Dashboard']);
});
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->middleware("jwtAuth");
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware("jwtAuth");
Route::get('/user-profile', [AuthController::class, 'getUser'])->middleware("jwtAuth");

