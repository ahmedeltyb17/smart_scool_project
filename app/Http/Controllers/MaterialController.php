<?php

namespace App\Http\Controllers;

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
    $user = $request->user();

    $query = Material::with(['class', 'teacher.user']);

    if ($user->role === 'student') {
        $query->where('class_id', $user->student->class_id);
    }

    if ($user->role === 'teacher') {
        $query->where('teacher_id', $user->teacher->id);
    }

    $materials = $query->orderBy('created_at', 'desc')->get();

    if ($materials->isEmpty()) {
    return response()->json([
        'success' => true,
        'message' => 'No materials found',
        'data' => []
    ], 200);
}


    $data = $materials->map(function ($material) {
        return [
            'id' => $material->id,
            'title' => $material->title,
            'file_url' => asset('storage/' . $material->file_path),
            'class_name' => $material->class->name ?? null,
            'teacher_name' => $material->teacher->name ?? null,
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Materials fetched successfully',
        'data' => $data
    ]);
}
    // ──────────────────────────────────────────────────────────────────────
    // POST /materials  — Teacher / Admin
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'class_id'     => ['required', 'integer', 'exists:classes,id'],
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
            'class_id'     => $data['class_id'],
            'teacher_id'   => $teacherId,
            'external_url' => $data['external_url'] ?? null,
            'file_path'    => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Material uploaded successfully.',
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
