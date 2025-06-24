<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomInstructionController;
use App\Http\Controllers\MessageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('chat.index');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', function () {
        return redirect()->route('chat.index');
    })->name('dashboard');

    // Ma page principale avec l'interface de Chat
    Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');

    // Messages avec streaming
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/conversations/{conversation}/stream', [MessageController::class, 'stream'])->name('messages.stream');
    Route::post('/conversations/{conversation}/stream-title', [MessageController::class, 'streamTitle'])->name('messages.streamTitle');

    // Route pour le stream du titre en temps réel
    Route::post('/conversations/{conversation}/stream-title', [MessageController::class, 'streamTitle'])->name('messages.streamTitle');

    // Modal pour les instrctions personnalisées
    Route::prefix('api/instructions')->name('instructions.')->group(function () {
        Route::get('/', [CustomInstructionController::class, 'get'])->name('get');
        Route::post('/', [CustomInstructionController::class, 'storeApi'])->name('store.api');
        Route::delete('/', [CustomInstructionController::class, 'destroy'])->name('destroy');
        Route::get('/status', [CustomInstructionController::class, 'status'])->name('status');
    });

    // Les pages que j'ai conservées
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask', [AskController::class, 'ask'])->name('ask.send');
    Route::get('/instructions', [CustomInstructionController::class, 'index'])->name('instructions.index');
    Route::post('/instructions', [CustomInstructionController::class, 'store'])->name('instructions.store');


    Route::get('/api/user/stats', function () {
        $user = Auth::user();
        return response()->json([
            'conversations_count' => $user->conversations()->count(),
            'messages_count' => $user->conversations()->withCount('messages')->get()->sum('messages_count'),
        ]);
    })->name('api.user.stats');
});
