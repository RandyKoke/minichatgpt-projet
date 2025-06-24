<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],

            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'question' => fn () => $request->session()->get('question'),
                'response' => fn () => $request->session()->get('response'),
                'model' => fn () => $request->session()->get('model'),
                'error' => fn () => $request->session()->get('error'),
            ],

        ]);
    }
}
