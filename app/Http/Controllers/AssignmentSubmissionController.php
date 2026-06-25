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
    'data' => [
        'assignment_id' => $submission->assignment_id,
        'student_id' => $submission->student_id,
        'file_path' => asset('storage/' . $submission->file_path),
        'status' => $submission->status,
        'created_at' => $submission->created_at,
        'updated_at' => $submission->updated_at,
        'id' => $submission->id,
    ]
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
        ->get()
        ->map(function ($submission) {

    return [
        'student_id'   => $submission->student->student_id,
        'student_name' => $submission->student->user->name,
        'status'       => $submission->status,
        'score'        => $submission->score,
        'feedback'     => $submission->feedback,
        'file_path'     => asset('storage/' . $submission->file_path),
    ];
});

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
    
    $student = Student::where('student_id', $data['student_id'])->first();

if (!$student) {
    return response()->json([
        'success' => false,
        'message' => 'Student not found'
    ], 404);
}

$submission = AssignmentSubmission::where('assignment_id', $data['assignment_id'])
    ->where('student_id', $student->id)
    ->first();

if (!$submission) {
    return response()->json([
        'success' => false,
        'message' => 'Submission not found'
    ], 404);
}

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
public function StudentGrades(Request $request): JsonResponse
{
    $student = $request->user()->student;

    if (!$student) {
        return response()->json([
            'success' => false,
            'message' => 'This user is not linked to a student record'
        ], 404);
    }

    $grades = AssignmentSubmission::with('assignment')
        ->where('student_id', $student->id)
        ->get()
        ->map(function ($submission) {

            return [
                'assignment_title' => $submission->assignment->title ?? 'N/A',
                'score' => $submission->score,
                'feedback' => $submission->feedback,
                'status' => $submission->status,
                'submitted_at' => $submission->created_at->format('Y-m-d H:i'),
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $grades
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
