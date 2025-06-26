<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ConversationController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index()
    {
        $conversations = Auth::user()
            ->conversations()
            ->with(['messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'models' => $this->chatService->getAvailableModels(),
        ]);
    }

    public function show(Conversation $conversation)
    {
        Gate::authorize('view', $conversation);

        $conversation->load('messages');

        return Inertia::render('Chat/Show', [
            'conversation' => $conversation,
            'models' => $this->chatService->getAvailableModels(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'model' => 'sometimes|string',
            'first_message' => 'sometimes|string|max:2000',
        ]);

        try {
            DB::beginTransaction();

            $conversation = Auth::user()->conversations()->create([
                'model_used' => $request->model ?? 'gpt-3.5-turbo',
                'title' => 'Nouvelle conversation',
            ]);

            if ($request->first_message) {
                $conversation->messages()->create([
                    'role' => 'user',
                    'content' => $request->first_message,
                ]);

                $title = $this->chatService->generateConversationTitle($request->first_message);
                $conversation->update([
                    'title' => $title,
                    'title_generated' => true
                ]);
            }

            DB::commit();

            // Détection améliorée : si c'est une requête AJAX explicite
            $isAjaxRequest = $request->header('X-Requested-With') === 'XMLHttpRequest' ||
                           $request->header('Content-Type') === 'application/json' ||
                           ($request->wantsJson() && $request->ajax());

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'conversation' => $conversation->load('messages')
                ]);
            }

            // Pour toutes les autres requêtes (y compris Inertia), redirection
            return redirect()->route('conversations.show', $conversation)
                           ->with('message', 'Nouvelle conversation créée avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur création conversation: ' . $e->getMessage());

            $isAjaxRequest = $request->header('X-Requested-With') === 'XMLHttpRequest' ||
                           $request->header('Content-Type') === 'application/json' ||
                           ($request->wantsJson() && $request->ajax());

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création de la conversation'
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la création de la conversation');
        }
    }

    public function destroy(Conversation $conversation)
    {
        Gate::authorize('delete', $conversation);

        $conversation->delete();

        return redirect()->route('chat.index')->with('message', 'Conversation supprimée avec succès.');
    }
}
