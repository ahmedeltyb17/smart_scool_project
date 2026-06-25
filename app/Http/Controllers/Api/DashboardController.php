<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents  = Student::count();
        $totalTeachers  = Teacher::count();
        $totalClasses   = ClassModel::count();

        $today          = now()->toDateString();
        $todayAttendance = Attendance::whereDate('date', $today)->get();
        $present        = $todayAttendance->where('status', 'present')->count();
        $total          = $todayAttendance->count();
        $avgAttendance  = $total > 0 ? round(($present / $total) * 100) : 0;

        return response()->json([
            'totalStudents'  => $totalStudents,
            'totalTeachers'  => $totalTeachers,
            'totalClasses'   => $totalClasses,
            'avgAttendance'  => $avgAttendance,
        ]);
    }
}