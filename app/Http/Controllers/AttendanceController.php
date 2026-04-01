<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
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
    public function mark(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_id'             => ['required', 'integer', 'exists:classes,id'],
            'schedule_id'          => ['nullable', 'integer', 'exists:schedules,id'],
            'date'                 => ['required', 'date', 'before_or_equal:today'],
            'records'              => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.status'     => ['required', 'in:present,absent,late,excused'],
            'records.*.notes'      => ['nullable', 'string', 'max:500'],
        ]);

        // If teacher, verify ownership of the class
        $user = $request->user();
        if ($user->role === 'teacher') {
            $owns = Classes::where('id', $data['class_id'])
                           ->where('teacher_id', $user->teacher->id)
                           ->exists();
            if (! $owns) {
                return response()->json(['success' => false, 'message' => 'Access denied. Not your class.'], 403);
            }
        }

        $saved = [];
        foreach ($data['records'] as $record) {
            $saved[] = Attendance::updateOrCreate(
                [
                    'student_id'  => $record['student_id'],
                    'class_id'    => $data['class_id'],
                    'date'        => $data['date'],
                ],
                [
                    'schedule_id' => $data['schedule_id'] ?? null,
                    'status'      => $record['status'],
                    'notes'       => $record['notes'] ?? null,
                    'marked_by'   => $user->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => count($saved) . ' record(s) saved.',
            'data'    => ['records' => $saved],
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
    public function studentHistory(Request $request, int $studentId): JsonResponse
    {
        // Students can only view their own attendance
        if ($request->user()->role === 'student') {
            $own = $request->user()->student;
            if (! $own || $own->id !== $studentId) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        $history = Attendance::with('class', 'schedule')
            ->where('student_id', $studentId)
            ->orderBy('date', 'desc')
            ->paginate($request->per_page ?? 20);

        // Compute summary stats
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
