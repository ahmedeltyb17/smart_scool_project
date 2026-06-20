<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizResultController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AssignmentSubmissionController;





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
        Route::put('/updateprofile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::get('/profile', [AuthController::class, 'getProfile']);

    });
});

// grades student and profile 
// Route::get('/student/profile', [StudentController::class, 'profile'])
//     ->middleware('auth.student');

//     Route::get('/student/grades', [StudentController::class, 'grades'])
//     ->middleware('auth.student');

// //attendance
//mark

// Route::prefix('attendance')->middleware('auth:sanctum')->group(function () {

//     Route::middleware('auth.teacher')->group(function () {
//         Route::post('mark', [AttendanceController::class, 'mark']);
//         Route::get('report', [AttendanceController::class, 'report']);
//         Route::get('class/{classId}/today', [AttendanceController::class, 'today']);
//         Route::put('{id}', [AttendanceController::class, 'update']);
    
//     });


// });

// Route::prefix('attendance')->middleware('auth:sanctum')->group(function () {

//     // للطالب (يشوف نفسه بس)
//     Route::get('my-history', [AttendanceController::class, 'studentHistory'])
//         ->middleware('auth.student');

//     // للمدرس (يشوف أي طالب)
//     Route::get('student/{studentId}', [AttendanceController::class, 'studentHistory'])
//         ->middleware('auth.teacher');

// });

// // parents show all students linked to them
// Route::middleware(['auth:sanctum', 'role:parent'])
//     ->prefix('parent')
//     ->group(function () {
//         Route::get('/profile', [ParentController::class, 'profile']);
//         Route::get('/children', [ParentController::class, 'myChildren']);

//         Route::get('/children/{id}', [ParentController::class, 'showChild']);

//         Route::get('/children/{studentId}/attendance', [ParentController::class, 'ChildAttendance']);

//         Route::get('/children/{studentId}/grades', [ParentController::class, 'ChildGrades']);

//         Route::post('/children/link', [ParentController::class, 'linkStudent']);

//         Route::delete('/children/{studentId}/unlink', [ParentController::class, 'unlinkStudent']);
//     });


    // grade show all grades for student and profile
    // Route::prefix('v1/grades')
    // ->middleware('auth:sanctum')
    // ->group(function () {

    //     // ───────── Teacher / Admin ─────────
    //     Route::middleware(['role:teacher,admin'])->group(function () {
    //         // all grades for student
    //         Route::get('/', [GradeController::class, 'index']);
    //         Route::post('/', [GradeController::class, 'store']);

    //         Route::put('/{id}', [GradeController::class, 'update']);
    //         Route::delete('/{id}', [GradeController::class, 'destroy']);

    //         Route::get('/student/{studentId}/summary',
    //             [GradeController::class, 'studentSummary']);
    //     });

    //     // ───────── Shared (Teacher/Admin/Student view single grade) ─────────
    //     Route::get('/{id}', [GradeController::class, 'show']);
    // });

    // // student show grades 
    // Route::middleware(['auth:sanctum', 'role:student'])
    // ->get('/v1/grades/my-grades', [GradeController::class, 'myGrades']);




    

    // // assignments
    //     Route::get('/assignments', [AssignmentController::class, 'index']);

    //     Route::post('/assignments', [AssignmentController::class, 'store']);

    //     Route::get('/assignments/{id}', [AssignmentController::class, 'show']);

    //     Route::put('/assignments/{id}', [AssignmentController::class, 'update']);

    //     Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);

    //     Route::patch('/assignments/{id}/publish', [AssignmentController::class, 'publish']);


        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////////////////  كل واحد لوحده 
    
    
        // student 
    Route::prefix('v1/student')->middleware('auth:sanctum')->group(function () {

        Route::get('/grades', [GradeController::class, 'myGrades']);

        // show materials for student   
        Route::get('/materials', [MaterialController::class, 'index']);    
    
    
    // Assignments
        Route::get('/assignments', [AssignmentController::class, 'index']);
        Route::get('/assignments/{id}', [AssignmentController::class, 'show']);

    // Submit assignment
        Route::post('/assignment-submissions', [AssignmentSubmissionController::class, 'submit']);

    // View own submissions + grades
        Route::get('/student/submissions', [AssignmentSubmissionController::class, 'studentSubmissions']);

        //attendance
         Route::get('/attendance/my-history', [AttendanceController::class, 'myHistory']);

    // Quizzes
        Route::get('/quizzes', [QuizController::class, 'studentQuizzes']);

    // Quiz results
        Route::get('/student/results', [QuizResultController::class, 'studentResults']);

    
        Route::get('/schedule', [ScheduleController::class, 'studentSchedule']);
});



