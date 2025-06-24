<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AskController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index()
    {
        return Inertia::render('Ask/Index', [
            'models' => $this->chatService->getAvailableModels(),
            'user' => Auth::user(),
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:2000',
            'model' => 'required|string',
        ]);

        try {
            $response = $this->chatService->sendMessage(
                $request->question,
                $request->model,
                Auth::user()
            );

            return back()->with([
                'question' => $request->question,
                'response' => $response,
                'model' => $request->model,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la génération de la réponse.',
            ]);
        }
    }
}
