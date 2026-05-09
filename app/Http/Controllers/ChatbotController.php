<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * ChatbotController
 *
 * Manages AI chatbot sessions for any authenticated user.
 * Stores full conversation history and proxies to an AI provider.
 *
 * Routes prefix: /api/v1/chatbot
 *
 * Integration: Replace the stub in `getAIResponse()` with your
 * actual AI provider (OpenAI, Gemini, etc.).
 */
class ChatbotController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // GET /chatbot/conversations  — List user's chatbot sessions
    // ──────────────────────────────────────────────────────────────────────
    public function conversations(Request $request): JsonResponse
    {
        $conversations = ChatbotConversation::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data'    => $conversations,
        ]);
    }


    // POST /chatbot/conversations  — Start a new chatbot session
    
    public function startConversation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
        ]);

        $conversation = ChatbotConversation::create([
            'user_id' => $request->user()->id,
            'title'   => $data['title'] ?? 'New Conversation',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chatbot session started.',
            'data'    => ['conversation' => $conversation],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /chatbot/conversations/{id}  — Load session + history
    // ──────────────────────────────────────────────────────────────────────
    public function showConversation(Request $request, int $id): JsonResponse
    {
        $conversation = ChatbotConversation::where('user_id', $request->user()->id)
                                           ->findOrFail($id);

        $messages = ChatbotMessage::where('conversation_id', $id)
                                  ->orderBy('created_at', 'asc')
                                  ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
                'messages'     => $messages,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /chatbot/conversations/{id}/message  — Send a message to the bot
    // ──────────────────────────────────────────────────────────────────────
    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $conversation = ChatbotConversation::where('user_id', $request->user()->id)
                                           ->findOrFail($id);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // 1. Save the user's message
        $userMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $data['message'],
        ]);

        // 2. Get full history for context
        $history = ChatbotMessage::where('conversation_id', $id)
                                 ->orderBy('created_at', 'asc')
                                 ->get()
                                 ->map(fn ($m) => [
                                     'role'    => $m->role,
                                     'content' => $m->content,
                                 ])
                                 ->toArray();

        // 3. Call AI provider
        $aiReply = $this->getAIResponse($history, $request->user());

        // 4. Save bot response
        $botMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $aiReply,
        ]);

        // Update conversation timestamp
        $conversation->touch();

        return response()->json([
            'success' => true,
            'data' => [
                'user_message' => $userMessage,
                'bot_reply'    => $botMessage,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /chatbot/conversations/{id}  — Delete session + history
    // ──────────────────────────────────────────────────────────────────────
    public function deleteConversation(Request $request, int $id): JsonResponse
    {
        ChatbotConversation::where('user_id', $request->user()->id)
                           ->findOrFail($id)
                           ->delete(); // cascades to messages via DB foreign key

        return response()->json([
            'success' => true,
            'message' => 'Chatbot session deleted.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /chatbot/conversations/{id}/clear  — Clear messages, keep session
    // ──────────────────────────────────────────────────────────────────────
    public function clearHistory(Request $request, int $id): JsonResponse
    {
        $conversation = ChatbotConversation::where('user_id', $request->user()->id)
                                           ->findOrFail($id);

        ChatbotMessage::where('conversation_id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation history cleared.',
        ]);
    }

    // ── AI Integration ───────────────────────────────────────────────────

    /**
     * Call your AI provider here.
     * Replace the stub with a real call to OpenAI, Gemini, etc.
     *
     * @param  array  $history  Full message history: [['role'=>'user','content'=>'...'], ...]
     * @param  \App\Models\User  $user  Logged-in user (useful for personalisation)
     * @return string  The AI's reply text
     */
    private function getAIResponse(array $history, $user): string
    {
        /*
        |-----------------------------------------------------------
        | OpenAI GPT-4 Example (uncomment + add OPENAI_API_KEY to .env)
        |-----------------------------------------------------------
        | $response = Http::withToken(config('services.openai.key'))
        |     ->post('https://api.openai.com/v1/chat/completions', [
        |         'model'    => 'gpt-4o',
        |         'messages' => array_merge([
        |             ['role' => 'system', 'content' =>
        |                 'You are a helpful school assistant for Smart School. '
        |                 . 'You help students, teachers, and parents with school-related questions. '
        |                 . "You are speaking with a {$user->role} named {$user->name}."],
        |         ], $history),
        |         'max_tokens' => 800,
        |     ]);
        |
        | return $response->json('choices.0.message.content')
        |     ?? 'Sorry, I could not generate a response.';
        */

        // ── STUB: replace with real AI call ──
        $last = end($history);
        return "Thanks for your message! (AI stub) You said: \"{$last['content']}\". "
             . "In production, this will be replaced with a real AI response.";
    }
}
