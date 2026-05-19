<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\AssignmentController;





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
        Route::get('/profile', [AuthController::class, 'profile']);

    });
});

// grades student and profile 
// Route::get('/student/profile', [StudentController::class, 'profile'])
//     ->middleware('auth.student');

//     Route::get('/student/grades', [StudentController::class, 'grades'])
//     ->middleware('auth.student');

// //attendance
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
        Route::get('/profile', [ParentController::class, 'profile']);
        Route::get('/children', [ParentController::class, 'myChildren']);

        Route::get('/children/{id}', [ParentController::class, 'showChild']);

        Route::get('/children/{studentId}/attendance', [ParentController::class, 'ChildAttendance']);

        Route::get('/children/{studentId}/grades', [ParentController::class, 'ChildGrades']);

        Route::post('/children/link', [ParentController::class, 'linkStudent']);

        Route::delete('/children/{studentId}/unlink', [ParentController::class, 'unlinkStudent']);
    });


    // grade show all grades for student and profile
    Route::prefix('v1/grades')
    ->middleware('auth:sanctum')
    ->group(function () {

        // ───────── Teacher / Admin ─────────
        Route::middleware(['role:teacher,admin'])->group(function () {
            // all grades for student
            Route::get('/', [GradeController::class, 'index']);
            Route::post('/', [GradeController::class, 'store']);

            Route::put('/{id}', [GradeController::class, 'update']);
            Route::delete('/{id}', [GradeController::class, 'destroy']);

            Route::get('/student/{studentId}/summary',
                [GradeController::class, 'studentSummary']);
        });

        // ───────── Shared (Teacher/Admin/Student view single grade) ─────────
        Route::get('/{id}', [GradeController::class, 'show']);
    });

    // student show grades 
    Route::middleware(['auth:sanctum', 'role:student'])
    ->get('/v1/grades/my-grades', [GradeController::class, 'myGrades']);




    

    // assignments
        Route::get('/assignments', [AssignmentController::class, 'index']);

        Route::post('/assignments', [AssignmentController::class, 'store']);

        Route::get('/assignments/{id}', [AssignmentController::class, 'show']);

        Route::put('/assignments/{id}', [AssignmentController::class, 'update']);

        Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);

        Route::patch('/assignments/{id}/publish', [AssignmentController::class, 'publish']);