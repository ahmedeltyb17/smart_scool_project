<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * MaterialController
 *
 * Teachers upload and manage learning materials (PDFs, videos, links).
 * Students can view materials published for their class.
 *
 * Routes prefix: /api/v1/materials
 */
class MaterialController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /materials
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Material::with(['class', 'teacher.user']);

        if ($user->role === 'teacher') {
            $query->where('teacher_id', $user->teacher->id);
        }

        if ($user->role === 'student') {
            $query->where('class_id', $user->student->class_id)
                  ->where('is_published', true);
        }

        $query
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->type,     fn ($q) => $q->where('type', $request->type))
            ->when($request->search,   fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
            )
            ->orderBy('created_at', 'desc');

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($request->per_page ?? 15),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /materials  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'class_id'     => ['required', 'integer', 'exists:classes,id'],
            'type'         => ['required', 'in:document,video,link,image,other'],
            'is_published' => ['boolean'],
            'external_url' => ['nullable', 'url', 'required_if:type,link,video'],
            'file'         => ['nullable', 'file', 'max:51200',
                               'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,zip'],
        ]);

        $user = $request->user();
        $teacherId = $user->role === 'teacher' ? $user->teacher->id : $request->input('teacher_id');

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('materials', 'public');
        }

        $material = Material::create([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'class_id'     => $data['class_id'],
            'teacher_id'   => $teacherId,
            'type'         => $data['type'],
            'is_published' => $data['is_published'] ?? false,
            'external_url' => $data['external_url'] ?? null,
            'file_path'    => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Material uploaded.',
            'data'    => ['material' => $material->load('class', 'teacher.user')],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /materials/{id}
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $material = Material::with(['class', 'teacher.user'])->findOrFail($id);

        if ($request->user()->role === 'student') {
            if (! $material->is_published
                || $material->class_id !== $request->user()->student->class_id) {
                return response()->json(['success' => false, 'message' => 'Not found.'], 404);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => ['material' => $material],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /materials/{id}  — Teacher (own) / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $material = Material::findOrFail($id);

        if ($request->user()->role === 'teacher'
            && $material->teacher_id !== $request->user()->teacher->id) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $data = $request->validate([
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'external_url' => ['nullable', 'url'],
        ]);

        $material->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Material updated.',
            'data'    => ['material' => $material->fresh(['class', 'teacher.user'])],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /materials/{id}  — Teacher (own) / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $material = Material::findOrFail($id);

        if ($request->user()->role === 'teacher'
            && $material->teacher_id !== $request->user()->teacher->id) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material deleted.',
        ]);
    }
}
