<?php

namespace App\Http\Controllers;





use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\ClassModel;


class AssignmentSubmissionController extends Controller
{
    //  student upload assignment submission
    public function submit(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $data = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        // منع إعادة التسليم
        $exists = AssignmentSubmission::where('assignment_id', $data['assignment_id'])
            ->where('student_id', $student->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Already submitted'
            ], 400);
        }

        // upload file
        $path = $request->file('file')
            ->store('assignment_submissions', 'public');

        $submission = AssignmentSubmission::create([
            'assignment_id' => $data['assignment_id'],
            'student_id' => $student->id,
            'file_path' => $path,
            'status' => 'submitted'
        ]);

        return response()->json([
            'success' => true,
            'data' => $submission
        ], 201);
    }
 
 
 //  teacher view all submissions for an assignment
   public function assignmentSubmissions(Request $request): JsonResponse
{
    $request->validate([
        'assignment_id' => 'required|exists:assignments,id'
    ]);

    $submissions = AssignmentSubmission::with('student.user')
        ->where('assignment_id', $request->assignment_id)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $submissions
    ]);
}

// teacher grade a submission
public function grade(Request $request): JsonResponse
{
    $data = $request->validate([
        'assignment_id' => 'required|exists:assignments,id',
        'student_id'    => 'required|string',
        'score'         => 'required|integer|min:0',
        'feedback'      => 'nullable|string'
    ]);

    $student = Student::where('student_id', $data['student_id'])
        ->firstOrFail();

    $submission = AssignmentSubmission::where('assignment_id', $data['assignment_id'])
        ->where('student_id', $student->id) // الـ FK الموجود في submissions
        ->firstOrFail();

    $submission->update([
        'score'    => $data['score'],
        'feedback' => $data['feedback'] ?? null,
        'status'   => 'graded'
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Grade added successfully',
        'data'    => $submission
    ]);
}

// student view their own submissions
public function studentSubmissions(Request $request): JsonResponse
{
    $student = $request->user()->student;

    $submissions = AssignmentSubmission::with('assignment')
        ->where('student_id', $student->id)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $submissions
    ]);
}

// parent view submissions for their children
public function parentSubmissions(Request $request): JsonResponse
{
    $parent = $request->user()->parentProfile;

    $studentIds = $parent->students()->pluck('students.id');

    $submissions = AssignmentSubmission::with([
        'assignment',
        'student.user'
    ])
    ->whereIn('student_id', $studentIds)
    ->get();

    return response()->json([
        'success' => true,
        'data' => $submissions
    ]);
}
}
