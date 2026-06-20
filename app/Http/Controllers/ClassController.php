<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Teacher;

/**
 * ClassController
 *
 * Admins manage all class records.
 * Teachers can view and manage their own classes.
 *
 * Routes prefix: /api/v1/classes
 */
class ClassController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /classes
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = ClassModel::with(['teacher.user'])->withCount('students');

        // Teachers only see their own classes
        if ($user->role === 'teacher') {
            $query->where('teacher_id', $user->teacher->id);
        }

        $query
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
            )
            ->when($request->grade_level,  fn ($q) => $q->where('grade_level', $request->grade_level));

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($request->per_page ?? 15),
        ]);
    }
 // ──────────────────────────────────────────────────────────────────────
    // POST /classes/{id}/students  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function addStudent(Request $request, int $id): JsonResponse
{
    $class = ClassModel::findOrFail($id);

    $student = Student::where('student_id', $request->student_id)->firstOrFail();

    $class->students()->syncWithoutDetaching([$student->id]);
    $request->validate([
        'student_id' => ['required', 'exists:students,student_id']
    ]);
    

    return response()->json([
        'success' => true,
        'message' => 'Student added successfully'
    ]);
}
    // ──────────────────────────────────────────────────────────────────────
    // POST /classes  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'grade_level'      => ['required', 'string', 'max:50'],
            'subject'    => ['required', 'string', 'max:100'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'room'       => ['nullable', 'string', 'max:50'],
            'capacity'   => ['nullable', 'integer', 'min:1'],
            'year'       => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $class = ClassModel::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Class created.',
            'data'    => ['class' => $class->load('teacher.user')],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /classes/{id}
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $class = ClassModel::with(['teacher.user', 'students.user', 'schedules'])->findOrFail($id);

        // Teachers can only view their own class
        if ($request->user()->role === 'teacher') {
            if ($class->teacher_id !== $request->user()->teacher->id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => ['class' => $class],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /classes/{id}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $class = ClassModel::findOrFail($id);

        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:100'],
            'grade_level'      => ['sometimes', 'string', 'max:50'],
            'subject'    => ['sometimes', 'string', 'max:100'],
            'teacher_id' => ['sometimes', 'integer', 'exists:teachers,id'],
            'room'       => ['nullable', 'string', 'max:50'],
            'capacity'   => ['nullable', 'integer', 'min:1'],
        ]);

        $class->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Class updated.',
            'data'    => ['class' => $class->fresh(['teacher.user'])],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /classes/{id}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $class = ClassModel::findOrFail($id);

        if ($class->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a class with enrolled students.',
            ], 422);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /classes/{id}/schedule
    // ──────────────────────────────────────────────────────────────────────
    public function schedule(int $id): JsonResponse
    {
        $class     = ClassModel::findOrFail($id);
        $schedules = Schedules::where('class_id', $id)
                            ->orderBy('day_of_week')
                            ->orderBy('start_time')
                            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'class'     => $class->only('id', 'name', 'grade_level', 'subject'),
                'schedules' => $schedules,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /classes/{id}/schedule  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function addSchedule(Request $request, int $id): JsonResponse
    {
        ClassModel::findOrFail($id); // ensure class exists

        $data = $request->validate([
            'day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'room'        => ['nullable', 'string', 'max:50'],
        ]);

        $schedule = Schedule::create([
            'class_id'    => $id,
            'day_of_week' => $data['day_of_week'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'room'        => $data['room'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule entry added.',
            'data'    => ['schedule' => $schedule],
        ], 201);
    }

    public function teacherClasses(Request $request): JsonResponse
{
    $teacher = $request->user()->teacher;

    $classes = ClassModel::withCount('students')
        ->where('teacher_id', $teacher->id)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $classes
    ]);
}

public function classStudents(Request $request, int $classId): JsonResponse
{
    $teacher = $request->user()->teacher;

    $class = ClassModel::with([
        'students.user',
        'students.attendances' => function ($query) {
            $query->whereDate('date', today());
        }
    ])
    ->where('teacher_id', $teacher->id)
    ->findOrFail($classId);

    $students = $class->students->map(function ($student) {

        $attendance = $student->attendances->first();

        return [
            'student_id' => $student->student_id,
            'student_name' => $student->user->name,
            'status' => $attendance?->status ?? 'not_marked',
        ];
    });

    return response()->json([
        'success' => true,
        'data' => [
            'class_id' => $class->id,
            'class_name' => $class->name,
            'students' => $students
        ]
    ]);
}
}
