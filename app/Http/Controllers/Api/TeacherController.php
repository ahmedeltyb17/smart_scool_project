<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::with('user')->get()->map(function ($teacher) {
            return [
                'id'           => $teacher->id,
                'name'         => $teacher->user->name ?? '',
                'email'        => $teacher->user->email ?? '',
                'subject'      => $teacher->subject ?? '',
                'status'       => 'active',
                'quizzesCount' => 0,
                'classes'      => [],
            ];
        });

        return response()->json($teachers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'email'   => 'required|email|unique:users,email',
            'subject' => 'required|string',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt('password123'),
            'role'     => 'teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'subject' => $request->subject,
        ]);

        return response()->json([
            'id'           => $teacher->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'subject'      => $teacher->subject,
            'status'       => 'active',
            'quizzesCount' => 0,
            'classes'      => [],
        ], 201);
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->user->delete();
        return response()->json(null, 204);
    }
}