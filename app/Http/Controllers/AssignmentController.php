<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\ClassModel;
use App\Models\Student;


/**
 * AssignmentController
 *
 * Teachers create, update, and delete assignments for their classes.
 * Students view assignments for their enrolled class.
 *
 * Routes prefix: /api/v1/assignments
 */
class AssignmentController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /assignments
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Assignment::with(['class', 'teacher.user']);

        if ($user->role === 'teacher') {
            $query->where('teacher_id', $user->teacher->id);
        }

        if ($user->role === 'student') {
            $classId = $user->student->class_id;
            $query->where('class_id', $classId);
}

        $query
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->when($request->due_before, fn ($q) => $q->where('due_date', '<=', $request->due_before))
            ->orderBy('due_date', 'asc');

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($request->per_page ?? 15),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /assignments  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'class_id'     => ['required', 'integer', 'exists:classes,id'],
            'due_date'     => ['required', 'date', 'after:today'],
            'max_score'    => ['required', 'numeric', 'min:1', 'max:100'],
            'type'         => ['required', 'in:homework,quiz,project,exam,other'],
            'attachment'   => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,zip'],
        ]);

        // Verify teacher owns the class (unless admin)
        if ($request->user()->role === 'teacher') {
            $teacherId = $request->user()->teacher->id;
            $data['teacher_id'] = $teacherId;

            $ownsClass = \App\Models\ClassModel::where('id', $data['class_id'])
                                            ->where('teacher_id', $teacherId)
                                            ->exists();
            if (! $ownsClass) {
                return response()->json(['success' => false, 'message' => 'You do not own this class.'], 403);
            }
        } else {
            $data['teacher_id'] = $request->input('teacher_id');
        }

        // Handle file upload
        $data['attachment_path'] = null;
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')
                ->store('assignments', 'public');
        }

        $path = null;

if ($request->hasFile('attachment')) {
    $path = $request->file('attachment')
                    ->store('assignments', 'public');
}
        $assignment = Assignment::create([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'class_id'        => $data['class_id'],
            'teacher_id'      => $data['teacher_id'],
            'due_date'        => $data['due_date'],
            'attachment_path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment created.',
            // 'data'    => ['assignment' => $assignment->load('class', 'teacher.user')],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /assignments/{id}
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::with(['class', 'teacher.user', 'grades.student.user'])->findOrFail($id);

        // Students only see published assignments for their class

        return response()->json([
            'success' => true,
            'data'    => ['assignment' => $assignment],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /assignments/{id}  — Teacher (own) / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        // Teachers can only edit their own assignments
        if ($request->user()->role === 'teacher') {
            if ($assignment->teacher_id !== $request->user()->teacher->id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        $data = $request->validate([
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'due_date'     => ['sometimes', 'date'],
            'max_score'    => ['sometimes', 'numeric', 'min:1', 'max:100'],
        ]);

        $assignment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated.',
            'data'    => ['assignment' => $assignment->fresh(['class', 'teacher.user'])],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /assignments/{id}  — Teacher (own) / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        if ($request->user()->role === 'teacher') {
            if ($assignment->teacher_id !== $request->user()->teacher->id) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
        }

        if ($assignment->attachment_path) {
            Storage::disk('public')->delete($assignment->attachment_path);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted.',
        ]);
    }

}