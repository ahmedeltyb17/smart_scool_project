<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
    'teacher_id' => 'required|exists:teachers,id',
    'class_id' => 'required|exists:classes,id',
    'subject' => 'required|string',
    'day' => 'required|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Saturday',
    'start_time' => 'required',
    'end_time' => 'required',
]);

    Schedule::create([
    'teacher_id' => $request->teacher_id,
    'class_id' => $request->class_id,
    'subject' => $request->subject, // 👈 لازم تكون موجودة
    'day' => $request->day,
    'start_time' => $request->start_time,
    'end_time' => $request->end_time,
]);
    return response()->json([
        'success' => true,
        'message' => 'Schedule created successfully',
        'data' => Schedule::latest()->first()
    ]);

    }
    public function teacherSchedule(Request $request)
{
    $teacher = $request->user()->teacher;

    $schedules = Schedule::with('class')
        ->where('teacher_id', $teacher->id)
        ->orderBy('day')
        ->orderBy('start_time')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $schedules
    ]);
}




    public function studentSchedule()
{
    $user = auth()->user();

    $student = $user->student;

    if (!$student) {
        return response()->json([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $schedules = Schedule::where('class_id', $student->class_id)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $schedules
    ]);
}



public function parentSchedules(Request $request)
{
    $parent = $request->user()->parentProfile;

    $students = $parent->students()->with('class')->get();

    $data = [];

    foreach ($students as $student) {

        $schedules = Schedule::where(
            'class_id',
            $student->class_id
        )
        ->orderBy('day')
        ->orderBy('start_time')
        ->get();

        $data[] = [
            'student_id'   => $student->id,
            'student_name' => $student->user->name ?? null,
            'class_id'     => $student->class_id,
            'schedule'     => $schedules
        ];
    }

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}

    





}

