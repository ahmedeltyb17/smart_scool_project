<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\ParentModel;

/**
 * RegisteredUserController
 *
 * Handles registration, login, logout, and profile management
 * for all roles: admin, teacher, student, parent.
 */
class RegisteredUserController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/login
    // ──────────────────────────────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Contact your administrator.',
            ], 403);
        }

        // Single-session policy: revoke previous tokens
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        // Eager-load the correct profile relation based on role
        $profile = match ($user->role) {
            'student' => $user->load('student'),
            'teacher' => $user->load('teacher'),
            'parent'  => $user->load('parent'),
            default   => $user,
        };

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user'       => $profile,
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/register  (Admin-only in production)
    // ──────────────────────────────────────────────────────────────────────
    public function register(Request $request): JsonResponse
{
        $data = $request->validate([
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'confirmed'],
        'role'     => ['required', 'in:admin,teacher,student,parent'],
        'phone'    => ['nullable', 'string', 'max:20'],
        'address'  => ['nullable', 'string', 'max:255'],
    ]);

    // 🔥 مهم: اعمل hash
    $data['password'] = Hash::make($data['password']);

    $user = User::create([
        'name'      => $data['name'],
        'email'     => $data['email'],
        'password'  => $data['password'],
        'role'      => $data['role'],
        'phone'     => $data['phone'] ?? null,
        'address'   => $data['address'] ?? null,
        'student_id' => $data['student_id'] ?? null,
        'is_active' => true,
    ]);

    // 🔥 الحل هنا
    
    // teacher
if ($data['role'] === 'teacher') {
    Teacher::create([
        'user_id' => $user->id,
        'name'    => $user->name,
        'email'   => $user->email,
        
    ]);
}


// parent
if ($data['role'] === 'parent') {
    ParentModel::create([
        'user_id' => $user->id
    ]);
}

// student
if ($data['role'] === 'student') {

    // 1. get class with < 30 students
    $class = ClassModel::withCount('students')
        ->having('students_count', '<', 30)
        ->first();

    // 2. create new class if none exists
    if (!$class) {
        $teacher = Teacher::first();

        $class = ClassModel::create([
            'name' => 'Class ' . (ClassModel::count() + 1),
            'teacher_id' => $teacher->id,
        ]);
    }

    // 3. generate student_id safely
    $lastStudent = Student::orderBy('id', 'desc')->first();

    $number = $lastStudent
        ? ((int) str_replace('STD-', '', $lastStudent->student_id)) + 1
        : 1;

    $studentId = 'STD-' . str_pad($number, 4, '0', STR_PAD_LEFT);

    // 4. create student
    Student::create([
    'user_id'    => $user->id,
    'class_id'   => $class->id,
    'student_id' => $studentId,
]);
}

    return response()->json([
        'success' => true,
        'message' => 'User registered successfully.',
        'data'    => ['user' => $user],
    ], 201);
    
}

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/logout
    // ──────────────────────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/v1/auth/me
    // ──────────────────────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = match ($user->role) {
            'student' => $user->load('student.class'),
            'teacher' => $user->load('teacher.classes'),
            'parent'  => $user->load('parent.students.user'),
            default   => $user,
        };

        return response()->json([
            'success' => true,
            'data'    => ['user' => $profile],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /api/v1/auth/profile
    // ──────────────────────────────────────────────────────────────────────
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'string', 'email', 'unique:users,email,' . $user->id],
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data'    => ['user' => $user->fresh()],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /api/v1/auth/password
    // ──────────────────────────────────────────────────────────────────────
    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update(['password' => $data['password']]);
        $user->tokens()->delete(); // force re-login everywhere

        return response()->json([
            'success' => true,
            'message' => 'Password changed. Please log in again.',
        ]);
    }
}
