<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * StudentMiddleware
 *
 * Allows access to students, teachers, and admins.
 * Teachers and admins can always access student-facing endpoints.
 * Must be used AFTER auth:sanctum middleware.
 */
class StudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        if (! in_array($user->role, ['admin', 'teacher', 'student'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
                'your_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
