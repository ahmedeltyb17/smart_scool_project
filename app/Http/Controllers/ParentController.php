<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\ParentStudent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

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
        $parents = \App\Models\ParentModel::with(['user', 'students.user'])
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
                    ParentStudent::create([
                        'parent_id'  => $parent->id,
                        'student_id' => $studentId,
                    ]);
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
        $parent = \App\Models\ParentModel::with(['user', 'students.user', 'students.class'])->findOrFail($id);

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
    public function linkStudent(Request $request, int $id): JsonResponse
    {
        \App\Models\ParentModel::findOrFail($id);

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $exists = ParentStudent::where('parent_id', $id)
                               ->where('student_id', $data['student_id'])
                               ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Already linked.'], 422);
        }

        ParentStudent::create([
            'parent_id'  => $id,
            'student_id' => $data['student_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student linked to parent.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /parents/{id}/unlink-student/{studentId}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function unlinkStudent(int $id, int $studentId): JsonResponse
    {
        ParentStudent::where('parent_id', $id)
                     ->where('student_id', $studentId)
                     ->firstOrFail()
                     ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student unlinked.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /parents/{id}/children  — Admin or own Parent
    // List all children linked to this parent
    // ──────────────────────────────────────────────────────────────────────
    public function children(Request $request, int $id): JsonResponse
    {
        $parent = \App\Models\ParentModel::findOrFail($id);

        if ($request->user()->role === 'parent') {
            $own = $request->user()->parentProfile;
            if (! $own || $own->id !== $parent->id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        $students = $parent->students()->with(['user', 'class'])->get();

        return response()->json([
            'success' => true,
            'data'    => ['children' => $students],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /parents/{id}/children/{studentId}/attendance
    // Parent views their child's attendance
    // ──────────────────────────────────────────────────────────────────────
    public function childAttendance(Request $request, int $id, int $studentId): JsonResponse
    {
        $this->authorizeParentAccess($request, $id, $studentId);

        $history = Attendance::with('class')
            ->where('student_id', $studentId)
            ->orderBy('date', 'desc')
            ->paginate(20);

        $all     = Attendance::where('student_id', $studentId)->get();
        $total   = $all->count();
        $present = $all->where('status', 'present')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total'           => $total,
                    'present'         => $present,
                    'absent'          => $all->where('status', 'absent')->count(),
                    'late'            => $all->where('status', 'late')->count(),
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
    public function childGrades(Request $request, int $id, int $studentId): JsonResponse
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

            $linked = ParentStudent::where('parent_id', $parentId)
                                   ->where('student_id', $studentId)
                                   ->exists();

            if (! $own || $own->id !== $parentId || ! $linked) {
                abort(response()->json(['success' => false, 'message' => 'Access denied.'], 403));
            }
        }
    }
}