#####################################################################
// teacher

Route::prefix('v1/teacher')->middleware('auth:sanctum')->group(function () {

// get profile 
Route::get('/get-profile', [AuthController::class, 'getProfile']);    


/*
    | Quiz Routes
    */
    
    Route::get('/show_quizzes', [QuizController::class, 'index']);
    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::get('/quizzes/{id}', [QuizController::class, 'show']);
    Route::delete('/quizzes/{id}', [QuizController::class, 'destroy']);

    Route::post('/quiz-results', [QuizResultController::class, 'store']);

    /*
    | Assignment Routes
    */// Assignments
    Route::post('/assignments', [AssignmentController::class, 'store']);
    Route::put('/assignments/{id}', [AssignmentController::class, 'update']);
    Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);
    Route::patch('/assignments/{id}/publish', [AssignmentController::class, 'publish']);

    // View assignments for teacher
    Route::get('/assignments', [AssignmentController::class, 'index']);

    // Submissions (grading system)
    Route::post('/assignments/submissions', [AssignmentSubmissionController::class, 'assignmentSubmissions']);
    Route::post('/assignment-submissions/grade', [AssignmentSubmissionController::class, 'grade']);

    // schedule for teacher
    Route::get('/schedules', [ScheduleController::class,'teacherSchedule']);

    // classes for teacher
    Route::get('/classes',[ClassController::class, 'teacherClasses']
    );
    // students in a class for teacher
    Route::get('/classes/{classId}/students',[ClassController::class, 'classStudents']
    );


    /*
    | Attendance Routes
    */
  Route::prefix('/attendance')->group(function () {

    Route::post('/mark', [AttendanceController::class, 'mark']);
    Route::get('/report', [AttendanceController::class, 'report']);
    Route::put('/update/{id}', [AttendanceController::class, 'update']);
    Route::get('/class/today/{classId}', [AttendanceController::class, 'today']);
    Route::get('/student/{studentId}', [AttendanceController::class, 'studentHistory']);

});



// materials
Route::post('/upload_materials', [MaterialController::class, 'store']);

});



//-----------------------------------------------------------------------
//-----------------------------------------------------------------------

// parent show all quizzes for their children

Route::prefix('v1/parent')->middleware('auth:sanctum')->group(function () {

// get profile
Route::get('/get-profile', [AuthController::class, 'getProfile']);
    // link children to parent
    Route::post('/link-student', [ParentController::class, 'linkStudent']);
    Route::post('/unlink-student', [ParentController::class, 'unlinkStudent']);
    Route::get('/children', [ParentController::class, 'children']);

    // Children results (quizzes + assignments)
    Route::get('/results', [ParentController::class, 'childResults']);

    // Assignments submissions (optional)
    Route::get('/parent/submissions', [AssignmentSubmissionController::class, 'parentSubmissions']);

    // Optional: student profile
    Route::get('/profile', function (Request $request) {
        return $request->user()->parent->students()->with('user', 'class')->get();
    });


    // attendance of all students linked to them
    Route::get('/children/attendance', [AttendanceController::class, 'childrenAttendance']);


    // schedules of all students linked to them
    Route::get('/schedules', [ScheduleController::class,'parentSchedules']);

    // quizzes of all students linked to them

    Route::get('/parent/children/quizzes', [ParentController::class, 'childQuizzes']);


});








Route::prefix('v1/admin')
->middleware('auth:sanctum')
->group(function () {

    Route::post('/schedules', [ScheduleController::class,'store']);

});