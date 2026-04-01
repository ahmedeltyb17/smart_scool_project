<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Assignment;
use App\Models\ChatbotConversation;
use App\Models\Classes;
use App\Models\Conversation;
use App\Models\Grade;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DashboardController
 *
 * Provides statistics tailored to the requesting user's role.
 *
 * Admin  → school-wide stats
 * Teacher → stats for their classes only
 * Student → personal stats (grades, attendance)
 * Parent  → children's combined stats
 *
 * Routes prefix: /api/v1/dashboard
 */
class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = match ($user->role) {
            'admin'   => $this->adminStats(),
            'teacher' => $this->teacherStats($user),
            'student' => $this->studentStats($user),
            'parent'  => $this->parentStats($user),
            default   => [],
        };

        return response()->json([
            'success' => true,
            'role'    => $user->role,
            'data'    => $stats,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ADMIN: School-wide overview
    // ──────────────────────────────────────────────────────────────────────
    private function adminStats(): array
    {
        $today = now()->toDateString();

        // User counts by role
        $usersByRole = User::selectRaw('role, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('role')
            ->pluck('count', 'role');

        // Attendance today
        $todayAttendance = Attendance::whereDate('date', $today)->get();
        $presentToday    = $todayAttendance->where('status', 'present')->count();
        $totalMarked     = $todayAttendance->count();

        // Attendance this month
        $monthStart      = now()->startOfMonth()->toDateString();
        $monthRecords    = Attendance::whereBetween('date', [$monthStart, $today])->get();
        $monthTotal      = $monthRecords->count();
        $monthPresent    = $monthRecords->where('status', 'present')->count();

        // Assignments due this week
        $assignmentsDueThisWeek = Assignment::where('due_date', '>=', now())
            ->where('due_date', '<=', now()->endOfWeek())
            ->where('is_published', true)
            ->count();

        // Active conversations
        $activeConversations = Conversation::count();

        // Unread messages
        $unreadMessages = Message::where('is_read', false)->count();

        // New registrations (last 30 days)
        $newStudents30 = Student::where('created_at', '>=', now()->subDays(30))->count();

        // Grade distribution
        $gradeDistribution = Grade::selectRaw('letter_grade, COUNT(*) as count')
            ->whereNotNull('letter_grade')
            ->groupBy('letter_grade')
            ->orderBy('letter_grade')
            ->pluck('count', 'letter_grade');

        // Per-status attendance breakdown for today
        $statusBreakdown = $todayAttendance->groupBy('status')
            ->map->count()
            ->toArray();

        return [
            'overview' => [
                'total_students'  => $usersByRole['student'] ?? 0,
                'total_teachers'  => $usersByRole['teacher'] ?? 0,
                'total_parents'   => $usersByRole['parent']  ?? 0,
                'total_admins'    => $usersByRole['admin']   ?? 0,
                'total_classes'   => Classes::count(),
            ],
            'attendance' => [
                'today' => [
                    'total_marked'    => $totalMarked,
                    'present'         => $presentToday,
                    'breakdown'       => $statusBreakdown,
                    'rate'            => $totalMarked > 0
                        ? round(($presentToday / $totalMarked) * 100, 2) . '%'
                        : 'No data',
                ],
                'this_month' => [
                    'total'   => $monthTotal,
                    'present' => $monthPresent,
                    'rate'    => $monthTotal > 0
                        ? round(($monthPresent / $monthTotal) * 100, 2) . '%'
                        : 'No data',
                ],
            ],
            'academics' => [
                'assignments_due_this_week' => $assignmentsDueThisWeek,
                'total_grades_recorded'    => Grade::count(),
                'grade_distribution'       => $gradeDistribution,
                'overall_average'          => round(Grade::avg('score') ?? 0, 2),
            ],
            'communication' => [
                'active_conversations' => $activeConversations,
                'unread_messages'      => $unreadMessages,
                'chatbot_sessions'     => ChatbotConversation::count(),
            ],
            'recent_activity' => [
                'new_students_last_30_days' => $newStudents30,
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // TEACHER: Stats for their classes
    // ──────────────────────────────────────────────────────────────────────
    private function teacherStats(User $user): array
    {
        $teacher  = $user->teacher;
        $classIds = $teacher->classes->pluck('id');
        $today    = now()->toDateString();

        $studentIds = Student::whereIn('class_id', $classIds)->pluck('id');

        $todayAttendance = Attendance::whereIn('class_id', $classIds)
            ->whereDate('date', $today)
            ->get();

        $presentToday = $todayAttendance->where('status', 'present')->count();
        $totalMarked  = $todayAttendance->count();

        $pendingGrades = Assignment::where('teacher_id', $teacher->id)
            ->where('due_date', '<', now())
            ->withCount(['grades as ungraded_count' => fn ($q) =>
                $q->whereNotIn('student_id', $studentIds)
            ])
            ->get()
            ->sum('ungraded_count');

        return [
            'overview' => [
                'total_classes'  => $classIds->count(),
                'total_students' => $studentIds->count(),
            ],
            'attendance_today' => [
                'marked'  => $totalMarked,
                'present' => $presentToday,
                'absent'  => $todayAttendance->where('status', 'absent')->count(),
                'late'    => $todayAttendance->where('status', 'late')->count(),
                'rate'    => $totalMarked > 0
                    ? round(($presentToday / $totalMarked) * 100, 2) . '%'
                    : 'No data',
            ],
            'assignments' => [
                'total_published'    => Assignment::where('teacher_id', $teacher->id)
                                                  ->where('is_published', true)->count(),
                'due_this_week'      => Assignment::where('teacher_id', $teacher->id)
                                                  ->whereBetween('due_date', [now(), now()->endOfWeek()])
                                                  ->count(),
                'awaiting_grading'   => $pendingGrades,
            ],
            'grades' => [
                'average_score' => round(
                    Grade::whereIn('student_id', $studentIds)->avg('score') ?? 0, 2
                ),
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // STUDENT: Personal academic dashboard
    // ──────────────────────────────────────────────────────────────────────
    private function studentStats(User $user): array
    {
        $student = $user->student;

        if (! $student) {
            return ['message' => 'Student profile not found.'];
        }

        $studentId = $student->id;

        $allAttendance = Attendance::where('student_id', $studentId)->get();
        $totalSessions = $allAttendance->count();
        $presentCount  = $allAttendance->where('status', 'present')->count();

        $allGrades = Grade::with('assignment')->where('student_id', $studentId)->get();

        $upcomingAssignments = Assignment::where('class_id', $student->class_id)
            ->where('is_published', true)
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'title', 'type', 'due_date', 'max_score']);

        $recentGrades = $allGrades->sortByDesc('created_at')->take(5)->values();

        return [
            'attendance' => [
                'total_sessions'  => $totalSessions,
                'present'         => $presentCount,
                'absent'          => $allAttendance->where('status', 'absent')->count(),
                'late'            => $allAttendance->where('status', 'late')->count(),
                'attendance_rate' => $totalSessions > 0
                    ? round(($presentCount / $totalSessions) * 100, 2) . '%'
                    : '0%',
            ],
            'grades' => [
                'total_graded'    => $allGrades->count(),
                'average_score'   => $allGrades->count() > 0
                    ? round($allGrades->avg('score'), 2)
                    : null,
                'recent_grades'   => $recentGrades,
            ],
            'upcoming_assignments' => $upcomingAssignments,
            'unread_messages'      => Message::whereHas('conversation.participants', fn ($q) =>
                                            $q->where('user_id', $user->id))
                                          ->where('sender_id', '!=', $user->id)
                                          ->where('is_read', false)
                                          ->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // PARENT: Summary across all children
    // ──────────────────────────────────────────────────────────────────────
    private function parentStats(User $user): array
    {
        $parent   = $user->parentProfile;
        $children = $parent ? $parent->students()->with('user', 'class')->get() : collect();

        $childStats = $children->map(function ($student) {
            $all     = Attendance::where('student_id', $student->id)->get();
            $total   = $all->count();
            $present = $all->where('status', 'present')->count();
            $grades  = Grade::where('student_id', $student->id)->get();

            return [
                'student_id'      => $student->id,
                'name'            => $student->user->name,
                'class'           => $student->class->name ?? null,
                'grade'           => $student->grade,
                'attendance_rate' => $total > 0
                    ? round(($present / $total) * 100, 2) . '%'
                    : '0%',
                'average_grade'   => $grades->count() > 0
                    ? round($grades->avg('score'), 2)
                    : null,
            ];
        });

        return [
            'total_children' => $children->count(),
            'children'       => $childStats,
        ];
    }
}
