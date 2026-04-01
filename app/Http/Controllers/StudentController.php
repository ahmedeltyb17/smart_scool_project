<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * StudentController
 *
 * Admin: full CRUD on student records.
 * Teacher: read-only list + show (students in their classes).
 * Student: view own profile only.
 *
 * Routes prefix: /api/v1/students
 */
class StudentController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /students  — Admin + Teacher
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Student::with(['user', 'class']);

        // Teachers only see students in their own classes
        if ($user->role === 'teacher') {
            $classIds = $user->teacher->classes->pluck('id');
            $query->whereIn('class_id', $classIds);
        }

        // Optional filters
        $query
            ->when($request->search, fn ($q) =>
                $q->whereHas('user', fn ($u) =>
                    $u->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%")
                )
            )
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->grade,    fn ($q) => $q->where('grade', $request->grade));

        $students = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data'    => $students,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /students  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'unique:users'],
            'password'       => ['required', Password::min(8)->mixedCase()->numbers()],
            'student_code'   => ['required', 'string', 'unique:students'],
            'class_id'       => ['required', 'integer', 'exists:classes,id'],
            'grade'          => ['required', 'string', 'max:50'],
            'date_of_birth'  => ['nullable', 'date'],
            'gender'         => ['nullable', 'in:male,female,other'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'enrolled_at'    => ['nullable', 'date'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role'      => 'student',
                'is_active' => true,
            ]);

            $student = Student::create([
                'user_id'       => $user->id,
                'student_code'  => $data['student_code'],
                'class_id'      => $data['class_id'],
                'grade'         => $data['grade'],
                'enrolled_at'   => $data['enrolled_at'] ?? now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully.',
                'data'    => ['student' => $student->load('user', 'class')],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /students/{id}  — Admin, Teacher, or own Student
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $student = Student::with(['user', 'class', 'grades', 'attendances'])->findOrFail($id);

        // Students can only view their own profile
        if ($request->user()->role === 'student') {
            $own = $request->user()->student;
            if (! $own || $own->id !== $student->id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => ['student' => $student],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /students/{id}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::with('user')->findOrFail($id);

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'email'         => ['sometimes', 'string', 'email', 'unique:users,email,' . $student->user_id],
            'class_id'      => ['sometimes', 'integer', 'exists:classes,id'],
            'grade'         => ['sometimes', 'string', 'max:50'],
            'gender'        => ['nullable', 'in:male,female,other'],
        ]);

        DB::beginTransaction();
        try {
            $userFields    = array_intersect_key($data, array_flip(['name', 'email', 'phone']));
            $studentFields = array_diff_key($data, array_flip(['name', 'email', 'phone']));

            if (! empty($userFields))    { $student->user->update($userFields); }
            if (! empty($studentFields)) { $student->update($studentFields); }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student updated.',
            'data'    => ['student' => $student->fresh(['user', 'class'])],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /students/{id}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $student->user->update(['is_active' => false]);
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deactivated and soft-deleted.',
        ]);
    }
}
