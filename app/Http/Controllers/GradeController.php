<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GradeController
 *
 * Teachers enter and manage grades for their students.
 * Students can view their own grades.
 * Admins have full access.
 *
 * Routes prefix: /api/v1/grades
 */
class GradeController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /grades  — Teacher (own students) / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Grade::with(['student.user', 'assignment']);

        if ($user->role === 'teacher') {
            $classIds = $user->teacher->classes->pluck('id');
            $query->whereHas('student', fn ($q) => $q->whereIn('class_id', $classIds));
        }

        $query
            ->when($request->student_id,   fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->assignment_id, fn ($q) => $q->where('assignment_id', $request->assignment_id))
            ->when($request->class_id, fn ($q) =>
                $q->whereHas('student', fn ($s) => $s->where('class_id', $request->class_id))
            );

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($request->per_page ?? 20),
        ]);
    }

    //------------------------------------
    // show my grades for student
    //------------------------------------
    public function myGrades(Request $request)
{
    
    $student = $request->user()->student;

    $grades = Grade::with('assignment')
        ->where('student_id', $student->id)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $grades
    ]);
}

    // ──────────────────────────────────────────────────────────────────────
    // POST /grades  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'exists:students,student_id'],
            'assignment_id' => ['required', 'integer', 'exists:assignments,id'],
            'score'         => ['required', 'numeric', 'min:0', 'max:100'],
            'letter_grade'  => ['nullable', 'string', 'max:5'],
            'feedback'      => ['nullable', 'string', 'max:2000'],
        ]);

        // Prevent duplicate grade for same student + assignment
        $exists = Grade::where('student_id', $data['student_id'])
                       ->where('assignment_id', $data['assignment_id'])
                       ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Grade already exists. Use PUT to update.',
            ], 422);
        }

        // Auto-derive letter grade if not provided
        if (empty($data['letter_grade'])) {
            $data['letter_grade'] = $this->scoreToLetter($data['score']);
        }

        $grade = Grade::create([
            'student_id'    => $data['student_id'],
            'assignment_id' => $data['assignment_id'],
            'score'         => $data['score'],
            'letter_grade'  => $data['letter_grade'],
            'feedback'      => $data['feedback'] ?? null,
            'graded_by'     => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grade recorded.',
            'data'    => ['grade' => $grade->load('student.user', 'assignment')],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /grades/{id}  — Teacher / Admin / own Student
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $grade = Grade::with(['student.user', 'assignment'])->findOrFail($id);

        if ($request->user()->role === 'student') {
            $own = $request->user()->student;
            if (! $own || $own->id !== $grade->student_id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => ['grade' => $grade],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /grades/{id}  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $grade = Grade::findOrFail($id);

        $data = $request->validate([
            'score'        => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'letter_grade' => ['nullable', 'string', 'max:5'],
            'feedback'     => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($data['score']) && empty($data['letter_grade'])) {
            $data['letter_grade'] = $this->scoreToLetter($data['score']);
        }

        $grade->update($data + ['graded_by' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Grade updated.',
            'data'    => ['grade' => $grade->fresh(['student.user', 'assignment'])],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /grades/{id}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        Grade::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grade deleted.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /grades/student/{studentId}/summary
    // Returns GPA-style summary for a student
    // ──────────────────────────────────────────────────────────────────────
    public function studentSummary(Request $request, int $studentId): JsonResponse
    {
        if ($request->user()->role === 'student') {
            $own = $request->user()->student;
            if (! $own || $own->id !== $studentId) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        $grades = Grade::with('assignment')->where('student_id', $studentId)->get();

        $avg = $grades->avg('score');

        return response()->json([
            'success' => true,
            'data' => [
                'student_id'   => $studentId,
                'total_grades' => $grades->count(),
                'average'      => $avg ? round($avg, 2) : null,
                'letter'       => $avg ? $this->scoreToLetter($avg) : null,
                'grades'       => $grades,
            ],
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function scoreToLetter(float $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'B+',
            $score >= 80 => 'B',
            $score >= 75 => 'C+',
            $score >= 70 => 'C',
            $score >= 65 => 'D+',
            $score >= 60 => 'D',
            default      => 'F',
        };
    }
}
