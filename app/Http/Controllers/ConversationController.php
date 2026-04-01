<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ConversationController
 *
 * Manages direct-message threads between any two users.
 * Any authenticated user can create/view their own conversations.
 *
 * Routes prefix: /api/v1/conversations
 */
class ConversationController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /conversations  — Current user's conversation list
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::with(['participants', 'latestMessage'])
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                      ->from('messages')
                      ->whereColumn('conversation_id', 'conversations.id')
                      ->orderByDesc('created_at')
                      ->limit(1);
            })
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $conversations,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /conversations  — Start or retrieve a conversation
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id', 'different:' . $request->user()->id],
        ]);

        $userId      = $request->user()->id;
        $recipientId = $data['recipient_id'];

        // Check if a conversation already exists between these two users
        $conversation = Conversation::whereHas('participants', fn ($q) =>
                                        $q->where('user_id', $userId))
                                    ->whereHas('participants', fn ($q) =>
                                        $q->where('user_id', $recipientId))
                                    ->first();

        if (! $conversation) {
            $conversation = Conversation::create(['type' => 'direct']);
            $conversation->participants()->createMany([
                ['user_id' => $userId],
                ['user_id' => $recipientId],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation ready.',
            'data'    => ['conversation' => $conversation->load(['participants.user', 'latestMessage'])],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /conversations/{id}  — Load conversation + messages
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        // Verify user is a participant
        $isParticipant = $conversation->participants()
                                      ->where('user_id', $request->user()->id)
                                      ->exists();

        if (! $isParticipant && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $messages = Message::with('sender')
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 30);

        // Mark messages as read
        Message::where('conversation_id', $id)
               ->where('sender_id', '!=', $request->user()->id)
               ->where('is_read', false)
               ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load('participants.user'),
                'messages'     => $messages,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /conversations/{id}  — Admin only
    // ──────────────────────────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        Conversation::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted.',
        ]);
    }
}
