<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Parent_Student;
use Illuminate\support\Facades\Hash;
use App\Models\ParentModel;
/**
 * ParentController
 *
 * Parents can:
 *   - View their linked children's profiles
 *   - View attendance history for each child
 *   - View grades for each child
 *   - Manage their own profile
 *
 * Admins can link parents ↔ students.
 *
 * Routes prefix: /api/v1/parents
 */
class ParentController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /parents  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $parents = ParentModel::with(['user', 'students.user'])
            ->when($request->search, fn ($q) =>
                $q->whereHas('user', fn ($u) =>
                    $u->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                )
            )
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data'    => $parents,
        ]);
    }


////profile parent  ____________________________
    
    
    public function profile(Request $request): JsonResponse
{
    $parent = ParentModel::with([
        'user',
        'students.user',
        'students.classes'
    ])->where('user_id', $request->user()->id)
    ->first();

    if (!$parent) {
        return response()->json([
            'success' => false,
            'message' => 'Parent profile not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'parent' => $parent
        ]
    ]);
} 
    // ──────────────────────────────────────────────────────────────────────
    // POST /parents  — Admin only
    // Create a parent account
    // ──────────────────────────────────────────────────────────────────────

    
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'unique:users'],
            'password'     => ['required', Password::min(8)->mixedCase()->numbers()],
            'phone'        => ['nullable', 'string', 'max:20'],
            'national_id'  => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:500'],
            'student_ids'  => ['nullable', 'array'],
            'student_ids.*'=> ['integer', 'exists:students,id'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role'      => 'parent',
                'phone'     => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            $parent = \App\Models\ParentModel::create([
                'user_id'     => $user->id,
                'national_id' => $data['national_id'] ?? null,
                'address'     => $data['address'] ?? null,
            ]);

            // Link children if provided
            if (! empty($data['student_ids'])) {
                foreach ($data['student_ids'] as $studentId) {
                $parent->students()
                ->syncWithoutDetaching($data['student_ids']);

                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed.', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parent account created.',
            'data'    => ['parent' => $parent->load('user', 'students.user')],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /parents/{id}  — Admin or own Parent
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $parent = \App\Models\ParentModel::with(['user', 'students.user', 'students.classes'])->findOrFail($id);

        if ($request->user()->role === 'parent') {
            $own = $request->user()->parentProfile;
            if (! $own || $own->id !== $parent->id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => ['parent' => $parent],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /parents/{id}/link-student  — Admin only
    // Link an existing student to a parent
    // ──────────────────────────────────────────────────────────────────────
    public function linkStudent(Request $request): JsonResponse
{
    $parent = $request->user()->parentProfile;

    if ($request->user()->role !== 'parent') {
    abort(403);
}
    $data = $request->validate([
        'student_id' => ['required', 'string', 'exists:students,student_id'],
    ]);

    $student = Student::where('student_id', $data['student_id'])->first();

    if (!$student) {
        return response()->json([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    if ($parent->students()->where('students.id', $student->id)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Already linked'
        ], 422);
    }

    $parent->students()->attach($student->id);

    return response()->json([
        'success' => true,
        'message' => 'Student linked successfully'
    ]);
}
    // ──────────────────────────────────────────────────────────────────────
    // DELETE /parents/{id}/unlink-student/{studentId}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function unlinkStudent(Request $request, int $studentId)
{
    $parent = $request->user()->parentProfile;

    $parent->students()->detach($studentId);

    return response()->json([
        'success' => true,
        'message' => 'Student unlinked.'
    ]);
}

    // ──────────────────────────────────────────────────────────────────────
    // GET /parents/{id}/children  — Admin or own Parent
    // List all children linked to this parent
    // ──────────────────────────────────────────────────────────────────────
    public function children(Request $request, string $id): JsonResponse
    {
        $parent = \App\Models\ParentModel::findOrFail($id);

        if ($request->user()->role === 'parent') {
            $own = $request->user()->parentProfile;
            if (! $own || $own->id !== $parent->id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        $students = $parent->students()->with(['user', 'classes'])->get();

        return response()->json([
            'success' => true,
            'data'    => ['children' => $students],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /parents/{id}/children/{studentId}/attendance
    // Parent views their child's attendance
    // ──────────────────────────────────────────────────────────────────────
    public function childAttendance(Request $request, string $studentId): JsonResponse
{
    // هات الطالب من الـ public student_id
    $student = Student::where('student_id', $studentId)->firstOrFail();

    // تحقق إن الأب مرتبط بالطالب
    $this->authorizeParentAccess($request, $student->id);

    $history = Attendance::with('class')
        ->where('student_id', $student->id)
        ->orderBy('date', 'desc')
        ->paginate(20);

    $all = Attendance::where('student_id', $student->id)->get();

    $total = $all->count();

    $present = $all->where('status', 'present')->count();

    return response()->json([
        'success' => true,
        'data' => [

            'student' => $student->student_id,

            'summary' => [
                'total'   => $total,
                'present' => $present,
                'absent'  => $all->where('status', 'absent')->count(),
                'late'    => $all->where('status', 'late')->count(),

                'attendance_rate' => $total > 0
                    ? round(($present / $total) * 100, 2) . '%'
                    : '0%',
            ],

            'records' => $history,
        ],
    ]);
}

    // ──────────────────────────────────────────────────────────────────────
    // GET /parents/{id}/children/{studentId}/grades
    // Parent views their child's grades
    // ──────────────────────────────────────────────────────────────────────
    public function childGrades(Request $request, string $id, string $studentId): JsonResponse
    {
        $this->authorizeParentAccess($request, $id, $studentId);

        $grades = Grade::with('assignment')
            ->where('student_id', $studentId)
            ->get();

        $avg = $grades->avg('score');

        return response()->json([
            'success' => true,
            'data' => [
                'average' => $avg ? round($avg, 2) : null,
                'grades'  => $grades,
            ],
        ]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────

    /**
     * Ensure the authenticated parent is actually linked to the given student.
     * Admins bypass this check.
     */
    private function authorizeParentAccess(Request $request, int $parentId, int $studentId): void
    {
        if ($request->user()->role === 'parent') {
            $own = $request->user()->parentProfile;

            $linked = Parent_Student::where('parent_id', $parentId)
                                ->where('student_id', $studentId)
                                ->exists();

            if (! $own || $own->id !== $parentId || ! $linked) {
                
                abort(403, 'Access denied.');
            }
            
        }
    }

}
