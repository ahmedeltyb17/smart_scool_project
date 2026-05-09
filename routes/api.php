<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\ParentController;





// register
//login
//logout
//update
//delete


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1/auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/updateprofile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });
});


//attendance
//mark

Route::prefix('attendance')->middleware('auth:sanctum')->group(function () {

    Route::middleware('auth.teacher')->group(function () {
        Route::post('mark', [AttendanceController::class, 'mark']);
        Route::get('report', [AttendanceController::class, 'report']);
        Route::get('class/{classId}/today', [AttendanceController::class, 'today']);
        Route::put('{id}', [AttendanceController::class, 'update']);
    
    });


});

Route::prefix('attendance')->middleware('auth:sanctum')->group(function () {

    // للطالب (يشوف نفسه بس)
    Route::get('my-history', [AttendanceController::class, 'studentHistory'])
        ->middleware('auth.student');

    // للمدرس (يشوف أي طالب)
    Route::get('student/{studentId}', [AttendanceController::class, 'studentHistory'])
        ->middleware('auth.teacher');

});

// parents show all students linked to them
Route::middleware(['auth:sanctum', 'role:parent'])
    ->prefix('parent')
    ->group(function () {

        Route::get('/children', [ParentController::class, 'myChildren']);
        Route::get('/children/{id}/attendance', [ParentController::class, 'myChildAttendance']);
        Route::get('/children/{id}/grades', [ParentController::class, 'myChildGrades']);
    });