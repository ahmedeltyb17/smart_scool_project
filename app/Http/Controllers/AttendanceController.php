<?php

namespace App\Http\Controllers\Student;

use App\Notifications\StudentAbsentNotification;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AttendanceController
 *
 * Teachers mark and manage attendance per class/schedule.
 * Students view their own attendance history.
 * Admins have full access.
 *
 * Routes prefix: /api/v1/attendance
 */
class AttendanceController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // POST /attendance/mark  — Teacher / Admin
    // Bulk-mark attendance for a class session
    // ──────────────────────────────────────────────────────────────────────
    public function mark(Request $request)
{
    $request->validate([
        'student_id' => 'required',
        'class_id' => 'required',
        'status' => 'required|in:present,absent,late',
    ]);

    $attendance = Attendance::create([
        'student_id' => $request->student_id,
        'class_id' => $request->class_id,
        'status' => $request->status,
        'date' => now()->toDateString(),
    ]);

    // 🔥 لو absent ابعت notification
    if ($request->status === 'absent') {

        $student = Student::with('parents')->find($request->student_id);

        if ($student && $student->parents->count() > 0) {

            Notification::send(
                $student->parents,
                new StudentAbsentNotification($student, now()->toDateString())
            );
        }
    }

    return response()->json([
        'success' => true,
        'data' => $attendance
    ]);
}
    // ──────────────────────────────────────────────────────────────────────
    // GET /attendance/report  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_id'  => ['required', 'integer', 'exists:classes,id'],
            'from_date' => ['required', 'date'],
            'to_date'   => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $records = Attendance::with('student.user')
            ->where('class_id', $data['class_id'])
            ->whereBetween('date', [$data['from_date'], $data['to_date']])
            ->get();

        $total   = $records->count();
        $present = $records->where('status', 'present')->count();
        $absent  = $records->where('status', 'absent')->count();
        $late    = $records->where('status', 'late')->count();
        $excused = $records->where('status', 'excused')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total'           => $total,
                    'present'         => $present,
                    'absent'          => $absent,
                    'late'            => $late,
                    'excused'         => $excused,
                    'attendance_rate' => $total > 0
                        ? round(($present / $total) * 100, 2) . '%'
                        : '0%',
                ],
                'records' => $records,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /attendance/student/{studentId}  — Admin, Teacher, own Student
    // ──────────────────────────────────────────────────────────────────────
    public function studentHistory(Request $request, ?int $studentId = null): JsonResponse
{
    // لو طالب → خليه يجيب نفسه
    if ($request->user()->role === 'student') {
        $own = $request->user()->student;

        if (! $own) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        $studentId = $own->id;
    }

    $history = Attendance::with('class', 'schedule')
        ->where('student_id', $studentId)
        ->orderBy('date', 'desc')
        ->paginate($request->per_page ?? 20);

    $all     = Attendance::where('student_id', $studentId)->get();
    $total   = $all->count();
    $present = $all->where('status', 'present')->count();

    return response()->json([
        'success' => true,
        'data' => [
            'summary' => [
                'total_sessions'  => $total,
                'present'         => $present,
                'absent'          => $all->where('status', 'absent')->count(),
                'late'            => $all->where('status', 'late')->count(),
                'attendance_rate' => $total > 0
                    ? round(($present / $total) * 100, 2) . '%'
                    : '0%',
            ],
            'history' => $history,
        ],
    ]);
}

    // ──────────────────────────────────────────────────────────────────────
    // PUT /attendance/{id}  — Teacher / Admin
    // Correct an existing attendance record
    // ──────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $attendance = Attendance::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:present,absent,late,excused'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->update([
            'status'    => $data['status'],
            'notes'     => $data['notes'] ?? $attendance->notes,
            'marked_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance record updated.',
            'data'    => ['record' => $attendance->fresh()],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /attendance/class/{classId}/today  — Teacher / Admin
    // Quick view of today's attendance for a class
    // ──────────────────────────────────────────────────────────────────────
    public function today(Request $request, int $classId): JsonResponse
    {
        $records = Attendance::with('student.user')
            ->where('class_id', $classId)
            ->whereDate('date', today())
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'date'    => today()->toDateString(),
                'class_id'=> $classId,
                'records' => $records,
                'count'   => $records->count(),
            ],
        ]);
    }
}
